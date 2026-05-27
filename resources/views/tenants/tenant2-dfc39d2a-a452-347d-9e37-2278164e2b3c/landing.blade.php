<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenant->name }}</title>
    @if($tenant->faviconUrl())
        <link rel="icon" href="{{ $tenant->faviconUrl() }}" type="image/x-icon">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('tenants/tenant2-dfc39d2a-a452-347d-9e37-2278164e2b3c/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.tenant2-dfc39d2a-a452-347d-9e37-2278164e2b3c.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.tenant2-dfc39d2a-a452-347d-9e37-2278164e2b3c.partials.hero')
    </main>

    @include('tenants.tenant2-dfc39d2a-a452-347d-9e37-2278164e2b3c.partials.footer')

    <script src="{{ asset('tenants/tenant2-dfc39d2a-a452-347d-9e37-2278164e2b3c/js/scripts.js') }}"></script>
</body>
</html>