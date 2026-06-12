<?php

namespace App\Filament\Central\Resources\TenantResource\Schemas;

use App\Models\Plan;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Stancl\Tenancy\Database\Models\Domain;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informacion del tenant')
                    ->description('Datos del tenant y su dominio.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Mi Empresa S.A.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get) {
                                if (blank($state)) {
                                    return;
                                }

                                if (filled($get('domain'))) {
                                    return;
                                }

                                $set('domain', self::slugifySubdomain($state));
                            }),
                        TextInput::make('domain')
                            ->label('Subdominio')
                            ->required()
                            ->maxLength(63)
                            ->live(onBlur: true)
                            ->unique('domains', 'domain', ignoreRecord: true)
                            ->rule('regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/')
                            ->placeholder('mi-empresa')
                            ->helperText('El tenant sera accesible en mi-empresa.app.cahilt.com')
                            ->visibleOn('create')
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                if (blank($state)) {
                                    $set('domain_checked', false);
                                    $set('domain_available', false);

                                    return;
                                }

                                $domain = strtolower(trim($state));

                                if (! preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $domain)) {
                                    $set('domain_checked', false);
                                    $set('domain_available', false);

                                    return;
                                }

                                $exists = Domain::where('domain', $domain)->exists();
                                $set('domain_checked', true);
                                $set('domain_available', ! $exists);
                            })
                            ->suffixIcon(fn (Get $get) => $get('domain_checked')
                                ? ($get('domain_available') ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                : null)
                            ->suffixIconColor(fn (Get $get) => $get('domain_checked')
                                ? ($get('domain_available') ? 'success' : 'danger')
                                : null),

                        Hidden::make('domain_checked')->default(false),
                        Hidden::make('domain_available')->default(false),
                        TextInput::make('admin_email')
                            ->label('Correo del administrador')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('admin@ejemplo.com')
                            ->helperText('A este correo se enviaran las credenciales de acceso al tenant.')
                            ->visibleOn(['create', 'edit']),

                        Select::make('plan_id')
                            ->label('Plan')
                            ->options(fn () => Plan::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->default(fn () => Plan::where('slug', config('plans.default', 'free'))->first()?->id),

                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'activa' => 'Activa',
                                'pausada' => 'Pausada',
                                'suspendida' => 'Suspendida',
                            ])
                            ->required()
                            ->visibleOn('edit'),
                    ])
                    ->columns(3),

                Section::make('Branding')
                    ->description('Favicon, logos e imagen de fondo del login.')
                    ->schema([
                        FileUpload::make('favicon_url')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('32')
                            ->imageResizeTargetHeight('32')
                            ->directory('tenants/branding')
                            ->maxSize(1024)
                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'])
                            ->required(fn (string $operation): bool => $operation === 'create'),

                        FileUpload::make('logo_light_url')
                            ->label('Logo (modo claro)')
                            ->image()
                            ->disk('public')
                            ->directory('tenants/branding')
                            ->maxSize(2048)
                            ->required(fn (string $operation): bool => $operation === 'create'),

                        FileUpload::make('logo_dark_url')
                            ->label('Logo (modo oscuro)')
                            ->image()
                            ->disk('public')
                            ->directory('tenants/branding')
                            ->maxSize(2048)
                            ->required(fn (string $operation): bool => $operation === 'create'),

                        FileUpload::make('login_background_image')
                            ->label('Fondo del login')
                            ->image()
                            ->disk('public')
                            ->directory('tenants/branding')
                            ->maxSize(5120)
                            ->helperText('Imagen de fondo para la pagina de inicio de sesion. Se recomienda 1920x1080px.'),
                    ])
                    ->columns(4),
            ]);
    }

    private static function slugifySubdomain(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return substr($text, 0, 63);
    }
}
