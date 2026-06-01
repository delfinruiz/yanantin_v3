<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'tenant_id', 'department_id', 'is_internal', 'avatar_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, MustVerifyEmail, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_internal' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $table = $builder->getModel()->getTable();

            if (tenancy()->initialized) {
                $builder->where($table.'.tenant_id', tenant()->getTenantKey());
            } else {
                $builder->whereNull($table.'.tenant_id');
            }
        });

        static::creating(function (User $user) {
            if (tenancy()->initialized && ! $user->tenant_id) {
                $user->tenant_id = tenant()->getTenantKey();
            }

            if ($user->is_internal) {
                $user->email_verified_at ??= now();
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatarColumn = config('filament-edit-profile.avatar_column', 'avatar_url');

        if (! $this->$avatarColumn) {
            return null;
        }

        return '/storage/'.ltrim($this->$avatarColumn, '/');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function emailAccount()
    {
        return $this->hasOne(EmailAccount::class, 'user_id');
    }
}
