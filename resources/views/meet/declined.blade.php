<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitacion rechazada - {{ $room->name }}</title>
    <link rel="icon" href="{{ tenant()?->faviconUrl() ?? asset('favicon.ico') }}">
    @vite('resources/css/app.css')
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900">
    <div class="min-h-full flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 text-center">
            <div class="mx-auto w-16 h-16 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Invitacion rechazada</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-1">Has rechazado la invitacion a la reunion:</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ $room->name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Si fue un error, contacta al organizador para que te envie una nueva invitacion.</p>
        </div>
    </div>
</body>
</html>
