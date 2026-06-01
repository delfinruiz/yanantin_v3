<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\EmailAccount;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $emailAccountId = $data['email_account_id'] ?? null;
        unset($data['email_account_id']);

        if ($emailAccountId) {
            $emailAccount = EmailAccount::find($emailAccountId);
            if ($emailAccount) {
                $data['email'] = $emailAccount->email;
                $data['password'] = $emailAccount->decrypted_password ?: Str::random(32);
            }
        }

        return DB::transaction(function () use ($data, $emailAccountId) {
            $user = static::getModel()::create($data);

            if ($emailAccountId) {
                $emailAccount = EmailAccount::where('id', $emailAccountId)
                    ->lockForUpdate()
                    ->first();

                if ($emailAccount && ! $emailAccount->user_id) {
                    $emailAccount->update([
                        'user_id' => $user->id,
                        'assigned_at' => now(),
                    ]);
                }
            }

            return $user;
        });
    }
}
