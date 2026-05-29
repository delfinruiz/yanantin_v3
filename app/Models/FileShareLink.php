<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileShareLink extends Model
{
    protected $table = 'file_manager_links';

    protected $fillable = [
        'file_item_id',
        'token',
        'permission',
        'expires_at',
        'password',
        'downloads',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'downloads' => 'integer',
    ];

    /* =========================
     | Relaciones
     ========================= */

    public function fileItem(): BelongsTo
    {
        return $this->belongsTo(FileItem::class, 'file_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* =========================
     | Metodos
     ========================= */

    public function isValid(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
