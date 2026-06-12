<?php

namespace App\Filament\Resources\MeetRoomResource\Pages;

use App\Filament\Resources\MeetRoomResource;
use App\Models\User;
use App\Notifications\MeetRoomInvitationNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditMeetRoom extends EditRecord
{
    protected static string $resource = MeetRoomResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getRedirectUrl(): string
    {
        return MeetRoomResource::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['invitations'] = $this->record->invitations->map(function ($invitation) {
            return [
                'id' => $invitation->id,
                'invitation_type' => $invitation->invitation_type,
                'invitable_id' => $invitation->invitable_id,
                'email' => $invitation->email,
                'name' => $invitation->name,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $invitationsData = $data['invitations'] ?? [];
        unset($data['invitations']);

        $record->update($data);

        $existingInvitationIds = $record->invitations()->pluck('id')->toArray();

        $submittedIds = [];

        foreach ($invitationsData as $invitationData) {
            if (isset($invitationData['id']) && in_array($invitationData['id'], $existingInvitationIds)) {
                $submittedIds[] = $invitationData['id'];

                continue;
            }

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

        $toDelete = array_diff($existingInvitationIds, $submittedIds);

        if (! empty($toDelete)) {
            $record->invitations()->whereIn('id', $toDelete)->delete();
        }

        Notification::make()
            ->title('Sala de reunion actualizada exitosamente')
            ->success()
            ->send();

        return $record;
    }
}
