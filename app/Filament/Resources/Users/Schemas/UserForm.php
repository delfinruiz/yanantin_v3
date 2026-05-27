<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Correo electronico')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label('Contrasena')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->visibleOn('create')
                    ->afterStateHydrated(fn ($component, $state) => null)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),

                Select::make('department_id')
                    ->label('Departamento')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->hidden(fn () => ! in_array('Department', tenant()->allowedEntities())),

                Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->options(fn () => Role::where('guard_name', 'web')->pluck('name', 'id')),

                Toggle::make('is_internal')
                    ->label('Usuario interno')
                    ->helperText('Los usuarios internos son parte de la organizacion. Los externos tienen acceso limitado.')
                    ->default(true),
            ]);
    }
}
