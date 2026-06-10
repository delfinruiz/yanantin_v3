<?php

namespace App\Filament\Pages;

use App\Services\CPanelEmailService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class EditProfilePage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $slug = 'edit-profile';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Editar perfil';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Editar perfil';

    protected string $view = 'filament.pages.edit-profile';

    public ?array $data = [];

    public array $sessions = [];

    public ?string $sessionPassword = null;

    public function mount(): void
    {
        $this->form->fill([
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'avatar_url' => auth()->user()->avatar_url,
            'currency' => auth()->user()->currency,
        ]);

        $this->sessions = $this->getSessions();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Información del perfil')
                    ->description('Actualiza la informacion del perfil de tu cuenta.')
                    ->aside()
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Foto')
                            ->avatar()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('avatars'),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Correo electronico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->readOnly(fn () => auth()->user()?->is_internal)
                            ->unique(ignoreRecord: true),
                        Select::make('currency')
                            ->label('Moneda')
                            ->placeholder('Seleccionar moneda')
                            ->options([
                                'ARS' => 'ARS - Peso argentino',
                                'BOB' => 'BOB - Boliviano',
                                'BRL' => 'BRL - Real brasileño',
                                'CAD' => 'CAD - Dólar canadiense',
                                'CLP' => 'CLP - Peso chileno',
                                'COP' => 'COP - Peso colombiano',
                                'CRC' => 'CRC - Colón costarricense',
                                'DOP' => 'DOP - Peso dominicano',
                                'EUR' => 'EUR - Euro',
                                'GBP' => 'GBP - Libra esterlina',
                                'GTQ' => 'GTQ - Quetzal guatemalteco',
                                'HNL' => 'HNL - Lempira hondureño',
                                'MXN' => 'MXN - Peso mexicano',
                                'NIO' => 'NIO - Córdoba nicaragüense',
                                'NOK' => 'NOK - Corona noruega',
                                'PAB' => 'PAB - Balboa panameño',
                                'PEN' => 'PEN - Sol peruano',
                                'PYG' => 'PYG - Guaraní paraguayo',
                                'SEK' => 'SEK - Corona sueca',
                                'CHF' => 'CHF - Franco suizo',
                                'USD' => 'USD - Dólar estadounidense',
                                'UYU' => 'UYU - Peso uruguayo',
                                'VES' => 'VES - Bolívar venezolano',
                            ])
                            ->searchable()
                            ->native(false),
                    ]),

                Section::make('Actualizar contraseña')
                    ->description('Asegurate de usar una contraseña larga y segura.')
                    ->aside()
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Contraseña actual')
                            ->password()
                            ->revealable()
                            ->nullable(),
                        TextInput::make('password')
                            ->label('Nueva contraseña')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->minLength(12)
                            ->same('passwordConfirmation')
                            ->helperText('Debe tener al menos 12 caracteres, incluyendo mayúsculas, minúsculas y números.')
                            ->suffixAction(
                                Action::make('generatePassword')
                                    ->icon('heroicon-o-key')
                                    ->label('Generar')
                                    ->action(function (Set $set) {
                                        $password = Str::password(12, true, true, false, false);
                                        $set('password', $password);
                                        $set('passwordConfirmation', $password);
                                    })
                            ),
                        TextInput::make('passwordConfirmation')
                            ->label('Confirmar nueva contraseña')
                            ->password()
                            ->revealable()
                            ->nullable(),
                    ]),

                Section::make('Eliminar cuenta')
                    ->description('Elimina permanentemente tu cuenta y todos tus datos.')
                    ->aside()
                    ->visible(fn () => ! auth()->user()?->is_internal)
                    ->schema([]),
            ]);
    }

    protected function getSessions(): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table('sessions')
            ->where('user_id', auth()->id())
            ->latest('last_activity')
            ->get()
            ->map(function ($session) {
                $agent = tap(new Agent, fn ($agent) => $agent->setUserAgent($session->user_agent));

                return [
                    'id' => $session->id,
                    'ip' => $session->ip_address,
                    'device' => $agent->platform() ?: 'Desconocido',
                    'browser' => $agent->browser() ?: 'Desconocido',
                    'is_current' => $session->id === session()->getId(),
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                ];
            })
            ->toArray();
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();

        if (! empty($data['current_password']) && ! empty($data['password'])) {
            if (! Hash::check($data['current_password'], $user->password)) {
                Notification::make()
                    ->title('La contraseña actual no es correcta.')
                    ->danger()
                    ->send();

                $this->form->fill([
                    ...$data,
                    'current_password' => null,
                    'password' => null,
                    'passwordConfirmation' => null,
                ]);

                return;
            }

            $emailAccount = $user->emailAccount;

            if ($emailAccount) {
                try {
                    app(CPanelEmailService::class)->changePassword(
                        $emailAccount->email,
                        $data['password']
                    );

                    $emailAccount->update([
                        'password' => Hash::make($data['password']),
                        'encrypted_password' => $data['password'],
                    ]);
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error al cambiar la contraseña de la cuenta de correo')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    $this->form->fill([
                        ...$data,
                        'current_password' => null,
                        'password' => null,
                        'passwordConfirmation' => null,
                    ]);

                    return;
                }
            }

            $user->password = Hash::make($data['password']);
        }

        $user->name = $data['name'];

        if (! $user->is_internal && isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (isset($data['avatar_url'])) {
            $user->avatar_url = $data['avatar_url'];
        }

        $user->currency = $data['currency'];

        $user->save();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'currency' => $user->currency,
            'current_password' => null,
            'password' => null,
            'passwordConfirmation' => null,
        ]);

        Notification::make()
            ->title('Perfil actualizado correctamente.')
            ->success()
            ->send();
    }

    public function logoutOtherBrowserSessions(): void
    {
        if (! Hash::check($this->sessionPassword, auth()->user()->password)) {
            Notification::make()
                ->title('La contraseña no es correcta.')
                ->danger()
                ->send();

            return;
        }

        auth()->logoutOtherDevices($this->sessionPassword);

        $this->sessionPassword = null;

        $this->sessions = $this->getSessions();

        Notification::make()
            ->title('Se cerraron las otras sesiones.')
            ->success()
            ->send();
    }

    public function deleteAccount(): void
    {
        $user = auth()->user();

        Filament::auth()->logout();

        $user->delete();

        redirect(Filament::getUrl());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deleteAccount')
                ->label('Eliminar cuenta')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->visible(fn () => ! auth()->user()?->is_internal)
                ->requiresConfirmation()
                ->modalHeading('Eliminar cuenta')
                ->modalDescription('Una vez eliminada tu cuenta, todos tus recursos y datos seran borrados permanentemente. Antes de borrar tu cuenta, por favor descarga cualquier dato o informacion que desees conservar.')
                ->modalSubmitActionLabel('Eliminar cuenta')
                ->action(fn () => $this->deleteAccount()),
        ];
    }
}
