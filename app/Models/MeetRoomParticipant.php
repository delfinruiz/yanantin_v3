<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meet_room_id',
    'user_id',
    'participant_id',
    'display_name',
    'email',
    'joined_at',
    'left_at',
    'is_moderator',
    'jitsi_connected',
    'is_muted_audio',
    'is_muted_video',
    'ip_address',
    'user_agent',
])]
class MeetRoomParticipant extends Model
{
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'is_moderator' => 'boolean',
            'is_muted_audio' => 'boolean',
            'is_muted_video' => 'boolean',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(MeetRoom::class, 'meet_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCurrentlyInRoom(Builder $query): Builder
    {
        return $query->whereNull('left_at');
    }

    public function scopeModerators(Builder $query): Builder
    {
        return $query->where('is_moderator', true);
    }

    public function isCurrentlyInRoom(): bool
    {
        return is_null($this->left_at);
    }

    public function markLeft(): void
    {
        $this->update(['left_at' => now()]);
    }

    public function getDurationAttribute(): ?int
    {
        if (! $this->left_at) {
            return null;
        }

        return $this->joined_at->diffInMinutes($this->left_at);
    }
}
