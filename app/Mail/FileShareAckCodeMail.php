<?php

namespace App\Mail;

use App\Models\FileItem;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FileShareAckCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FileItem $file,
        public string $code,
        public ?Carbon $expiresAt,
        public string $senderName,
    ) {}

    public function build()
    {
        $companyName = tenant()?->name ?? config('app.name');
        $logoUrl = tenant()?->logoLightUrl() ?: asset('/asset/images/logo-light.png');

        return $this
            ->subject(__('FileManager_Mail_Subject_Ack'))
            ->view('emails.file-share-ack')
            ->with([
                'file' => $this->file,
                'code' => $this->code,
                'expiresAt' => $this->expiresAt,
                'senderName' => $this->senderName,
                'companyName' => $companyName,
                'logoUrl' => $logoUrl,
            ]);
    }
}
