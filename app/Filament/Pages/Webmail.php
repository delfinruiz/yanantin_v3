<?php

namespace App\Filament\Pages;

use App\Models\EmailAccount;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Config;

class Webmail extends Page
{
    protected static ?string $slug = 'webmail';

    protected static ?string $title = 'Webmail';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.webmail';

    public static function canAccess(): bool
    {
        return EmailAccount::where('user_id', auth()->id())
            ->whereNotNull('encrypted_password')
            ->exists();
    }

    public function mount(): void
    {
        $account = EmailAccount::where('user_id', auth()->id())->first();

        if (! $account || ! $account->decrypted_password) {
            $this->redirect(static::getUrl());

            return;
        }

        $domain = $account->domain ?? substr(strrchr($account->email, '@'), 1);
        $host = tenant()?->cpanel_host ?: Config::get('cpanel.host') ?: $domain;

        $url = "https://{$host}:2096/login?user={$account->email}&pass={$account->decrypted_password}";

        $this->redirect($url);
    }
}
