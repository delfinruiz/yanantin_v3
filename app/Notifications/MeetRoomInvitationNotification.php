<?php

namespace App\Notifications;

use App\Models\MeetRoom;
use App\Models\MeetRoomInvitation;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetRoomInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MeetRoom $meetRoom,
        public MeetRoomInvitation $invitation,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Invitacion a reunion: '.$this->meetRoom->name)
            ->greeting('Hola '.$notifiable->name.'!')
            ->line('Has sido invitado a una reunion de videoconferencia.')
            ->line('**Reunion:** '.$this->meetRoom->name);

        if ($this->meetRoom->scheduled_date && $this->meetRoom->scheduled_time) {
            $message->line('**Fecha:** '.$this->meetRoom->scheduled_date->format('d/m/Y'));
            $message->line('**Hora:** '.$this->meetRoom->scheduled_time->format('H:i'));
        } else {
            $message->line('**Tipo:** Sala recurrente - Disponible en cualquier momento');
        }

        $message->line('**Organizador:** '.$this->meetRoom->user->name)
            ->action('Unirse a la reunion', $this->invitation->getJoinUrl())
            ->line('Codigo de sala: '.$this->meetRoom->room_code)
            ->line('Gracias por usar nuestra plataforma!')
            ->line('Si no puedes asistir, [rechaza la invitacion aqui]('.$this->invitation->getDeclineUrl().').');

        return $message;
    }

    public function toDatabase(object $notifiable): array
    {
        $body = $this->meetRoom->name;

        if ($this->meetRoom->scheduled_date && $this->meetRoom->scheduled_time) {
            $body .= ' - '.$this->meetRoom->scheduled_date->format('d/m/Y').' a las '.$this->meetRoom->scheduled_time->format('H:i');
        } else {
            $body .= ' - Sala recurrente';
        }

        return FilamentNotification::make()
            ->title('Has sido invitado a una reunion')
            ->body($body)
            ->icon('heroicon-o-video-camera')
            ->color('primary')
            ->actions([
                Action::make('join')
                    ->label('Unirse')
                    ->url($this->invitation->getJoinUrl())
                    ->button(),
                Action::make('decline')
                    ->label('Rechazar')
                    ->url($this->invitation->getDeclineUrl())
                    ->color('danger')
                    ->link(),
                Action::make('view')
                    ->label('Ver detalles'),
            ])
            ->getDatabaseMessage();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'meet_room_id' => $this->meetRoom->id,
            'invitation_id' => $this->invitation->id,
            'room_code' => $this->meetRoom->room_code,
            'room_name' => $this->meetRoom->name,
            'scheduled_date' => $this->meetRoom->scheduled_date?->format('Y-m-d'),
            'scheduled_time' => $this->meetRoom->scheduled_time?->format('H:i:s'),
            'organizer_name' => $this->meetRoom->user->name,
            'join_url' => $this->invitation->getJoinUrl(),
        ];
    }
}
