<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = config('plans.plans', []);

        foreach ($plans as $slug => $data) {
            Plan::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'features' => $data['features'],
                    'max_users' => $data['max_users'] ?? null,
                    'is_active' => true,
                    'sort' => array_search($slug, array_keys($plans)),
                ]
            );
        }
    }
}
