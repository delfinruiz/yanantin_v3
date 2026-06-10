<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SetupChecklistWidget extends Widget
{
    protected string $view = 'filament.widgets.setup-checklist';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        if (! tenant()) {
            return false;
        }

        $steps = self::getSteps();

        return collect($steps)->where('mandatory', true)->contains(fn ($s) => ! $s['done']);
    }

    public function getViewData(): array
    {
        $steps = self::getSteps();
        $mandatorySteps = collect($steps)->where('mandatory', true);
        $mandatoryDone = $mandatorySteps->where('done', true)->count();
        $mandatoryTotal = $mandatorySteps->count();
        $optionalSteps = collect($steps)->where('mandatory', false);

        return [
            'steps' => $steps,
            'mandatoryDone' => $mandatoryDone,
            'mandatoryTotal' => $mandatoryTotal,
            'optionalSteps' => $optionalSteps,
            'progressPercent' => $mandatoryTotal > 0
                ? round(($mandatoryDone / $mandatoryTotal) * 100)
                : 0,
        ];
    }

    private static function getSteps(): array
    {
        $tenant = tenant();

        return [
            [
                'key' => 'timezone',
                'title' => 'Zona Horaria',
                'description' => 'Define la zona horaria del tenant para sincronización.',
                'mandatory' => true,
                'done' => filled($tenant?->timezone)
                    && $tenant->timezone !== config('app.timezone'),
            ],
            [
                'key' => 'openai',
                'title' => 'API OpenAI',
                'description' => 'Token para generación de mensajes y sugerencias con IA.',
                'mandatory' => true,
                'done' => filled($tenant?->token_ai),
            ],
            [
                'key' => 'cpanel',
                'title' => 'Cpanel',
                'description' => 'Host, usuario, token API y contraseña para WebDAV.',
                'mandatory' => true,
                'done' => filled($tenant?->cpanel_host)
                    && filled($tenant?->cpanel_user)
                    && filled($tenant?->cpanel_token)
                    && filled($tenant?->cpanel_password),
            ],
            [
                'key' => 'zadarma',
                'title' => 'Token Zadarma',
                'description' => 'Integración VoIP para telefonía (opcional).',
                'mandatory' => false,
                'done' => filled($tenant?->zadarma_token),
            ],
        ];
    }
}
