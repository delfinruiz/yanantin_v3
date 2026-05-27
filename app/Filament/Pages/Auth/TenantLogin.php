<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class TenantLogin extends Login
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

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }
}
