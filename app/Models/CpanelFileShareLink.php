<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CpanelFileShareLink extends Model
{
    protected $table = 'cpanel_file_share_links';

    protected $fillable = [
        'tenant_id',
        'owner_id',
        'token',
        'path',
        'name',
        'permission',
        'expires_at',
        'downloads',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'downloads' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function isValid(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
