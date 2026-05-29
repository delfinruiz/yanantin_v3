<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiMessageService
{
    protected ?string $apiKey;

    public string $model;

    protected static array $fallbackMessages = [
        'happy' => [
            'Que alegria que tengas un excelente dia! Sigue brillando y contagiando tu energia positiva a los demas.',
            'Tu entusiasmo es inspirador! Hoy es un gran dia para lograr cosas increibles.',
            'Que bien que te sientas asi! Aprovecha esta energia para hacer realidad tus metas.',
        ],
        'med_happy' => [
            'Que bueno que te sientas bien! Sigue asi, cada dia es una nueva oportunidad.',
            'Tu actitud positiva hace la diferencia. Disfruta este momento y compartelo con los demas.',
            'Un buen dia es el comienzo de algo grande. Sigue adelante con esa sonrisa!',
        ],
        'neutral' => [
            'Un dia neutral tambien es valioso. A veces la calma nos permite ver las cosas con claridad.',
            'No pasa nada si hoy te sientes asi. Manana es otro dia lleno de posibilidades.',
            'Los dias neutrales son perfectos para reflexionar y recargar energias.',
        ],
        'med_sad' => [
            'Entendemos que no todos los dias son buenos. Recuerda que esto es temporal y pasara.',
            'A veces necesitamos un respiro. No te preocupes, estaremos aqui para apoyarte.',
            'Los momentos dificiles nos hacen mas fuertes. Cuentas con nosotros para lo que necesites.',
        ],
        'sad' => [
            'Lamentamos que te sientas asi. Recuerda que tu bienestar es importante y no estas solo.',
            'Los dias dificiles son parte del camino. Permite sentir y busca apoyo cuando lo necesites.',
            'Valoramos tu honestidad al compartir como te sientes. Estamos aqui para apoyarte en todo momento.',
        ],
    ];

    protected static array $fallbackSuggestions = [
        'Se recomienda realizar actividades de integracion para fortalecer el trabajo en equipo.',
        'Fomentar la comunicacion abierta y el feedback constructivo entre los miembros del equipo.',
        'Implementar pausas activas y espacios de relajacion durante la jornada laboral.',
        'Reconocer y celebrar los logros individuales y colectivos del equipo.',
        'Promover un equilibrio saludable entre la vida laboral y personal.',
        'Organizar talleres de desarrollo personal y profesional.',
    ];

    public function __construct()
    {
        $this->apiKey = tenant()?->token_ai ?? config('services.openai.key');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    public function generateDailyMessage(User $user, string $mood, int $score): string
    {
        if (! $this->apiKey) {
            return $this->getFallbackMessage($mood);
        }

        $moodLabels = [
            'sad' => 'triste',
            'med_sad' => 'un poco triste',
            'neutral' => 'neutral',
            'med_happy' => 'alegre',
            'happy' => 'muy feliz',
        ];

        $label = $moodLabels[$mood] ?? $mood;

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->connectTimeout(5)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un asistente de bienestar laboral. Genera un mensaje corto y positivo (maximo 240 caracteres) en espanol para un empleado que se siente '.$label.'. Usa emojis. Se empatico y motivador.',
                        ],
                        [
                            'role' => 'user',
                            'content' => 'Genera un mensaje personalizado para alguien que se siente '.$label.' hoy.',
                        ],
                    ],
                    'max_tokens' => 150,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $message = $response->json('choices.0.message.content');

                return trim($message) ?: $this->getFallbackMessage($mood);
            }

            Log::warning('OpenAI daily message failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Exception $e) {
            Log::warning('OpenAI daily message exception', ['error' => $e->getMessage()]);
        }

        return $this->getFallbackMessage($mood);
    }

    public function generateOrganizationalSuggestions(array $moodDistribution, float $averageScore): string
    {
        if (! $this->apiKey) {
            return implode("\n", $this->getFallbackSuggestions());
        }

        $total = array_sum($moodDistribution);
        $summary = [];
        foreach ($moodDistribution as $mood => $count) {
            $summary[] = "$mood: $count";
        }

        $level = match (true) {
            $averageScore >= 75 => 'alta',
            $averageScore >= 50 => 'moderada',
            default => 'baja',
        };

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(20)
                ->connectTimeout(5)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un consultor de recursos humanos. Genera 4 a 6 sugerencias numeradas en espanol para mejorar el clima laboral basandote en los datos de humor de los empleados. La felicidad organizacional es '.$level.'. Las sugerencias deben ser especificas, practicas y accionables.',
                        ],
                        [
                            'role' => 'user',
                            'content' => 'La distribucion de humor de hoy es: '.implode(', ', $summary).'. El nivel de felicidad promedio es '.number_format($averageScore, 1).'/100. Genera sugerencias para mejorar.',
                        ],
                    ],
                    'max_tokens' => 400,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $suggestion = $response->json('choices.0.message.content');

                return trim($suggestion) ?: implode("\n", $this->getFallbackSuggestions());
            }

            Log::warning('OpenAI suggestions failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Exception $e) {
            Log::warning('OpenAI suggestions exception', ['error' => $e->getMessage()]);
        }

        return implode("\n", $this->getFallbackSuggestions());
    }

    public function generateCompanySuggestions(array $stats): ?array
    {
        $apiKey = $this->apiKey;
        $model = $this->model;

        $summary = json_encode($stats, JSON_UNESCAPED_UNICODE);

        if (! $apiKey) {
            return [
                'text' => $this->fallbackCompanySuggestions($stats),
                'model' => 'fallback',
            ];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->connectTimeout(5)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.6,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres consultor en RR.HH. y bienestar. Devuelve 2 acciones concretas, numeradas, cada una en ~1 linea, enfocadas en mejorar el clima laboral y productividad. Espanol neutro.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Datos de animo agregados: {$summary}\nPropon acciones priorizadas para la empresa. Evita jerga tecnica.",
                        ],
                    ],
                ]);

            $text = trim((string) data_get($response, 'choices.0.message.content', ''));

            return [
                'text' => $text ?: $this->fallbackCompanySuggestions($stats),
                'model' => $model,
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenAI suggestions error: '.$e->getMessage());

            return [
                'text' => $this->fallbackCompanySuggestions($stats),
                'model' => 'fallback',
            ];
        }
    }

    protected function fallbackCompanySuggestions(array $stats): string
    {
        return "1) Promover pausas activas y check-ins breves diarios\n"
            .'2) Reconocer logros semanales en equipo';
    }

    protected function getFallbackMessage(string $mood): string
    {
        $messages = static::$fallbackMessages[$mood] ?? static::$fallbackMessages['neutral'];

        return $messages[array_rand($messages)];
    }

    protected function getFallbackSuggestions(): array
    {
        return static::$fallbackSuggestions;
    }
}
