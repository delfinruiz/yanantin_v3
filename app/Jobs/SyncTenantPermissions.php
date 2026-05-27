<?php

namespace App\Jobs;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SyncTenantPermissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Tenant $tenant,
    ) {}

    public function handle(): void
    {
        tenancy()->initialize($this->tenant);

        $allowedEntities = $this->tenant->allowedEntities();

        $globalRoleIds = DB::table('roles')
            ->where('name', 'super_admin')
            ->whereNull('tenant_id')
            ->pluck('id');

        $globalPermissionIds = DB::table('role_has_permissions')
            ->whereIn('role_id', $globalRoleIds)
            ->pluck('permission_id')
            ->unique()
            ->values();

        if ($globalPermissionIds->isEmpty()) {
            tenancy()->end();

            return;
        }

        $permissions = Permission::whereIn('id', $globalPermissionIds)
            ->whereNull('tenant_id')
            ->pluck('name', 'id');

        $filtered = $permissions->filter(function ($name) use ($allowedEntities) {
            return $this->matchesAllowedEntities($name, $allowedEntities);
        });

        $tenantRole = Role::where('name', 'super_admin')->first();

        if ($tenantRole && $filtered->isNotEmpty()) {
            $tenantRole->syncPermissions($filtered->keys());
        }

        $this->removeDisallowedPermissionsFromAllRoles($allowedEntities);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        tenancy()->end();
    }

    protected function matchesAllowedEntities(string $permissionName, array $allowedEntities): bool
    {
        $parts = explode(':', $permissionName);

        if (count($parts) < 2) {
            return true;
        }

        return in_array(end($parts), $allowedEntities);
    }

    protected function removeDisallowedPermissionsFromAllRoles(array $allowedEntities): void
    {
        $disallowedIds = DB::table('permissions')
            ->whereNull('tenant_id')
            ->get()
            ->reject(fn ($p) => $this->matchesAllowedEntities($p->name, $allowedEntities))
            ->pluck('id');

        if ($disallowedIds->isEmpty()) {
            return;
        }

        $tenantRoleIds = DB::table('roles')
            ->where('tenant_id', $this->tenant->id)
            ->pluck('id');

        if ($tenantRoleIds->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')
            ->whereIn('role_id', $tenantRoleIds)
            ->whereIn('permission_id', $disallowedIds)
            ->delete();
    }
}
