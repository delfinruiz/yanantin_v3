<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\EmailAccount;
use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                    ->unique(ignoreRecord: true)
                    ->readonly(fn (Get $get): bool => $get('is_internal'))
                    ->disabled(fn ($record): bool => $record?->hasRole('super_admin')),

                Select::make('email_account_id')
                    ->label('Cuenta de correo')
                    ->options(fn ($record) => EmailAccount::where(function ($q) use ($record) {
                        $q->whereNull('user_id');

                        if ($record) {
                            $q->orWhere('id', $record->emailAccount?->id)
                                ->orWhere('email', $record->email);
                        }
                    })
                        ->pluck('email', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->hidden(fn () => ! in_array('EmailAccount', tenant()->allowedEntities()))
                    ->visible(fn (Get $get): bool => $get('is_internal'))
                    ->disabled(fn ($record): bool => $record?->hasRole('super_admin'))
                    ->helperText('Selecciona una cuenta de correo disponible. El email y la contrasena se asignaran automaticamente.')
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state) {
                            $email = EmailAccount::find($state)?->email;
                            if ($email) {
                                $set('email', $email);
                            }
                        }
                    })
                    ->afterStateHydrated(function ($component, $record) {
                        if (! $record) {
                            return;
                        }

                        $id = $record->emailAccount?->id
                            ?? EmailAccount::where('email', $record->email)->value('id');

                        if ($id) {
                            $component->state($id);
                        }
                    }),

                TextInput::make('password')
                    ->label('Contrasena')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->visibleOn('create')
                    ->hidden(fn (Get $get): bool => filled($get('email_account_id')))
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
                    ->options(fn () => Role::where('guard_name', 'web')->pluck('name', 'id'))
                    ->disabled(fn ($record): bool => $record?->hasRole('super_admin')),

                Toggle::make('is_internal')
                    ->label('Usuario interno')
                    ->helperText('Los usuarios internos son parte de la organizacion. Los externos tienen acceso limitado.')
                    ->live()
                    ->default(true)
                    ->disabled(fn ($record): bool => $record?->hasRole('super_admin')),
            ]);
    }
}
