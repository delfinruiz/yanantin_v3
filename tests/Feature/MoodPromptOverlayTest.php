<?php

use App\Livewire\MoodPromptOverlay;
use App\Models\Mood;
use App\Models\User;
use Livewire\Livewire;

it('shows overlay for internal user without mood today', function () {
    $user = User::factory()->create([
        'is_internal' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(MoodPromptOverlay::class)
        ->assertSet('showOverlay', true);
});

it('hides overlay for external user', function () {
    $user = User::factory()->create([
        'is_internal' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(MoodPromptOverlay::class)
        ->assertSet('showOverlay', false);
});

it('hides overlay when user already submitted mood today', function () {
    $user = User::factory()->create([
        'is_internal' => true,
    ]);

    Mood::create([
        'user_id' => $user->id,
        'date' => today(),
        'mood' => 'happy',
        'score' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(MoodPromptOverlay::class)
        ->assertSet('showOverlay', false);
});

it('saves mood on selection', function () {
    $user = User::factory()->create([
        'is_internal' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(MoodPromptOverlay::class)
        ->call('selectMood', 'happy')
        ->assertSet('showOverlay', false);

    $this->assertDatabaseHas('moods', [
        'user_id' => $user->id,
        'mood' => 'happy',
        'score' => 100,
    ]);
});

it('ignores invalid mood', function () {
    $user = User::factory()->create([
        'is_internal' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(MoodPromptOverlay::class)
        ->call('selectMood', 'invalid');

    $this->assertDatabaseCount('moods', 0);
});

it('can dismiss overlay', function () {
    $user = User::factory()->create([
        'is_internal' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(MoodPromptOverlay::class)
        ->call('dismiss')
        ->assertSet('showOverlay', false);
});
