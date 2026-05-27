<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $table = $builder->getModel()->getTable();

            if (tenancy()->initialized) {
                $builder->where($table.'.tenant_id', tenant()->getTenantKey());
            } else {
                $builder->whereNull($table.'.tenant_id');
            }
        });

        static::creating(function (self $role) {
            if (tenancy()->initialized && ! $role->tenant_id) {
                $role->tenant_id = tenant()->getTenantKey();
            }
        });
    }
}
