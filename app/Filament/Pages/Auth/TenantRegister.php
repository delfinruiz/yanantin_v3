<?php

namespace App\Filament\Pages\Auth;

use App\Auth\Notifications\VerifyEmail;
use App\Models\Role;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class TenantRegister extends Register
{
    protected static string $layout = 'filament.components.layout.tenant-simple';

    public function getHeading(): string
    {
        return '';
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $limit = tenant()->maxUsers();

        if ($limit && User::count() >= $limit) {
            Notification::make()
                ->title('Registro no disponible')
                ->body('Se ha alcanzado el limite de usuarios de este servicio.')
                ->danger()
                ->send();

            $this->halt();
        }

        return array_merge($data, ['is_internal' => false]);
    }

    public function register(): ?RegistrationResponse
    {
        $limit = tenant()->maxUsers();

        if ($limit && User::count() >= $limit) {
            return null;
        }

        return parent::register();
    }

    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        $publicRole = Role::where('name', 'Público')
            ->where('guard_name', 'web')
            ->first();

        if ($publicRole) {
            $user->assignRole($publicRole);
        }

        return $user;
    }

    protected function sendEmailVerificationNotification(Model $user): void
    {
        if (! $user instanceof MustVerifyEmail) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new \LogicException("Model [{$userClass}] does not have a [notify()] method.");
        }

        $notification = app(VerifyEmail::class);
        $notification->url = Filament::getVerifyEmailUrl($user);

        $user->notify($notification);
    }
}
