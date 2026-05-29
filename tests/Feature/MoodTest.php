<?php

use App\Models\Mood;
use App\Models\User;
use Illuminate\Database\QueryException;

it('can create a mood entry', function () {
    $user = User::factory()->create();

    $mood = Mood::create([
        'user_id' => $user->id,
        'date' => today(),
        'mood' => 'happy',
        'score' => Mood::scoreFor('happy'),
    ]);

    expect($mood)->toBeInstanceOf(Mood::class)
        ->and($mood->mood)->toBe('happy')
        ->and($mood->score)->toBe(100);
});

it('prevents duplicate mood per day', function () {
    $user = User::factory()->create();

    Mood::create([
        'user_id' => $user->id,
        'date' => today(),
        'mood' => 'happy',
        'score' => Mood::scoreFor('happy'),
    ]);

    expect(fn () => Mood::create([
        'user_id' => $user->id,
        'date' => today(),
        'mood' => 'sad',
        'score' => Mood::scoreFor('sad'),
    ]))->toThrow(QueryException::class);
});

it('calculates correct score for all moods', function () {
    expect(Mood::scoreFor('happy'))->toBe(100)
        ->and(Mood::scoreFor('med_happy'))->toBe(75)
        ->and(Mood::scoreFor('neutral'))->toBe(50)
        ->and(Mood::scoreFor('med_sad'))->toBe(25)
        ->and(Mood::scoreFor('sad'))->toBe(0);
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $mood = Mood::factory()->create(['user_id' => $user->id]);

    expect($mood->user)->toBeInstanceOf(User::class)
        ->and($mood->user->id)->toBe($user->id);
});

it('has correct codes constant', function () {
    expect(Mood::CODES)->toBe(['sad', 'med_sad', 'neutral', 'med_happy', 'happy']);
});
