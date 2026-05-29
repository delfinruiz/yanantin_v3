<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class FileItem extends Model
{
    use BelongsToTenant;

    protected $table = 'file_manager_items';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'disk',
        'path',
        'name',
        'filename',
        'mime_type',
        'size',
        'is_folder',
    ];

    protected $casts = [
        'is_folder' => 'boolean',
    ];

    /* =========================
     | Relaciones
     ========================= */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sharedWith()
    {
        return $this->belongsToMany(
            User::class,
            'file_manager_shares',
            'file_item_id'
        )->withPivot([
            'permission',
            'requires_ack',
            'ack_code',
            'ack_code_expires_at',
            'ack_completed_at',
        ])->withTimestamps();
    }

    public function shareLinks()
    {
        return $this->hasMany(FileShareLink::class, 'file_item_id');
    }

    /* =========================
     | Scopes
     ========================= */

    public function scopeAccessible($query)
    {
        return $query
            ->where('user_id', Auth::id())
            ->orWhereHas('sharedWith', function ($q) {
                $q->where('users.id', Auth::id());
            });
    }

    /* =========================
     | Permisos
     ========================= */

    public function canEdit(): bool
    {
        if ($this->user_id === Auth::id()) {
            return true;
        }

        return $this->sharedWith()
            ->where('users.id', Auth::id())
            ->wherePivot('permission', 'edit')
            ->exists();
    }

    /* =========================
     | Accessor Permiso visible
     ========================= */

    protected $appends = ['permission_type'];

    public function getPermissionTypeAttribute(): string
    {
        if ($this->user_id === Auth::id()) {
            return 'full';
        }

        $share = $this->sharedWith
            ->firstWhere('id', Auth::id());

        return $share?->pivot?->permission ?? '—';
    }

    public function isShared(): bool
    {
        return $this->sharedWith()->exists();
    }

    public function sharedCount(): int
    {
        return $this->sharedWith()->count();
    }
}
