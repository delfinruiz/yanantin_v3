<?php

namespace App\Console\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class PlansSyncCommand extends Command
{
    protected $signature = 'plans:sync
        {--panel=admin : Panel a escanear}
        {--add : Agregar automaticamente las features faltantes al config}';

    protected $description = 'Escanea los recursos del panel y verifica que esten declarados en config/plans.php';

    public function handle(): int
    {
        $panelName = $this->option('panel');
        $this->info("Escaneando recursos del panel '{$panelName}'...");

        $panel = Filament::getPanel($panelName);

        if (! $panel) {
            $this->error("El panel '{$panelName}' no existe.");

            return self::FAILURE;
        }

        $resources = $panel->getResources();

        if (empty($resources)) {
            $this->warn('No se encontraron recursos en el panel.');

            return self::SUCCESS;
        }

        $features = config('plans.features', []);

        $knownEntities = collect($features)
            ->pluck('entities')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        $foundEntities = collect($resources)
            ->map(fn ($resource) => class_basename($resource::getModel()))
            ->unique()
            ->values()
            ->toArray();

        $missing = array_diff($foundEntities, $knownEntities);
        $extra = array_diff($knownEntities, $foundEntities);

        $this->newLine();

        if (empty($missing) && empty($extra)) {
            $this->info('✓ Todos los recursos estan declarados en config/plans.php');

            return self::SUCCESS;
        }

        if (! empty($missing)) {
            $this->warn('Recursos sin feature en config/plans.php:');
            foreach ($missing as $entity) {
                $this->line("  - {$entity}");
            }
            $this->newLine();

            if ($this->option('add') || confirm('¿Agregar features faltantes a config/plans.php?', false)) {
                $this->addMissingFeatures($missing, $features);
            }
        }

        if (! empty($extra)) {
            $this->warn('Features en config/plans.php que no corresponden a ningun recurso:');
            foreach ($extra as $entity) {
                $this->line("  - {$entity}");
            }
            $this->newLine();
            $this->line('Puedes eliminarlas manualmente de config/plans.php si ya no se usan.');
        }

        $this->newLine();
        $this->info('Para generar permisos de los nuevos modulos: php artisan shield:generate --all --panel='.$this->option('panel'));
        $this->line('Para sincronizar permisos a tenants existentes: php artisan tinker --execute \'Artisan::call("tenants:sync-permissions")\'');

        return self::SUCCESS;
    }

    protected function addMissingFeatures(array $missing, array $features): void
    {
        $configPath = config_path('plans.php');
        $config = file_exists($configPath) ? require $configPath : [];

        foreach ($missing as $entity) {
            $key = str($entity)->lower()->plural()->toString();
            $label = str($entity)->plural()->headline()->toString();

            $config['features'][$key] = [
                'label' => $label,
                'entities' => [$entity],
            ];

            $this->info("  ✓ Agregada feature '{$key}' -> {$label} ({$entity})");
        }

        $export = var_export($config, true);

        file_put_contents($configPath, "<?php\n\nreturn {$export};\n");

        $this->newLine();
        $this->info('config/plans.php actualizado. Revisa el archivo y ajusta los labels si es necesario.');
    }
}
