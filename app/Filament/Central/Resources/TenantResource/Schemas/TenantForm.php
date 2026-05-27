<?php

namespace App\Filament\Central\Resources\TenantResource\Schemas;

use App\Models\Plan;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                            ->placeholder('Mi Empresa S.A.'),
                        TextInput::make('domain')
                            ->label('Subdominio')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->placeholder('mi-empresa')
                            ->helperText('El tenant sera accesible en mi-empresa.localhost')
                            ->visibleOn('create'),
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
}
