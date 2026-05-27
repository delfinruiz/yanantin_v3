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
    <link rel="stylesheet" href="{{ asset('tenants/empty-tenant-c4071a56-b018-3ee9-927e-31f027b9318b/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.empty-tenant-c4071a56-b018-3ee9-927e-31f027b9318b.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.empty-tenant-c4071a56-b018-3ee9-927e-31f027b9318b.partials.hero')
    </main>

    @include('tenants.empty-tenant-c4071a56-b018-3ee9-927e-31f027b9318b.partials.footer')

    <script src="{{ asset('tenants/empty-tenant-c4071a56-b018-3ee9-927e-31f027b9318b/js/scripts.js') }}"></script>
</body>
</html>