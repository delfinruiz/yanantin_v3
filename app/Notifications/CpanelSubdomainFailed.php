<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CpanelSubdomainFailed extends Notification
{
    use Queueable;

    public function __construct(
        protected string $tenantId,
        protected string $subdomain,
        protected string $action,
        protected string $error,
    ) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(): array
    {
        return [
            'title' => "cPanel: Error al {$this->action} subdominio",
            'body' => "No se pudo {$this->action} el subdominio {$this->subdomain} para el tenant {$this->tenantId}. Revisar logs.",
            'tenant_id' => $this->tenantId,
            'subdomain' => $this->subdomain,
            'action' => $this->action,
            'error' => $this->error,
        ];
    }
}
