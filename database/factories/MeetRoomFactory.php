<?php

namespace Database\Factories;

use App\Models\MeetRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetRoom>
 */
class MeetRoomFactory extends Factory
{
    protected $model = MeetRoom::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'room_code' => MeetRoom::generateUniqueCode(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'type' => fake()->randomElement(['unique', 'recurrent']),
            'scheduled_date' => fake()->dateTimeBetween('today', '+30 days')->format('Y-m-d'),
            'scheduled_time' => fake()->time('H:i'),
            'duration_minutes' => fake()->randomElement([30, 60, 90, 120]),
            'status' => 'pending',
            'waiting_room_enabled' => true,
            'allow_chat' => true,
            'allow_screen_share' => true,
            'allow_recording' => false,
            'start_muted_audio' => false,
            'start_muted_video' => false,
            'noise_suppression_enabled' => true,
        ];
    }

    public function unique(): static
    {
        return $this->state(fn () => ['type' => 'unique']);
    }

    public function recurrent(): static
    {
        return $this->state(fn () => ['type' => 'recurrent']);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function today(): static
    {
        return $this->state(fn () => ['scheduled_date' => today()->format('Y-m-d')]);
    }

    public function withLobby(): static
    {
        return $this->state(fn () => ['lobby_enabled' => true]);
    }

    public function withPassword(string $password = 'secret'): static
    {
        return $this->state(fn () => [
            'require_password' => true,
            'password' => $password,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}
