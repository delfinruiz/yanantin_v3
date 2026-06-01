<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class EmailAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'email',
        'username',
        'password',
        'domain',
        'quota',
        'used',
        'user_id',
        'assigned_at',
        'encrypted_password',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setEncryptedPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['encrypted_password'] = null;

            return;
        }

        $this->attributes['encrypted_password'] = Crypt::encryptString($value);
    }

    public function getDecryptedPasswordAttribute(): ?string
    {
        $val = $this->attributes['encrypted_password'] ?? null;
        if (! $val) {
            return null;
        }

        for ($i = 0; $i < 3; $i++) {
            try {
                $val = Crypt::decryptString($val);
            } catch (\Throwable $e) {
                break;
            }
        }

        return is_string($val) ? $val : null;
    }

    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'used' => 'float',
            'assigned_at' => 'datetime',
        ];
    }
}
