<?php

namespace Database\Factories;

use App\Models\Mood;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MoodFactory extends Factory
{
    protected $model = Mood::class;

    public function definition(): array
    {
        $mood = fake()->randomElement(Mood::CODES);

        return [
            'user_id' => User::factory(),
            'date' => fake()->date(),
            'mood' => $mood,
            'score' => Mood::scoreFor($mood),
        ];
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => today(),
        ]);
    }

    public function happy(): static
    {
        return $this->state(fn (array $attributes) => [
            'mood' => 'happy',
            'score' => 100,
        ]);
    }

    public function sad(): static
    {
        return $this->state(fn (array $attributes) => [
            'mood' => 'sad',
            'score' => 0,
        ]);
    }
}
