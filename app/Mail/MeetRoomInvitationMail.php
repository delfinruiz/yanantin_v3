<?php

namespace App\Mail;

use App\Models\MeetRoom;
use App\Models\MeetRoomInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetRoomInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MeetRoom $meetRoom,
        public MeetRoomInvitation $invitation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitacion a reunion: '.$this->meetRoom->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.meet.invitation',
            with: [
                'joinUrl' => $this->invitation->getJoinUrl(),
                'roomCode' => $this->meetRoom->room_code,
                'roomName' => $this->meetRoom->name,
                'roomDescription' => $this->meetRoom->description,
                'scheduledDate' => $this->meetRoom->scheduled_date->format('d/m/Y'),
                'scheduledTime' => $this->meetRoom->scheduled_time->format('H:i'),
                'organizerName' => $this->meetRoom->user->name,
                'organizerEmail' => $this->meetRoom->user->email,
                'guestName' => $this->invitation->name,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
