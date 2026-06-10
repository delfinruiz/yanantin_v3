<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CpanelFileShareAckCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fileName,
        public string $code,
        public ?Carbon $expiresAt,
        public string $senderName,
    ) {}

    public function build()
    {
        $companyName = tenant()?->name ?? config('app.name');
        $logoUrl = tenant()?->logoLightUrl() ?: asset('/asset/images/logo-light.png');

        return $this
            ->subject(__('CpanelFileManager_Mail_Subject_Ack'))
            ->view('emails.cpanel-file-share-ack')
            ->with([
                'fileName' => $this->fileName,
                'code' => $this->code,
                'expiresAt' => $this->expiresAt,
                'senderName' => $this->senderName,
                'companyName' => $companyName,
                'logoUrl' => $logoUrl,
            ]);
    }
}
