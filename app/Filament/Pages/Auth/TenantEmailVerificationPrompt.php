<?php

namespace App\Filament\Pages\Auth;

use App\Auth\Notifications\VerifyEmail;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Support\Htmlable;

class TenantEmailVerificationPrompt extends EmailVerificationPrompt
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

    protected function sendEmailVerificationNotification(MustVerifyEmail $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new \LogicException("Model [{$userClass}] does not have a [notify()] method.");
        }

        $notification = app(VerifyEmail::class);
        $notification->url = Filament::getVerifyEmailUrl($user);

        $user->notify($notification);
    }
}
