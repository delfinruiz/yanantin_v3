<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $id = fake()->unique()->slug(1);

        return [
            'id' => $id,
            'name' => fake()->company(),
            'plan' => fake()->randomElement(['free', 'basic', 'pro', 'enterprise']),
        ];
    }
}
