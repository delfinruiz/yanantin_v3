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
    <link rel="stylesheet" href="{{ asset('tenants/tenant2-24ff9cb2-8036-36b4-a248-0b18b2a1370d/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.tenant2-24ff9cb2-8036-36b4-a248-0b18b2a1370d.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.tenant2-24ff9cb2-8036-36b4-a248-0b18b2a1370d.partials.hero')
    </main>

    @include('tenants.tenant2-24ff9cb2-8036-36b4-a248-0b18b2a1370d.partials.footer')

    <script src="{{ asset('tenants/tenant2-24ff9cb2-8036-36b4-a248-0b18b2a1370d/js/scripts.js') }}"></script>
</body>
</html>