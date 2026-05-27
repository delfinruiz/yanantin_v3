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
    <link rel="stylesheet" href="{{ asset('tenants/disabled-test-60a4c4e8-7fe8-3345-9967-cd0d9d867bba/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.disabled-test-60a4c4e8-7fe8-3345-9967-cd0d9d867bba.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.disabled-test-60a4c4e8-7fe8-3345-9967-cd0d9d867bba.partials.hero')
    </main>

    @include('tenants.disabled-test-60a4c4e8-7fe8-3345-9967-cd0d9d867bba.partials.footer')

    <script src="{{ asset('tenants/disabled-test-60a4c4e8-7fe8-3345-9967-cd0d9d867bba/js/scripts.js') }}"></script>
</body>
</html>