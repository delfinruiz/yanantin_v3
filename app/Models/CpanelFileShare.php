<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CpanelFileShare extends Model
{
    protected $table = 'cpanel_file_shares';

    protected $fillable = [
        'tenant_id',
        'owner_id',
        'user_id',
        'path',
        'name',
        'size',
        'mtime',
        'permission',
        'requires_ack',
        'ack_code',
        'ack_code_expires_at',
        'ack_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_ack' => 'boolean',
            'ack_code_expires_at' => 'datetime',
            'ack_completed_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
