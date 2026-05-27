<?php

namespace App\Filament\Central\Resources\PlanResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->searchable(),

                TextColumn::make('features')
                    ->label('Modulos')
                    ->formatStateUsing(fn ($state) => collect($state)
                        ->map(fn ($f) => config("plans.features.$f.label", $f))
                        ->implode(', ')),

                TextColumn::make('max_users')
                    ->label('Max. usuarios')
                    ->formatStateUsing(fn ($state) => $state ?? 'Ilimitado')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'success'),

                ToggleColumn::make('is_active')
                    ->label('Activo'),

                TextColumn::make('sort')
                    ->label('Orden')
                    ->sortable(),

                TextColumn::make('tenants_count')
                    ->label('Suscripciones')
                    ->counts('tenants')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime(),
            ])
            ->defaultSort('sort')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
