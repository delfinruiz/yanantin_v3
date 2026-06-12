<?php

use App\Models\MeetRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class)
    ->beforeEach(function () {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
    })
    ->in(__FILE__);

test('un usuario puede crear una sala de reunion', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $room = MeetRoom::factory()->forUser($user)->create([
        'name' => 'Reunion de prueba',
        'type' => 'unique',
        'scheduled_date' => today()->addDays(1),
        'scheduled_time' => '10:00',
    ]);

    expect($room->name)->toBe('Reunion de prueba')
        ->and($room->user_id)->toBe($user->id)
        ->and($room->room_code)->not->toBeEmpty();
});

test('una sala genera un codigo unico automaticamente', function () {
    $room = MeetRoom::factory()->create();

    expect($room->room_code)->toMatch('/^[a-z]{3}-[a-z]{3}-\d{3}$/');
});

test('el dueño puede acceder a su sala', function () {
    $user = User::factory()->create();
    $room = MeetRoom::factory()->forUser($user)->create();

    expect($room->isOwner($user))->toBeTrue()
        ->and($room->canAccess($user))->toBeTrue();
});

test('un usuario invitado puede acceder a la sala', function () {
    $owner = User::factory()->create();
    $guest = User::factory()->create();
    $room = MeetRoom::factory()->forUser($owner)->create();

    $room->inviteInternalUser($guest);

    expect($room->isInvited($guest))->toBeTrue()
        ->and($room->canAccess($guest))->toBeTrue();
});

test('un usuario no invitado no puede acceder a la sala', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $room = MeetRoom::factory()->forUser($owner)->create();

    expect($room->isOwner($stranger))->toBeFalse()
        ->and($room->isInvited($stranger))->toBeFalse()
        ->and($room->canAccess($stranger))->toBeFalse();
});

test('se puede invitar a un usuario interno', function () {
    $owner = User::factory()->create();
    $guest = User::factory()->create();
    $room = MeetRoom::factory()->forUser($owner)->create();

    $invitation = $room->inviteInternalUser($guest);

    expect($invitation->invitation_type)->toBe('internal')
        ->and($invitation->email)->toBe($guest->email)
        ->and($invitation->token)->not->toBeEmpty();
});

test('se puede invitar a un usuario externo', function () {
    $owner = User::factory()->create();
    $room = MeetRoom::factory()->forUser($owner)->create();

    $invitation = $room->inviteExternalUser('externo@example.com', 'Juan Externo');

    expect($invitation->invitation_type)->toBe('external')
        ->and($invitation->email)->toBe('externo@example.com')
        ->and($invitation->name)->toBe('Juan Externo');
});

test('la url de union es correcta', function () {
    $owner = User::factory()->create();
    $room = MeetRoom::factory()->forUser($owner)->create();

    $url = $room->getJoinUrl();

    expect($url)->toContain('/meet/')
        ->and($url)->toContain($room->room_code);
});

test('el conteo de reuniones pendientes funciona', function () {
    $user = User::factory()->create();

    MeetRoom::factory()->forUser($user)->count(3)->create([
        'type' => 'unique',
        'status' => 'pending',
        'scheduled_date' => today()->addDays(1),
        'scheduled_time' => '10:00',
    ]);

    MeetRoom::factory()->forUser($user)->create([
        'status' => 'completed',
        'scheduled_date' => today()->subDays(1),
    ]);

    $count = MeetRoom::pendingCountForUser($user->id);

    expect($count)->toBe(3);
});

test('una sala recurrente siempre puede unirse', function () {
    $user = User::factory()->create();
    $room = MeetRoom::factory()->forUser($user)->recurrent()->create([
        'scheduled_date' => today()->subDays(30),
    ]);

    expect($room->can_join)->toBeTrue();
});

test('el codigo de sala es unico', function () {
    $room1 = MeetRoom::factory()->create();
    $room2 = MeetRoom::factory()->create();

    expect($room1->room_code)->not->toBe($room2->room_code);
});
