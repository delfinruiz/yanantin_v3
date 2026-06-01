<?php

namespace App\Filament\Resources\EmailAccounts\Pages;

use App\Filament\Resources\EmailAccounts\EmailAccountResource;
use App\Models\EmailAccount;
use App\Services\CPanelEmailService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class ListEmailAccounts extends ListRecords
{
    protected static string $resource = EmailAccountResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function mount(): void
    {
        parent::mount();
        $this->syncEmails(true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sincronizar')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->syncEmails()),
            CreateAction::make()
                ->using(function (array $data, string $model): Model {
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

                        return $model::create($modelData);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al crear en cPanel')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        throw $e;
                    }
                }),
        ];
    }

    public function syncEmails(bool $silent = false): void
    {
        try {
            $service = app(CPanelEmailService::class);
            $emails = $service->list();

            $apiEmailsList = [];

            foreach ($emails as $emailData) {
                if (is_string($emailData)) {
                    $emailData = ['email' => $emailData];
                }

                $fullEmail = $emailData['email'] ?? null;

                if (! $fullEmail) {
                    continue;
                }

                if (! str_contains($fullEmail, '@') && isset($emailData['domain'])) {
                    $fullEmail .= '@'.$emailData['domain'];
                }

                $apiEmailsList[] = $fullEmail;

                $usedMb = 0;
                if (isset($emailData['diskused'])) {
                    $usedMb = (float) $emailData['diskused'];
                } elseif (isset($emailData['_diskused'])) {
                    $usedMb = round((float) $emailData['_diskused'] / 1024 / 1024, 2);
                }

                $quota = 0;
                if (isset($emailData['diskquota']) && $emailData['diskquota'] !== 'unlimited') {
                    $quota = (int) $emailData['diskquota'];
                } elseif (isset($emailData['quota']) && $emailData['quota'] !== 'unlimited') {
                    $quota = (int) $emailData['quota'];
                }

                EmailAccount::updateOrCreate(
                    ['email' => $fullEmail],
                    [
                        'domain' => $emailData['domain'] ?? substr(strrchr($fullEmail, '@'), 1),
                        'quota' => $quota,
                        'used' => $usedMb,
                    ]
                );
            }

            EmailAccount::whereNotIn('email', $apiEmailsList)->delete();

            if (! $silent) {
                Notification::make()
                    ->title('Sincronizacion completada')
                    ->success()
                    ->send();

                $this->resetTable();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error de sincronizacion')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
