<?php

namespace App\Filament\Resources\MeetRoomResource\Pages;

use App\Filament\Resources\MeetRoomResource;
use App\Models\User;
use App\Notifications\MeetRoomInvitationNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateMeetRoom extends CreateRecord
{
    protected static string $resource = MeetRoomResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['user_id'] = Auth::id();
        $invitationsData = $data['invitations'] ?? [];
        unset($data['invitations']);

        $record = static::getModel()::create($data);

        foreach ($invitationsData as $invitationData) {
            if ($invitationData['invitation_type'] === 'internal' && ! empty($invitationData['invitable_id'])) {
                $user = User::find($invitationData['invitable_id']);
                if ($user) {
                    $invitation = $record->inviteInternalUser($user);
                    $user->notify(new MeetRoomInvitationNotification($record, $invitation));
                }
            } elseif ($invitationData['invitation_type'] === 'external' && ! empty($invitationData['email'])) {
                $record->inviteExternalUser(
                    $invitationData['email'],
                    $invitationData['name'] ?? null
                );
            }
        }

        Notification::make()
            ->title('Sala de reunion creada exitosamente')
            ->success()
            ->send();

        return $record;
    }
}
