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
    <link rel="stylesheet" href="{{ asset('tenants/empty-tenant-3ccf81c6-0342-3f72-850a-270be3235080/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.empty-tenant-3ccf81c6-0342-3f72-850a-270be3235080.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.empty-tenant-3ccf81c6-0342-3f72-850a-270be3235080.partials.hero')
    </main>

    @include('tenants.empty-tenant-3ccf81c6-0342-3f72-850a-270be3235080.partials.footer')

    <script src="{{ asset('tenants/empty-tenant-3ccf81c6-0342-3f72-850a-270be3235080/js/scripts.js') }}"></script>
</body>
</html>