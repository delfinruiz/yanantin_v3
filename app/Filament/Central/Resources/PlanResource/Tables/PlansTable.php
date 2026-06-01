<?php

namespace App\Filament\Central\Resources\PlanResource\Tables;

use App\Models\Plan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

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

                TextColumn::make('max_users')
                    ->label('Max. usuarios')
                    ->state(fn (Plan $record): string => $record->max_users ? (string) $record->max_users : 'Ilimitado')
                    ->badge()
                    ->color(fn (Plan $record): string => $record->max_users ? 'warning' : 'success'),

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
                DeleteAction::make()
                    ->visible(fn (Plan $record): bool => $record->tenants()->count() === 0)
                    ->tooltip(fn (Plan $record): ?string => $record->tenants()->count() > 0
                        ? 'No se puede eliminar: tiene suscriptores activos'
                        : null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $records = $records->filter(fn (Plan $record): bool => $record->tenants()->count() === 0);

                            $records->each->delete();
                        }),
                ]),
            ]);
    }
}
