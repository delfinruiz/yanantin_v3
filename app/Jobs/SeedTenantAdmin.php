<?php

namespace App\Jobs;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SeedTenantAdmin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Tenant $tenant,
    ) {}

    public function handle(): void
    {
        tenancy()->initialize($this->tenant);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@'.$this->getTenantSlug().'.localhost',
            'password' => Hash::make('password'),
            'is_internal' => true,
        ]);

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $this->copyGlobalRolePermissions($superAdmin);

        Role::firstOrCreate([
            'name' => 'Público',
            'guard_name' => 'web',
        ]);

        $user->assignRole($superAdmin);

        tenancy()->end();

        $this->createTenantLandingDirectory();
    }

    protected function copyGlobalRolePermissions(Role $role): void
    {
        $globalRoleIds = DB::table('roles')
            ->where('name', $role->name)
            ->whereNull('tenant_id')
            ->pluck('id');

        if ($globalRoleIds->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('role_has_permissions')
            ->whereIn('role_id', $globalRoleIds)
            ->pluck('permission_id')
            ->unique()
            ->values();

        if ($permissionIds->isEmpty()) {
            return;
        }

        $allowed = $this->tenant->allowedEntities();

        if (! empty($allowed)) {
            $permissions = DB::table('permissions')
                ->whereIn('id', $permissionIds)
                ->whereNull('tenant_id')
                ->pluck('name', 'id');

            $permissionIds = $permissions
                ->filter(fn ($name) => $this->permissionMatchesPlan($name, $allowed))
                ->keys();
        }

        if ($permissionIds->isNotEmpty()) {
            $role->syncPermissions($permissionIds->toArray());
        }
    }

    protected function permissionMatchesPlan(string $permissionName, array $allowedEntities): bool
    {
        $parts = explode(':', $permissionName);

        if (count($parts) < 2) {
            return true;
        }

        $entity = end($parts);

        return in_array($entity, $allowedEntities);
    }

    protected function createTenantLandingDirectory(): void
    {
        $slug = $this->getTenantSlug();
        $base = resource_path('views/tenants/'.$slug);
        $publicBase = public_path('tenants/'.$slug);

        if (! File::isDirectory($base)) {
            try {
                File::makeDirectory($base, 0755, true);
            } catch (\Exception $e) {
                $this->tenant->update([
                    'landing_dir_error' => 'No se pudo crear la carpeta. Crea manualmente: mkdir -p '
                        .$base.'/partials '.$publicBase.'/css '.$publicBase.'/js',
                ]);

                Log::warning('No se pudo crear el directorio de landing para '.$slug.': '.$e->getMessage());

                return;
            }
        }

        $directories = [$base.'/partials', $publicBase.'/css', $publicBase.'/js'];

        foreach ($directories as $dir) {
            $this->ensureDirectory($dir, $slug);
        }

        $this->ensureFile($publicBase.'/css/styles.css', $this->getDefaultCss(), $slug);
        $this->ensureFile($publicBase.'/js/scripts.js', $this->getDefaultJs(), $slug);
        $this->ensureFile($base.'/partials/navbar.blade.php', $this->getNavbarPartial(), $slug);
        $this->ensureFile($base.'/partials/footer.blade.php', $this->getFooterPartial(), $slug);
        $this->ensureFile($base.'/partials/hero.blade.php', $this->getHeroPartial(), $slug);

        $this->ensureFile($base.'/landing.blade.php', $this->getLandingMain($slug), $slug);
    }

    protected function getTenantSlug(): string
    {
        return $this->tenant->domain_name
            ?? $this->tenant->domains()->first()?->domain
            ?? $this->tenant->id;
    }

    protected function ensureDirectory(string $path, string $tenantId): void
    {
        if (! File::isDirectory($path)) {
            try {
                File::makeDirectory($path, 0755, true);
            } catch (\Exception $e) {
                Log::warning("No se pudo crear {$path} para {$tenantId}: ".$e->getMessage());
            }
        }
    }

    protected function ensureFile(string $path, string $content, string $tenantId): void
    {
        if (! File::isFile($path)) {
            try {
                File::put($path, $content);
            } catch (\Exception $e) {
                Log::warning("No se pudo crear {$path} para {$tenantId}: ".$e->getMessage());
            }
        }
    }

    protected function getDefaultCss(): string
    {
        return <<<'CSS'
        /* Tus estilos personalizados aqui */

        CSS;
    }

    protected function getDefaultJs(): string
    {
        return <<<'JS'
        // Tus scripts personalizados aqui

        // Toggle dark/light/system mode
        (function() {
            var t = localStorage.getItem('theme');
            if (t === 'dark') {
                document.documentElement.classList.add('dark');
                document.body.style.backgroundColor = '#070919';
            } else if (t === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                    document.body.style.backgroundColor = '#070919';
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        })();

        JS;
    }

    protected function getNavbarPartial(): string
    {
        return <<<'BLADE'
        <nav class="bg-white dark:bg-gray-800 shadow sticky top-0 z-50 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-5xl mx-auto px-6 flex justify-center items-center h-24 relative">
                <a href="/" class="flex items-center">
                    @if($tenant->logoLightUrl())
                        <img src="{{ $tenant->logoLightUrl() }}" alt="{{ $tenant->name }}" class="h-28 w-auto object-contain block dark:hidden">
                    @endif
                    @if($tenant->logoDarkUrl())
                        <img src="{{ $tenant->logoDarkUrl() }}" alt="{{ $tenant->name }}" class="h-28 w-auto object-contain hidden dark:block">
                    @endif
                </a>
                <button
                    id="theme-toggle-btn"
                    class="tlp-toggle-btn"
                    onclick="var d=document.documentElement,b=document.body,p=this.querySelector('.tlp-toggle-dot'),isDark=d.classList.contains('dark');if(isDark){localStorage.setItem('theme','light');d.classList.remove('dark');b.style.backgroundColor='';p.style.left='5px'}else{localStorage.setItem('theme','dark');d.classList.add('dark');b.style.backgroundColor='#070919';p.style.left='35px'}"
                    style="position:fixed;top:1rem;right:1rem;z-index:60;display:flex;align-items:center;padding:0;border-radius:9999px;width:64px;height:32px;cursor:pointer"
                    aria-label="Cambiar tema"
                >
                    <svg class="tlp-toggle-sun" style="width:16px;height:16px;position:absolute;left:8px;top:8px" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" fill="currentColor"/><g stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none"><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/></g></svg>
                    <svg class="tlp-toggle-moon" style="width:16px;height:16px;position:absolute;right:8px;top:8px" fill="currentColor" viewBox="0 0 24 24"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <span class="tlp-toggle-dot" style="display:block;width:24px;height:24px;border-radius:50%;background:#fff;position:absolute;top:4px;left:5px;transition:left 0.2s;box-shadow:0 1px 3px rgba(0,0,0,.2)"></span>
                </button>
                <style>
                    .tlp-toggle-btn { background: #e5e7eb; border: 1px solid #d1d5db }
                    .tlp-toggle-sun { color: #d97706 }
                    .tlp-toggle-moon { color: #9ca3af }
                    .dark .tlp-toggle-btn { background: #374151; border-color: #4b5563 }
                    .dark .tlp-toggle-sun { color: #fbbf24 }
                    .dark .tlp-toggle-moon { color: #e2e8f0 }
                </style>
                <script>
                    (function(){
                        var b = document.getElementById('theme-toggle-btn');
                        if(!b) return;
                        var p = b.querySelector('.tlp-toggle-dot');
                        if(!p) return;
                        var t = localStorage.getItem('theme');
                        var isDark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
                        p.style.left = isDark ? '35px' : '5px';
                    })();
                </script>
            </div>
        </nav>
        BLADE;
    }

    protected function getFooterPartial(): string
    {
        return <<<'BLADE'
        <footer class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm border-t border-gray-200 dark:border-gray-700">
            &copy; {{ date('Y') }} {{ $tenant->name }}. Todos los derechos reservados.
        </footer>
        BLADE;
    }

    protected function getHeroPartial(): string
    {
        return <<<'BLADE'
        <section class="text-center">
            <video src="{{ asset('videos/seccion1_light.mp4') }}" autoplay loop muted playsinline class="mx-auto mb-6 max-w-full h-auto max-h-64 block dark:hidden"></video>
            <video src="{{ asset('videos/seccion1_dark.mp4') }}" autoplay loop muted playsinline class="mx-auto mb-6 max-w-full h-auto max-h-64 hidden dark:block"></video>
            <h1 class="text-4xl font-bold mb-4">
                Bienvenido a Yanantin
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 mb-4 max-w-3xl mx-auto leading-relaxed">
                Plataforma integral para la colaboracion empresarial. Yanantin unifica productividad,
                comunicacion y bienestar para tu organizacion. Centraliza la gestion del talento humano,
                la colaboracion y las operaciones diarias, tanto en entornos presenciales como remotos.
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mb-8">
                ¿Quieres personalizar esta pagina con tu marca?
                <a href="mailto:contacto@cahilt.com" class="text-amber-600 dark:text-amber-400 hover:underline font-medium">contacto@cahilt.com</a>
            </p>
            <a href="/admin"
               class="inline-block bg-amber-500 dark:bg-amber-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-amber-600 dark:hover:bg-amber-500 transition">
                Ir al Panel Admin
            </a>
        </section>
        BLADE;
    }

    protected function getLandingMain(string $slug): string
    {
        return <<<BLADE
        <!DOCTYPE html>
        <html lang="es" class="scroll-smooth">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{{ \$tenant->name }}</title>
            @if(\$tenant->faviconUrl())
                <link rel="icon" href="{{ \$tenant->faviconUrl() }}" type="image/x-icon">
            @endif
            @vite(['resources/css/app.css', 'resources/js/app.js'])
            <link rel="stylesheet" href="{{ asset('tenants/{$slug}/css/styles.css') }}">
        </head>
        <body class="bg-white dark:bg-[#070919] text-gray-900 dark:text-gray-100 min-h-screen">

            @include('tenants.{$slug}.partials.navbar')

            <main class="max-w-5xl mx-auto px-6 py-16">
                @include('tenants.{$slug}.partials.hero')
            </main>

            @include('tenants.{$slug}.partials.footer')

            <script src="{{ asset('tenants/{$slug}/js/scripts.js') }}"></script>
        </body>
        </html>
        BLADE;
    }
}
