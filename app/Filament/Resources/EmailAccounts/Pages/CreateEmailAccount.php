<?php

namespace App\Filament\Resources\EmailAccounts\Pages;

use App\Filament\Resources\EmailAccounts\EmailAccountResource;
use App\Services\CPanelEmailService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateEmailAccount extends CreateRecord
{
    protected static string $resource = EmailAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $service = app(CPanelEmailService::class);

        try {
            $service->create($data['email'], $data['password'], (int) $data['quota']);

            $domain = substr(strrchr($data['email'], '@'), 1);
            $username = substr($data['email'], 0, strrpos($data['email'], '@'));

            $modelData = [
                'email' => $data['email'],
                'username' => $username,
                'password' => Hash::make($data['password']),
                'encrypted_password' => $data['password'],
                'domain' => $domain,
                'quota' => $data['quota'],
                'used' => 0,
            ];

            return static::getModel()::create($modelData);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al crear en cPanel')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();

            return new (static::getModel());
        }
    }
}
