<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTenantRoles extends Command
{
    protected $signature = 'tenants:migrate-roles';

    protected $description = 'Clone global roles (tenant_id=NULL) into each existing tenant';

    public function handle(): int
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No hay tenants para migrar.');

            return self::SUCCESS;
        }

        $globalRoles = DB::table('roles')->whereNull('tenant_id')->get();

        if ($globalRoles->isEmpty()) {
            $this->warn('No hay roles globales para migrar.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $this->info("Procesando tenant: {$tenant->id}");

            tenancy()->initialize($tenant);

            foreach ($globalRoles as $globalRole) {
                $existing = Role::where('name', $globalRole->name)
                    ->where('guard_name', $globalRole->guard_name)
                    ->first();

                if ($existing) {
                    $this->line("  Rol '{$globalRole->name}' ya existe, saltando.");

                    continue;
                }

                $tenantRole = Role::create([
                    'name' => $globalRole->name,
                    'guard_name' => $globalRole->guard_name,
                ]);

                $permissionIds = DB::table('role_has_permissions')
                    ->where('role_id', $globalRole->id)
                    ->pluck('permission_id');

                if ($permissionIds->isNotEmpty()) {
                    $tenantRole->syncPermissions($permissionIds->toArray());
                }

                DB::table('model_has_roles')
                    ->where('role_id', $globalRole->id)
                    ->where('model_type', (new User)->getMorphClass())
                    ->whereIn('model_id', function ($q) use ($tenant) {
                        $q->select('id')
                            ->from('users')
                            ->where('tenant_id', $tenant->id);
                    })
                    ->update(['role_id' => $tenantRole->id]);

                $this->line("  Rol '{$globalRole->name}' migrado (ID: {$tenantRole->id})");

                $permissionIds = DB::table('role_has_permissions')
                    ->where('role_id', $globalRole->id)
                    ->pluck('permission_id');

                if ($permissionIds->isNotEmpty()) {
                    $tenantRole->syncPermissions($permissionIds->toArray());
                }

                DB::table('model_has_roles')
                    ->where('role_id', $globalRole->id)
                    ->where('model_type', (new User)->getMorphClass())
                    ->whereIn('model_id', function ($q) use ($tenant) {
                        $q->select('id')
                            ->from('users')
                            ->where('tenant_id', $tenant->id);
                    })
                    ->update(['role_id' => $tenantRole->id]);

                $this->line("  Rol '{$globalRole->name}' migrado (ID: {$tenantRole->id})");
            }

            tenancy()->end();
        }

        $this->info('Migracion completada.');

        return self::SUCCESS;
    }
}
