<?php

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use App\Mail\TenantWelcomeMail;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resendCredentials')
                ->label('Reenviar credenciales')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reenviar credenciales')
                ->modalDescription('Se generara una nueva contraseña y se enviara al correo del administrador.')
                ->action(function () {
                    $recipientEmail = $this->record->getRawOriginal('admin_email');

                    if (! $recipientEmail) {
                        Notification::make()
                            ->title('Sin correo registrado')
                            ->body('Este tenant no tiene un correo de administrador configurado.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $password = Str::password(16);

                    $rootDomain = config('services.cpanel.root_domain') ?: parse_url(config('app.url'), PHP_URL_HOST);
                    $slug = $this->record->domain_name
                        ?? $this->record->domains()->first()?->domain
                        ?? $this->record->id;
                    $newAdminEmail = 'admin@'.$slug.'.'.$rootDomain;

                    tenancy()->initialize($this->record);

                    $user = User::withoutGlobalScope('tenant')
                        ->where('tenant_id', $this->record->id)
                        ->where(function ($q) use ($newAdminEmail, $slug) {
                            $q->where('email', $newAdminEmail)
                                ->orWhere('email', 'admin@'.$slug.'.localhost');
                        })
                        ->first();

                    if ($user) {
                        $user->update([
                            'email' => $newAdminEmail,
                            'password' => Hash::make($password),
                        ]);
                    }

                    tenancy()->end();

                    Mail::send(new TenantWelcomeMail(
                        tenant: $this->record,
                        password: $password,
                        recipientEmail: $recipientEmail,
                    ));

                    Notification::make()
                        ->title('Credenciales reenviadas')
                        ->body('Se ha enviado un correo con las nuevas credenciales al administrador.')
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['favicon_url'] = $this->toDiskPath($data['favicon_url'] ?? null);
        $data['logo_light_url'] = $this->toDiskPath($data['logo_light_url'] ?? null);
        $data['logo_dark_url'] = $this->toDiskPath($data['logo_dark_url'] ?? null);

        $storedAdminEmail = $this->record->getRawOriginal('admin_email');
        if ($storedAdminEmail !== null) {
            $data['admin_email'] = $storedAdminEmail;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['favicon_url'] = $this->extractFilePath($data['favicon_url'] ?? null);
        $data['logo_light_url'] = $this->extractFilePath($data['logo_light_url'] ?? null);
        $data['logo_dark_url'] = $this->extractFilePath($data['logo_dark_url'] ?? null);

        return $data;
    }

    protected function toDiskPath(?string $value): ?string
    {
        if (! $value || ! is_string($value)) {
            return null;
        }

        $value = str_replace(Storage::disk('public')->url(''), '', $value);

        if (str_starts_with($value, '/storage/')) {
            $value = substr($value, 9);
        }

        return ltrim($value, '/');
    }

    protected function extractFilePath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[array_key_first($value)] ?? null;
        }

        if (! $value || ! is_string($value)) {
            return null;
        }

        return $value;
    }
}
