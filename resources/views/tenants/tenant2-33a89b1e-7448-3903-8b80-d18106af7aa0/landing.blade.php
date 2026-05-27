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
    <link rel="stylesheet" href="{{ asset('tenants/tenant2-33a89b1e-7448-3903-8b80-d18106af7aa0/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.tenant2-33a89b1e-7448-3903-8b80-d18106af7aa0.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.tenant2-33a89b1e-7448-3903-8b80-d18106af7aa0.partials.hero')
    </main>

    @include('tenants.tenant2-33a89b1e-7448-3903-8b80-d18106af7aa0.partials.footer')

    <script src="{{ asset('tenants/tenant2-33a89b1e-7448-3903-8b80-d18106af7aa0/js/scripts.js') }}"></script>
</body>
</html>