<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS Multitenancy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">
            <a href="/" class="text-xl font-bold text-blue-600">SaaS Platform</a>
            <div class="flex gap-4 items-center">
                <a href="/" class="text-gray-600 hover:text-gray-900">Inicio</a>
                <a href="/central" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition">
                    Panel Central
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-full mx-auto px-4 py-24 text-center">
        <h1 class="text-5xl font-bold text-gray-900 mb-6">
            Plataforma Multi-Tenant
        </h1>
        <p class="text-xl text-gray-600 mb-10 max-w-2xl mx-auto">
            Crea y gestiona multiples tenants con su propio subdominio, base de datos independiente y pagina principal personalizada.
        </p>
        <div class="flex justify-center gap-4">
            <a href="/central" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-medium text-lg hover:bg-blue-700 transition">
                Ir al Panel Central
            </a>
        </div>
    </main>

    <footer class="text-center py-8 text-gray-500 text-sm">
        &copy; {{ date('Y') }} SaaS Platform. Todos los derechos reservados.
    </footer>
</body>
</html>
