<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reunion Finalizada - {{ $room?->name ?? 'Videoconferencia' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ tenant()?->faviconUrl() ?? asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'media',
            theme: {
                extend: {
                    animation: {
                        'bounce-slow': 'bounce 2s infinite',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4 transition-colors duration-300">
    <div class="w-full max-w-lg">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden transition-colors duration-300">
            <div class="p-10 md:p-14 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full mb-6 animate-bounce-slow">
                    <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-3">
                    Reunion Finalizada
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">
                    Gracias por participar en la reunion
                </p>
            </div>
        </div>

        <div class="text-center mt-8">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ config('app.name') }} - Plataforma de Videoconferencia
            </p>
        </div>
    </div>
</body>
</html>
