<?php

namespace App\Filament\Resources\ProductResource\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del producto')
                    ->description('Datos principales del producto.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->placeholder('Nombre del producto'),
                        Textarea::make('description')
                            ->nullable()
                            ->rows(3)
                            ->maxLength(2000)
                            ->placeholder('Descripción opcional'),
                    ])
                    ->columns(1),

                Section::make('Precio e inventario')
                    ->description('Precio en ARS y control de stock.')
                    ->schema([
                        TextInput::make('price')
                            ->label('Precio (ARS)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->placeholder('0,00')
                            ->helperText('Precio en pesos argentinos. Ej: 1500,50')
                            ->formatStateUsing(fn (?int $state): ?string => $state !== null
                                ? number_format($state / 100, 2, ',', '.')
                                : null)
                            ->dehydrateStateUsing(fn (?string $state): ?int => $state !== null
                                ? (int) round(
                                    (float) str_replace(['.', ','], ['', '.'], $state) * 100
                                )
                                : 0),
                        TextInput::make('stock')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->placeholder('0'),
                    ])
                    ->columns(2),

                Section::make('Estado')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Producto activo')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->helperText('Solo los productos activos son visibles.'),
                    ]),
            ]);
    }
}
