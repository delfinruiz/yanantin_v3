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
    <link rel="stylesheet" href="{{ asset('tenants/cleanup-users-test-7ea33543-915c-3f23-88ca-baff6770a64f/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.cleanup-users-test-7ea33543-915c-3f23-88ca-baff6770a64f.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.cleanup-users-test-7ea33543-915c-3f23-88ca-baff6770a64f.partials.hero')
    </main>

    @include('tenants.cleanup-users-test-7ea33543-915c-3f23-88ca-baff6770a64f.partials.footer')

    <script src="{{ asset('tenants/cleanup-users-test-7ea33543-915c-3f23-88ca-baff6770a64f/js/scripts.js') }}"></script>
</body>
</html>