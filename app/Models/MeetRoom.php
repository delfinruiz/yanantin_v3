<?php

namespace App\Models;

use Database\Factories\MeetRoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'room_code',
    'name',
    'description',
    'type',
    'scheduled_date',
    'scheduled_time',
    'duration_minutes',
    'status',
    'waiting_room_enabled',
    'waiting_room_video_url',
    'waiting_room_message',
    'allow_chat',
    'allow_screen_share',
    'allow_recording',
    'require_password',
    'password',
    'max_participants',
    'start_muted_audio',
    'start_muted_video',
    'lobby_enabled',
    'break_out_rooms_enabled',
    'whiteboard_enabled',
    'subtitles_enabled',
    'noise_suppression_enabled',
    'redirect_url_on_leave',
    'custom_background_url',
    'accepting_participants',
])]
class MeetRoom extends Model
{
    /** @use HasFactory<MeetRoomFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'waiting_room_enabled' => 'boolean',
            'allow_chat' => 'boolean',
            'allow_screen_share' => 'boolean',
            'allow_recording' => 'boolean',
            'require_password' => 'boolean',
            'start_muted_audio' => 'boolean',
            'start_muted_video' => 'boolean',
            'lobby_enabled' => 'boolean',
            'break_out_rooms_enabled' => 'boolean',
            'whiteboard_enabled' => 'boolean',
            'subtitles_enabled' => 'boolean',
            'noise_suppression_enabled' => 'boolean',
            'max_participants' => 'integer',
            'duration_minutes' => 'integer',
            'password' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MeetRoom $room) {
            if (empty($room->room_code)) {
                $room->room_code = static::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtolower(Str::random(3)).'-'.strtolower(Str::random(3)).'-'.rand(100, 999);
        } while (static::where('room_code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(MeetRoomInvitation::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetRoomParticipant::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MeetRoomLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('scheduled_date', today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('type', 'recurrent')
                ->orWhere(function (Builder $q2) {
                    $q2->where('type', 'unique')
                        ->where(function (Builder $q3) {
                            $q3->whereDate('scheduled_date', '>', today())
                                ->orWhere(function (Builder $q4) {
                                    $q4->whereDate('scheduled_date', today())
                                        ->whereTime('scheduled_time', '>', now()->format('H:i:s'));
                                });
                        });
                });
        });
    }

    public function scopeWhereOwner(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWhereInvited(Builder $query, int $userId): Builder
    {
        return $query->whereHas('invitations', function (Builder $q) use ($userId) {
            $q->where('invitable_type', User::class)
                ->where('invitable_id', $userId);
        });
    }

    public function scopeAccessibleBy(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhereHas('invitations', function (Builder $q2) use ($userId) {
                    $q2->where('invitable_type', User::class)
                        ->where('invitable_id', $userId)
                        ->whereIn('status', ['pending', 'accepted', 'attended']);
                });
        });
    }

    public function getIsScheduledNowAttribute(): bool
    {
        if ($this->type === 'recurrent') {
            return true;
        }

        $scheduledDateTime = $this->scheduled_date->format('Y-m-d').' '.$this->scheduled_time;
        $endTime = now()->parse($scheduledDateTime)->addMinutes($this->duration_minutes);

        return now()->between(
            now()->parse($scheduledDateTime)->subMinutes(15),
            $endTime
        );
    }

    public function getCanJoinAttribute(): bool
    {
        if ($this->status === 'cancelled') {
            return false;
        }

        if ($this->type === 'recurrent') {
            return true;
        }

        return $this->is_scheduled_now;
    }

    public function moderatorIsConnected(): bool
    {
        return $this->participants()
            ->where('is_moderator', true)
            ->whereNull('left_at')
            ->where('updated_at', '>', now()->subSeconds(25))
            ->exists();
    }

    public function getIsPastAttribute(): bool
    {
        if ($this->type === 'recurrent') {
            return false;
        }

        $scheduledDateTime = $this->scheduled_date->format('Y-m-d').' '.$this->scheduled_time;
        $endTime = now()->parse($scheduledDateTime)->addMinutes($this->duration_minutes);

        return now()->gt($endTime);
    }

    public function isOwner(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->user_id === $user->id;
    }

    public function isRecurrent(): bool
    {
        return $this->type === 'recurrent';
    }

    public function isInvited(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->invitations()
            ->where('invitable_type', User::class)
            ->where('invitable_id', $user->id)
            ->whereIn('status', ['pending', 'accepted', 'attended'])
            ->exists();
    }

    public function canAccess(?User $user): bool
    {
        return $this->isOwner($user) || $this->isInvited($user);
    }

    public function getJoinUrl(?string $token = null): string
    {
        $url = route('meet.join', ['roomCode' => $this->room_code]);

        if ($token) {
            $url .= '?token='.$token;
        }

        return $url;
    }

    public function inviteInternalUser(User $user): MeetRoomInvitation
    {
        $invitation = $this->invitations()->updateOrCreate(
            [
                'invitable_type' => User::class,
                'invitable_id' => $user->id,
            ],
            [
                'email' => $user->email,
                'name' => $user->name,
                'token' => Str::random(60),
                'invitation_type' => 'internal',
                'status' => 'pending',
            ]
        );

        return $invitation;
    }

    public function inviteExternalUser(string $email, ?string $name = null): MeetRoomInvitation
    {
        return $this->invitations()->create([
            'email' => $email,
            'name' => $name,
            'token' => Str::random(60),
            'invitation_type' => 'external',
            'status' => 'pending',
        ]);
    }

    public static function pendingCountForUser(int $userId): int
    {
        return static::where(function (Builder $query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhereHas('invitations', function (Builder $q) use ($userId) {
                    $q->where('invitable_type', User::class)
                        ->where('invitable_id', $userId)
                        ->whereIn('status', ['pending', 'accepted']);
                });
        })
            ->where('status', 'pending')
            ->where('type', 'unique')
            ->where(function (Builder $query) {
                $query->whereDate('scheduled_date', '>', today())
                    ->orWhere(function (Builder $q) {
                        $q->whereDate('scheduled_date', today())
                            ->whereTime('scheduled_time', '>', now()->format('H:i:s'));
                    });
            })
            ->count();
    }
}
