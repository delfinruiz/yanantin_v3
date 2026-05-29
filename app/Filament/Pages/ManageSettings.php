<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    use HasPageShield {
        canAccess as protected shieldCanAccess;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Configuracion';

    protected static ?int $navigationSort = 999;

    protected string $view = 'filament.pages.manage-settings';

    public static function canAccess(): bool
    {
        if (! tenant()?->hasEntity(class_basename(static::class))) {
            return false;
        }

        return static::shieldCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public ?array $data = [];

    public function mount(): void
    {
        $tenant = tenant();

        $this->form->fill([
            'token_ai' => $tenant?->token_ai,
            'timezone' => $tenant?->timezone ?: config('app.timezone'),
            'logo_light_url' => $tenant?->logo_light_url,
            'logo_dark_url' => $tenant?->logo_dark_url,
            'favicon_url' => $tenant?->favicon_url,
            'cpanel_host' => $tenant?->cpanel_host,
            'cpanel_user' => $tenant?->cpanel_user,
            'cpanel_token' => $tenant?->cpanel_token,
            'zadarma_token' => $tenant?->zadarma_token,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Zona Horaria')
                    ->description('Define la zona horaria del tenant. Todos los modulos que requieran sincronizacion horaria usaran esta configuracion.')
                    ->schema([
                        Select::make('timezone')
                            ->label('Zona Horaria')
                            ->searchable()
                            ->options(fn () => collect(\DateTimeZone::listIdentifiers())
                                ->mapWithKeys(fn (string $tz) => [$tz => $tz])
                                ->toArray()
                            )
                            ->helperText('Selecciona la zona horaria para este tenant.'),
                    ])
                    ->columns(1),

                Section::make('Apariencia')
                    ->description('Personaliza los elementos visuales del tenant.')
                    ->schema([
                        FileUpload::make('logo_light_url')
                            ->label('Logo (modo claro)')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('tenants/branding')
                            ->maxSize(1024)
                            ->helperText('Imagen para el modo claro. Recomendado: formato PNG con fondo transparente.'),
                        FileUpload::make('logo_dark_url')
                            ->label('Logo (modo oscuro)')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('tenants/branding')
                            ->maxSize(1024)
                            ->helperText('Imagen para el modo oscuro. Recomendado: PNG con texto claro.'),
                        FileUpload::make('favicon_url')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('tenants/branding')
                            ->maxSize(256)
                            ->helperText('Icono del sitio. Recomendado: 32x32 o 64x64 PNG.'),
                    ])
                    ->columns(3),

                Section::make('Configuracion de OpenAI')
                    ->description('Clave de API para la generacion de mensajes y sugerencias de IA.')
                    ->schema([
                        TextInput::make('token_ai')
                            ->label('Token de OpenAI')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->placeholder('sk-...')
                            ->helperText('Se usa para generar mensajes personalizados y sugerencias organizacionales en el modulo de Felicidad Organizacional.'),
                    ])
                    ->columns(1),

                Section::make('Cpanel')
                    ->description('Credenciales para la integracion con Cpanel.')
                    ->schema([
                        TextInput::make('cpanel_host')
                            ->label('Host Cpanel')
                            ->maxLength(255)
                            ->placeholder('https://cpanel.tudominio.com:2083')
                            ->helperText('URL del servidor Cpanel.'),
                        TextInput::make('cpanel_user')
                            ->label('Usuario Cpanel')
                            ->maxLength(255)
                            ->helperText('Nombre de usuario para acceder a Cpanel.'),
                        TextInput::make('cpanel_token')
                            ->label('Token Cpanel')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('Token de API de Cpanel.'),
                    ])
                    ->columns(2),

                Section::make('Zadarma')
                    ->description('Configuracion para la integracion con Zadarma VoIP.')
                    ->schema([
                        TextInput::make('zadarma_token')
                            ->label('Token Zadarma')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('Token de API de Zadarma para integracion de telefonia.'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $tenant = tenant();

        if ($tenant) {
            $tenant->token_ai = $data['token_ai'] ?? null;
            $tenant->timezone = $data['timezone'] ?? config('app.timezone');
            $tenant->logo_light_url = $data['logo_light_url'] ?? null;
            $tenant->logo_dark_url = $data['logo_dark_url'] ?? null;
            $tenant->favicon_url = $data['favicon_url'] ?? null;
            $tenant->cpanel_host = $data['cpanel_host'] ?? null;
            $tenant->cpanel_user = $data['cpanel_user'] ?? null;
            $tenant->cpanel_token = $data['cpanel_token'] ?? null;
            $tenant->zadarma_token = $data['zadarma_token'] ?? null;

            $tenant->save();
        }

        Notification::make()
            ->title('Configuracion guardada exitosamente')
            ->success()
            ->send();
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }
}
