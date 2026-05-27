<?php

namespace App\Filament\Central\Resources\TenantResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('domains.domain')
                    ->label('Dominio')
                    ->badge()
                    ->sortable(),

                SelectColumn::make('status')
                    ->label('Estado')
                    ->options([
                        'activa' => 'Activa',
                        'pausada' => 'Pausada',
                        'suspendida' => 'Suspendida',
                    ])
                    ->selectablePlaceholder(false)
                    ->sortable(),

                TextColumn::make('status_changed_at')
                    ->label('Cambio de estado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('admin_email')
                    ->label('Admin')
                    ->copyable()
                    ->placeholder('pendiente...'),

                TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'gray',
                        'basic' => 'info',
                        'pro' => 'success',
                        'enterprise' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'free' => 'Gratuito',
                        'basic' => 'Basico',
                        'pro' => 'Profesional',
                        'enterprise' => 'Enterprise',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('landing_dir_error')
                    ->label('Landing')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('warning')
                    ->tooltip(fn ($state) => $state)
                    ->visible(fn ($state) => filled($state)),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
