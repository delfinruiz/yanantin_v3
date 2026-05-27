<?php

$ok = 0;
$fix = 0;
$warn = 0;

function report(string $status, string $message): void
{
    global $ok, $fix, $warn;

    $icon = match ($status) {
        'OK' => "\033[32m[OK]\033[0m",
        'FIX' => "\033[33m[FIX]\033[0m",
        'WARN' => "\033[31m[WARN]\033[0m",
        default => "[{$status}]",
    };

    match ($status) {
        'OK' => $ok++,
        'FIX' => $fix++,
        'WARN' => $warn++,
    };

    echo "  {$icon}  {$message}\n";
}

function ensureDir(string $path): void
{
    if (! is_dir($path)) {
        mkdir($path, 0775, true);
        report('FIX', "Directorio creado: {$path}");
    } else {
        report('OK', "Directorio existe: {$path}");
    }
}

function fixPermissions(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $user = posix_getpwuid(posix_geteuid())['name'] ?? 'www-data';

    exec("chown -R {$user}:{$user} {$path} 2>/dev/null", $out, $code);

    if ($code === 0) {
        report('FIX', "Permisos corregidos: {$path} (dueño: {$user})");
    } else {
        report('WARN', "No se pudo cambiar dueño de {$path} (ejecutar como root)");
    }

    exec("find {$path} -type d -exec chmod 775 {} \\; 2>/dev/null");
    exec("find {$path} -type f -exec chmod 664 {} \\; 2>/dev/null");
}

function cleanLivewireTmp(string $path): int
{
    if (! is_dir($path)) {
        return 0;
    }

    $cleaned = 0;
    $files = glob($path.'/*.json');

    foreach ($files as $jsonFile) {
        $base = pathinfo($jsonFile, PATHINFO_FILENAME);
        $hasImage = false;

        foreach (['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico'] as $ext) {
            if (file_exists($path.'/'.$base.'.'.$ext)) {
                $hasImage = true;
                break;
            }
        }

        if (! $hasImage) {
            unlink($jsonFile);
            $cleaned++;
        }
    }

    return $cleaned;
}

echo "\n\033[1m=== Diagnostico y Fix Yanantin ===\033[0m\n\n";

// Detectar raíz del proyecto (funciona desde cualquier ubicación)
$base = __DIR__;
while (! file_exists($base.'/artisan') && $base !== dirname($base)) {
    $base = dirname($base);
}

if (! file_exists($base.'/artisan')) {
    echo "\033[31m[ERROR]\033[0m No se encontró artisan. Ejecuta desde la raíz del proyecto.\n";
    exit(1);
}

$public = $base.'/public';
$storage = $base.'/storage';
$bootstrap = $base.'/bootstrap/cache';

// 1. Symlink public/storage -> storage/app/public
$symlink = $public.'/storage';
$target = $storage.'/app/public';

if (is_link($symlink)) {
    $current = readlink($symlink);

    if (realpath($current) === realpath($target)) {
        report('OK', 'Symlink public/storage -> storage/app/public');
    } else {
        unlink($symlink);
        symlink($target, $symlink);
        report('FIX', "Symlink recreado: public/storage -> {$target}");
    }
} elseif (is_dir($symlink)) {
    rename($symlink, $symlink.'_bak_'.date('YmdHis'));
    symlink($target, $symlink);
    report('FIX', 'Symlink creado (directorio anterior renombrado): public/storage');
} else {
    symlink($target, $symlink);
    report('FIX', 'Symlink creado: public/storage -> storage/app/public');
}

// 2. Directorios necesarios
echo "\n  \033[1mDirectorios:\033[0m\n";
ensureDir($storage.'/app/private/livewire-tmp');
ensureDir($storage.'/app/public/tenants/branding');
ensureDir($storage.'/app/public/avatars');
ensureDir($storage.'/framework/cache');
ensureDir($storage.'/framework/sessions');
ensureDir($storage.'/framework/views');

// 3. Permisos
echo "\n  \033[1mPermisos:\033[0m\n";
fixPermissions($storage);
fixPermissions($bootstrap);

// 4. Limpiar Livewire temp huerfanos
echo "\n  \033[1mLimpieza:\033[0m\n";
$cleaned = cleanLivewireTmp($storage.'/app/private/livewire-tmp');

if ($cleaned > 0) {
    report('FIX', "Limpiados {$cleaned} archivos temporales huerfanos de Livewire");
} else {
    report('OK', 'Livewire temp limpio');
}

// 5. Caches de Laravel
echo "\n  \033[1mCaches:\033[0m\n";

$artisan = $base.'/artisan';
$php = file_exists('/usr/local/bin/ea-php84') ? '/usr/local/bin/ea-php84' : PHP_BINARY;

$commands = [
    'view:clear' => 'Vistas compiladas',
    'config:clear' => 'Configuracion',
    'route:clear' => 'Rutas',
    'event:clear' => 'Eventos',
    'cache:clear' => 'Cache de aplicacion',
];

foreach ($commands as $cmd => $label) {
    exec("{$php} {$artisan} {$cmd} --no-interaction 2>&1", $out, $code);

    if ($code === 0) {
        report('OK', "Cache limpiado: {$label}");
    } else {
        report('WARN', "No se pudo limpiar: {$label}");
    }
}

// 6. Regenerar caches
echo "\n  \033[1mRegenerando caches:\033[0m\n";

$regenCommands = [
    'config:cache' => 'Configuracion',
    'route:cache' => 'Rutas',
    'view:cache' => 'Vistas',
    'event:cache' => 'Eventos',
];

foreach ($regenCommands as $cmd => $label) {
    exec("{$php} {$artisan} {$cmd} --no-interaction 2>&1", $out, $code);

    if ($code === 0) {
        report('OK', "Cache regenerado: {$label}");
    } else {
        report('WARN', "No se pudo regenerar: {$label}");
    }
}

// Resumen
echo "\n\033[1m=== Resumen: {$ok} OK, {$fix} FIX, {$warn} WARN ===\033[0m\n\n";
