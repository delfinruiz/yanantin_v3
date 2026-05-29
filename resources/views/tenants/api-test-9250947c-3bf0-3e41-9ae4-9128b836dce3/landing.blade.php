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
    <link rel="stylesheet" href="{{ asset('tenants/api-test-9250947c-3bf0-3e41-9ae4-9128b836dce3/css/styles.css') }}">
</head>
<body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

    @include('tenants.api-test-9250947c-3bf0-3e41-9ae4-9128b836dce3.partials.navbar')

    <main class="max-w-5xl mx-auto px-6 py-16">
        @include('tenants.api-test-9250947c-3bf0-3e41-9ae4-9128b836dce3.partials.hero')
    </main>

    @include('tenants.api-test-9250947c-3bf0-3e41-9ae4-9128b836dce3.partials.footer')

    <script src="{{ asset('tenants/api-test-9250947c-3bf0-3e41-9ae4-9128b836dce3/js/scripts.js') }}"></script>
</body>
</html>