<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HappinessSuggestion extends Model
{
    protected $fillable = [
        'date',
        'requested_by',
        'suggestion',
        'context',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'context' => 'array',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
