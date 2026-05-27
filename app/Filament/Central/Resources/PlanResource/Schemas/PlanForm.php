<?php

namespace App\Filament\Central\Resources\PlanResource\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Informacion del plan')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Identificador')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->helperText('Identificador unico usado internamente.'),
                    ])
                    ->columnSpan(1),

                Section::make('Configuracion')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),

                        TextInput::make('sort')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),

                        TextInput::make('max_users')
                            ->label('Max. usuarios')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Limite total de usuarios (internos + publicos) del tenant. Vacio = ilimitado.'),
                    ])
                    ->columnSpan(1),

                Section::make('Modulos')
                    ->description('Selecciona los modulos que incluye este plan.')
                    ->schema([
                        CheckboxList::make('features')
                            ->label('Modulos disponibles')
                            ->options(
                                collect(config('plans.features'))
                                    ->mapWithKeys(fn ($feature, $key) => [$key => $feature['label']])
                                    ->toArray()
                            )
                            ->required()
                            ->columns(3),
                    ])
                    ->columnSpanFull(),

                Section::make('Descripcion')
                    ->schema([
                        Textarea::make('description')
                            ->label('Descripcion')
                            ->rows(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
