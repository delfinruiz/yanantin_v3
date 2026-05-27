<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        SpatieRole::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => null,
        ]);

        SpatieRole::firstOrCreate([
            'name' => 'Público',
            'guard_name' => 'web',
            'tenant_id' => null,
        ]);
    }
}
