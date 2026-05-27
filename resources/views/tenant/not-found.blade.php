<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No encontrado</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="text-center max-w-lg px-4">
        <h1 class="text-6xl font-bold text-gray-300 mb-4">404</h1>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Dominio no encontrado</h2>
        <p class="text-gray-600 mb-8">
            El subdominio que intentas acceder no esta registrado en el sistema.
        </p>
        <a href="http://localhost:8000"
           class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition">
            Ir al inicio
        </a>
    </div>
</body>
</html>
