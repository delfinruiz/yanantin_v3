<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\EmailAccount;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn ($record) => $record?->hasRole('super_admin')),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $emailAccountId = $data['email_account_id'] ?? null;
        unset($data['email_account_id']);

        return DB::transaction(function () use ($record, $data, $emailAccountId) {
            $currentAccount = EmailAccount::where('user_id', $record->id)->first();

            if ($currentAccount && $currentAccount->id !== $emailAccountId) {
                $currentAccount->update([
                    'user_id' => null,
                    'assigned_at' => null,
                ]);
            }

            if ($emailAccountId) {
                $newAccount = EmailAccount::where('id', $emailAccountId)
                    ->lockForUpdate()
                    ->first();

                if ($newAccount && ! $newAccount->user_id) {
                    $newAccount->update([
                        'user_id' => $record->id,
                        'assigned_at' => now(),
                    ]);
                    $data['email'] = $newAccount->email;

                    if ($newAccount->decrypted_password) {
                        $data['password'] = $newAccount->decrypted_password;
                    }
                }
            } elseif ($currentAccount && ! ($data['is_internal'] ?? true)) {
                $currentAccount->update([
                    'user_id' => null,
                    'assigned_at' => null,
                ]);
            }

            $record->update($data);

            return $record;
        });
    }
}
