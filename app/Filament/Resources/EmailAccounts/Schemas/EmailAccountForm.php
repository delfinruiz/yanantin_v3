<?php

namespace App\Filament\Resources\EmailAccounts\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EmailAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        $domain = tenant()?->cpanel_host ?: config('cpanel.host');

        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Correo electronico')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit')
                    ->helperText('Solo la parte local del correo (sin @dominio)')
                    ->suffix($domain ? '@'.$domain : null)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Set $set) use ($domain) {
                        if ($state && $domain && str_ends_with($state, '@'.$domain)) {
                            $set('email', substr($state, 0, -strlen('@'.$domain)));
                        }
                    })
                    ->formatStateUsing(function ($state) use ($domain) {
                        if ($state && $domain && str_ends_with($state, '@'.$domain)) {
                            return substr($state, 0, -strlen('@'.$domain));
                        }

                        return $state;
                    })
                    ->dehydrateStateUsing(function ($state) use ($domain) {
                        if (! $state || ! $domain) {
                            return $state;
                        }

                        if (str_ends_with($state, '@'.$domain)) {
                            return $state;
                        }

                        if (str_contains($state, '@')) {
                            $local = strstr($state, '@', true);

                            return $local.'@'.$domain;
                        }

                        return $state.'@'.$domain;
                    }),

                TextInput::make('password')
                    ->label('Contrasena')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(12)
                    ->hiddenOn('edit')
                    ->suffixAction(
                        Action::make('generatePassword')
                            ->icon('heroicon-o-key')
                            ->label('Generar')
                            ->action(function (Set $set) {
                                $password = Str::password(12, true, true, false, false);
                                $set('password', $password);
                            })
                    ),

                TextInput::make('quota')
                    ->label('Cuota (MB)')
                    ->numeric()
                    ->default(250)
                    ->helperText('0 para ilimitado')
                    ->required(),
            ]);
    }
}
