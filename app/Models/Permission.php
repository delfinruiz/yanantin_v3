<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission as BasePermission;

class Permission extends BasePermission
{
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $table = $builder->getModel()->getTable();

            if (tenancy()->initialized) {
                $builder->where(function (Builder $q) use ($table) {
                    $q->where($table.'.tenant_id', tenant()->getTenantKey())
                        ->orWhereNull($table.'.tenant_id');
                });
            } else {
                $builder->whereNull($table.'.tenant_id');
            }
        });

        static::creating(function (self $permission) {
            if (tenancy()->initialized && ! $permission->tenant_id) {
                $permission->tenant_id = tenant()->getTenantKey();
            }
        });

        static::deleting(function (self $permission) {
            if (tenancy()->initialized && $permission->tenant_id === null) {
                throw new \RuntimeException('No se puede eliminar un permiso global desde un tenant.');
            }
        });
    }
}
