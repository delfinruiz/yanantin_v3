<?php

namespace App\Filament\Resources\EmailAccounts\Pages;

use App\Filament\Resources\EmailAccounts\EmailAccountResource;
use App\Services\CPanelEmailService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEmailAccount extends EditRecord
{
    protected static string $resource = EmailAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (Model $record) {
                    try {
                        app(CPanelEmailService::class)->delete($record->email);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al eliminar en cPanel')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return false;
                    }
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $service = app(CPanelEmailService::class);

        try {
            if (! empty($data['password'])) {
                $service->changePassword($record->email, $data['password']);
            }

            if (isset($data['quota']) && $data['quota'] != $record->quota) {
                $service->changeQuota($record->email, (int) $data['quota']);
            }

            unset($data['password']);

            $record->update($data);

            return $record;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al actualizar en cPanel')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();

            return $record;
        }
    }
}
