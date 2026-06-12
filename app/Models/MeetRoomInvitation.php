<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

#[Fillable([
    'meet_room_id',
    'invitable_type',
    'invitable_id',
    'email',
    'name',
    'token',
    'invitation_type',
    'status',
    'email_sent_at',
    'joined_at',
    'left_at',
])]
class MeetRoomInvitation extends Model
{
    protected function casts(): array
    {
        return [
            'email_sent_at' => 'datetime',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MeetRoomInvitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(60);
            }
        });
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(MeetRoom::class, 'meet_room_id');
    }

    public function invitable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): MorphTo
    {
        return $this->invitable();
    }

    public function isInternal(): bool
    {
        return $this->invitation_type === 'internal';
    }

    public function isExternal(): bool
    {
        return $this->invitation_type === 'external';
    }

    public function getJoinUrl(): string
    {
        return route('meet.join', [
            'roomCode' => $this->room->room_code,
            'token' => $this->token,
        ]);
    }

    public function getDeclineUrl(): string
    {
        return route('meet.decline', [
            'roomCode' => $this->room->room_code,
            'token' => $this->token,
        ]);
    }

    public function markAsAccepted(): void
    {
        $this->update([
            'status' => 'accepted',
            'joined_at' => now(),
        ]);
    }

    public function markAsAttended(): void
    {
        $this->update([
            'status' => 'attended',
        ]);
    }

    public function markAsDeclined(): void
    {
        $this->update([
            'status' => 'declined',
        ]);
    }

    public function markEmailSent(): void
    {
        $this->update([
            'email_sent_at' => now(),
        ]);
    }

    public function markLeft(): void
    {
        $this->update([
            'left_at' => now(),
        ]);
    }
}
