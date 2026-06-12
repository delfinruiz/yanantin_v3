<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meet_room_id',
    'user_id',
    'action',
    'description',
    'metadata',
    'ip_address',
    'created_at',
])]
class MeetRoomLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
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

    public static function log(
        MeetRoom $room,
        string $action,
        ?User $user = null,
        ?string $description = null,
        ?array $metadata = null,
    ): static {
        return static::create([
            'meet_room_id' => $room->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
