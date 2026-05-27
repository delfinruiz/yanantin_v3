<?php

namespace App\Auth\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseNotification;

class VerifyEmail extends BaseNotification
{
    public string $url;

    protected function verificationUrl($notifiable): string
    {
        return $this->url;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }
}
