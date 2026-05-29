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
    <link rel="stylesheet" href="{{ asset('tenants/no-domain-test-385b43ac-42ea-3f81-9e7b-6cd535f790e8/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.no-domain-test-385b43ac-42ea-3f81-9e7b-6cd535f790e8.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.no-domain-test-385b43ac-42ea-3f81-9e7b-6cd535f790e8.partials.hero')
    </main>

    @include('tenants.no-domain-test-385b43ac-42ea-3f81-9e7b-6cd535f790e8.partials.footer')

    <script src="{{ asset('tenants/no-domain-test-385b43ac-42ea-3f81-9e7b-6cd535f790e8/js/scripts.js') }}"></script>
</body>
</html>