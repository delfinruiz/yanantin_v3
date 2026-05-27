<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-[#070919] min-h-screen">
    <nav class="bg-white shadow">
        <div class="max-w-5xl mx-auto px-4 flex justify-center items-center h-24 relative">
            <a href="/" class="text-xl font-bold text-gray-800">
                {{ $tenant->name }}
            </a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-16">
        <div class="text-center">
            <video src="{{ asset('videos/seccion1_light.mp4') }}" autoplay loop muted playsinline class="mx-auto mb-6 max-w-full h-auto max-h-64 block dark:hidden"></video>
            <video src="{{ asset('videos/seccion1_dark.mp4') }}" autoplay loop muted playsinline class="mx-auto mb-6 max-w-full h-auto max-h-64 hidden dark:block"></video>
            <h1 class="text-4xl font-bold text-gray-800 mb-4">
                Bienvenido a Yanantin
            </h1>
            <p class="text-lg text-gray-600 mb-8">
                Esta es tu pagina principal de muestra. Personalizala desde el panel de administracion.
            </p>
            <a href="/admin"
               class="inline-block bg-amber-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-amber-600 transition">
                Ir al Panel Admin
            </a>
        </div>
    </main>

    <footer class="text-center py-8 text-gray-500 text-sm">
        &copy; {{ date('Y') }} {{ $tenant->name }}.
    </footer>
</body>
</html>
