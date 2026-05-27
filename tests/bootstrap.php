<?php

// Clear config cache to prevent using cached dev DB config during tests.
// PHPUnit env vars (DB_DATABASE=laravel_test) are ignored when config cache exists.
$cachedConfig = __DIR__.'/../bootstrap/cache/config.php';
if (file_exists($cachedConfig)) {
    @unlink($cachedConfig);
}

require __DIR__.'/../vendor/autoload.php';
