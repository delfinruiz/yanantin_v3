<?php

namespace App\Models;

use Database\Factories\MoodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mood extends Model
{
    /** @use HasFactory<MoodFactory> */
    use HasFactory;

    const CODES = ['sad', 'med_sad', 'neutral', 'med_happy', 'happy'];

    protected $fillable = [
        'user_id',
        'date',
        'mood',
        'score',
        'message',
        'message_model',
        'message_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'message_generated_at' => 'datetime',
        ];
    }

    public static function scoreFor(string $mood): int
    {
        return match ($mood) {
            'happy' => 100,
            'med_happy' => 75,
            'neutral' => 50,
            'med_sad' => 25,
            'sad' => 0,
            default => 50,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
