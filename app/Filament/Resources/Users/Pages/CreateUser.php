<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeCreate(): void
    {
        $limit = tenant()->maxUsers();

        if ($limit && User::count() >= $limit) {
            Notification::make()
                ->title('Limite de usuarios alcanzado')
                ->body("Has alcanzado el limite de {$limit} usuarios de tu plan. Contacta al administrador para ampliar tu plan.")
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
