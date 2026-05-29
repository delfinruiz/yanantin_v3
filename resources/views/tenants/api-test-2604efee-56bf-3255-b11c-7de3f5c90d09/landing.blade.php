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
    <link rel="stylesheet" href="{{ asset('tenants/api-test-2604efee-56bf-3255-b11c-7de3f5c90d09/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.api-test-2604efee-56bf-3255-b11c-7de3f5c90d09.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.api-test-2604efee-56bf-3255-b11c-7de3f5c90d09.partials.hero')
    </main>

    @include('tenants.api-test-2604efee-56bf-3255-b11c-7de3f5c90d09.partials.footer')

    <script src="{{ asset('tenants/api-test-2604efee-56bf-3255-b11c-7de3f5c90d09/js/scripts.js') }}"></script>
</body>
</html>