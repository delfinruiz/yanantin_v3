<?php

use App\Models\User;
use App\Services\AiMessageService;

beforeEach(function () {
    $this->user = User::factory()->make([
        'name' => 'Test User',
        'is_internal' => true,
    ]);

    $this->service = new AiMessageService;
});

it('returns fallback message when no api key', function () {
    $message = $this->service->generateDailyMessage($this->user, 'happy', 100);

    expect($message)->toBeString()->not->toBeEmpty();
});

it('returns fallback message for sad mood', function () {
    $message = $this->service->generateDailyMessage($this->user, 'sad', 0);

    expect($message)->toBeString()->not->toBeEmpty();
});

it('returns fallback suggestions when no api key', function () {
    $suggestions = $this->service->generateOrganizationalSuggestions(['happy' => 5, 'neutral' => 3], 75);

    expect($suggestions)->toBeString()->not->toBeEmpty();
});

it('returns different messages for different moods', function () {
    $happyMessage = $this->service->generateDailyMessage($this->user, 'happy', 100);
    $sadMessage = $this->service->generateDailyMessage($this->user, 'sad', 0);

    expect($happyMessage)->not->toBe($sadMessage);
});

it('handles empty distribution gracefully', function () {
    $suggestions = $this->service->generateOrganizationalSuggestions([], 0);

    expect($suggestions)->toBeString()->not->toBeEmpty();
});
