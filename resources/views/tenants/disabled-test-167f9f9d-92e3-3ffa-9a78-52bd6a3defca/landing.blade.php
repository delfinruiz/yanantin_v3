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
    <link rel="stylesheet" href="{{ asset('tenants/disabled-test-167f9f9d-92e3-3ffa-9a78-52bd6a3defca/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.disabled-test-167f9f9d-92e3-3ffa-9a78-52bd6a3defca.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.disabled-test-167f9f9d-92e3-3ffa-9a78-52bd6a3defca.partials.hero')
    </main>

    @include('tenants.disabled-test-167f9f9d-92e3-3ffa-9a78-52bd6a3defca.partials.footer')

    <script src="{{ asset('tenants/disabled-test-167f9f9d-92e3-3ffa-9a78-52bd6a3defca/js/scripts.js') }}"></script>
</body>
</html>