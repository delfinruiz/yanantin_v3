<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Illuminate\Contracts\Support\Htmlable;

class TenantRequestPasswordReset extends RequestPasswordReset
{
    protected static string $layout = 'filament.components.layout.tenant-simple';

    public function getHeading(): string
    {
        return '';
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }
}
