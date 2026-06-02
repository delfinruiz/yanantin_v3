<?php

namespace App\Livewire\Webmail;

use App\Models\EmailAccount;
use App\Services\ImapService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WebmailBadgePoll extends Component
{
    public $badgeContent = null;

    public $shouldShow = false;

    public $webmailUrl = '#';

    public function mount(): void
    {
        $this->updateBadge();
    }

    public function updateBadge(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->badgeContent = null;
            $this->shouldShow = false;
            $this->webmailUrl = '#';

            return;
        }

        $account = EmailAccount::where('user_id', $user->id)->first();
        if (! $account || empty($account->encrypted_password)) {
            $this->badgeContent = null;
            $this->shouldShow = false;
            $this->webmailUrl = '#';

            return;
        }

        $this->shouldShow = true;

        try {
            $count = app(ImapService::class)->unreadCount($account);
            $this->badgeContent = $count > 0 ? (string) $count : null;
        } catch (\Throwable) {
            $this->badgeContent = null;
        }

        $password = $account->decrypted_password;
        if ($password) {
            $domain = $account->domain ?? substr(strrchr($account->email, '@'), 1);
            $host = tenant()?->cpanel_host ?: config('cpanel.host') ?: $domain;
            $this->webmailUrl = "https://{$host}:2096/login?user={$account->email}&pass={$password}";
        } else {
            $this->webmailUrl = '#';
        }
    }

    public function render()
    {
        return view('livewire.webmail.webmail-badge-poll');
    }
}
