<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripcion Suspendida - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: system-ui,-apple-system,sans-serif; margin: 0; padding: 0; min-height: 100dvh; display: flex; align-items: center; justify-content: center; background: #f8fafc }
    </style>
</head>
<body>
    <div style="text-align:center;max-width:480px;padding:2rem">
        <div style="display:flex;align-items:center;justify-content:center;width:4rem;height:4rem;margin:0 auto 1.5rem;border-radius:9999px;background:#fee2e2">
            <svg style="width:2rem;height:2rem;color:#dc2626" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        </div>
        <h1 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 0.75rem">Suscripcion Suspendida</h1>
        <p style="font-size:1rem;color:#475569;line-height:1.6;margin:0 0 1.5rem">
            Tu suscripcion ha sido suspendida por falta de pago prolongado. Lamentablemente, el acceso al panel de administracion ya no esta disponible.
        </p>
        <p style="font-size:0.9375rem;color:#64748b;margin:0 0 2rem">
            Para conocer tus opciones, comunicate con nosotros a<br>
            <a href="mailto:contacto@cahilt.com" style="color:#dc2626;font-weight:600;text-decoration:none">contacto@cahilt.com</a>
        </p>
        <a href="/" style="display:inline-block;background:#fff;color:#475569;border:1px solid #e2e8f0;padding:0.625rem 1.5rem;border-radius:0.5rem;font-size:0.875rem;font-weight:500;text-decoration:none">Volver al inicio</a>
    </div>
</body>
</html>
