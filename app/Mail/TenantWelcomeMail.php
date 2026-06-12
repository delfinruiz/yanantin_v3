<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $password,
        public string $recipientEmail,
    ) {}

    public function build()
    {
        $companyName = $this->tenant->name ?? config('app.name');
        $logoUrl = $this->tenant->logoLightUrl() ?: asset('/asset/images/logo-light.png');
        $subdomain = $this->tenant->domain_name
            ?? $this->tenant->domains()->first()?->domain
            ?? $this->tenant->id;
        $adminEmail = 'admin@'.$subdomain.'.localhost';
        $loginUrl = 'https://'.$subdomain.'.'.config('tenancy.central_domains')[0] ?? $subdomain.'.localhost';

        return $this
            ->subject('Bienvenido a '.$companyName.' - Credenciales de acceso')
            ->to($this->recipientEmail)
            ->view('emails.tenant-welcome')
            ->with([
                'companyName' => $companyName,
                'logoUrl' => $logoUrl,
                'subdomain' => $subdomain,
                'loginUrl' => $loginUrl,
                'adminEmail' => $adminEmail,
                'password' => $this->password,
            ]);
    }
}
