---
name: testing-database-isolation
description: "Aplica esta skill al escribir, modificar o ejecutar tests. Los tests NUNCA deben usar la base de datos de produccion ni la local de desarrollo. Usar una base de datos separada para testing (SQLite o MySQL separado). Al finalizar los tests exitosamente, limpiar archivos y registros creados durante las pruebas."
---

# Aislamiento de base de datos para tests

## Regla 1: Base de datos separada

Los tests NUNCA corren contra `DB_DATABASE=laravel` (la base de desarrollo). Usan una base de datos independiente configurada en `phpunit.xml`.

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Si el proyecto requiere MySQL (ej: multi-database tenancy), usar una base de datos separada:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="laravel_test"/>
```

## Regla 2: Eliminar config cache antes de tests

Cuando existe `bootstrap/cache/config.php` (generado por `php artisan optimize` o `php artisan config:cache`), las variables de entorno definidas en `phpunit.xml` son ignoradas porque la cache ya tiene los valores computados. Esto provoca que `migrate:fresh` apunte a `DB_DATABASE=laravel` (desarrollo) en vez de `DB_DATABASE=laravel_test`.

**Solucion**: `phpunit.xml` debe usar un bootstrap personalizado que elimine la cache de configuracion ANTES de que la aplicacion arranque:

```xml
bootstrap="tests/bootstrap.php"
```

Contenido de `tests/bootstrap.php`:
```php
<?php

$cachedConfig = __DIR__ . '/../bootstrap/cache/config.php';
if (file_exists($cachedConfig)) {
    @unlink($cachedConfig);
}

require __DIR__ . '/../vendor/autoload.php';
```

## Regla 3: Limpieza automatica en TestCase::tearDown

Los archivos creados por `SeedTenantAdmin` (landing pages) NO se revierten con `RefreshDatabase`. Agregar limpieza en `tearDown()`:

```php
protected function tearDown(): void
{
    if ($this->currentTenant) {
        tenancy()->end();

        $slug = $this->currentTenant->domain_name
            ?? $this->currentTenant->domains()->first()?->domain
            ?? $this->currentTenant->id;

        foreach ([resource_path('views/tenants/'.$slug), public_path('tenants/'.$slug)] as $dir) {
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
        }

        $this->currentTenant = null;
    }

    parent::tearDown();
}
```

## Regla 4: Limpieza manual post-tests (si hicieron falta)

Si se ejecutaron tests que no limpiaron archivos, ejecutar:

```bash
rm -rf resources/views/tenants/{test-*,my-tenant-*,new-tenant-*} public/tenants/{test-*,my-tenant-*,new-tenant-*}
```

Si se usa MySQL para testing, tambien eliminar las bases tenant:

```bash
mysql -u$DB_USERNAME -p$DB_PASSWORD -e "SHOW DATABASES LIKE 'tenant_test_%'" | \
  tail -n +3 | while read db; do mysql -u$DB_USERNAME -p$DB_PASSWORD -e "DROP DATABASE IF EXISTS \`$db\`"; done
```

## Verificacion

Antes de considerar un cambio como completado:
1. `phpunit.xml` usa una base de datos de testing (NO `laravel`)
2. `php artisan test` pasa
3. Se ejecuta la limpieza post-tests
4. La base de desarrollo (`laravel`) queda intacta
