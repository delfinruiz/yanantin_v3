<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenant->name }}</title>
    <style>{!! $css !!}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {!! $html !!}
</body>
</html>
