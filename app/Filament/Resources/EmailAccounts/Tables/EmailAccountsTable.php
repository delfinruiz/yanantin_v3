<?php

namespace App\Filament\Resources\EmailAccounts\Tables;

use App\Models\EmailAccount;
use App\Models\User;
use App\Services\CPanelEmailService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmailAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Correo electronico')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Asignado a')
                    ->placeholder('Sin asignar')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('encrypted_password')->hidden(),
                TextColumn::make('password')
                    ->label('Contrasena')
                    ->formatStateUsing(fn () => '••••••••••••')
                    ->action(
                        Action::make('viewPassword')
                            ->modalHeading('Ver contrasena')
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitAction(false)
                            ->schema(function (EmailAccount $record) {
                                $password = $record->decrypted_password ?? '';

                                return [
                                    TextInput::make('password_view')
                                        ->label('Contrasena')
                                        ->default($password)
                                        ->readOnly()
                                        ->copyable(),
                                ];
                            })
                    ),
                TextColumn::make('quota')
                    ->label('Cuota')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Ilimitado' : $state.' MB'),
                TextColumn::make('used')
                    ->label('Usado')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2).' MB'),
            ])
            ->poll('60s')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'assigned' => 'Asignado',
                        'unassigned' => 'Sin asignar',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'assigned') {
                            $query->whereNotNull('user_id');
                        } elseif ($data['value'] === 'unassigned') {
                            $query->whereNull('user_id');
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (EmailAccount $record, array $data): EmailAccount {
                        $service = app(CPanelEmailService::class);

                        try {
                            if (isset($data['quota']) && (int) $data['quota'] !== (int) $record->quota) {
                                $service->changeQuota($record->email, (int) $data['quota']);
                            }

                            $record->update($data);

                            return $record;

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al actualizar en cPanel')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            throw $e;
                        }
                    }),
                Action::make('changePassword')
                    ->label('Cambiar contrasena')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->schema([
                        TextInput::make('new_password')
                            ->label('Nueva contrasena')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(12)
                            ->confirmed()
                            ->suffixAction(
                                Action::make('generatePassword')
                                    ->icon('heroicon-o-key')
                                    ->action(function (Set $set) {
                                        $password = Str::password(12, true, true, false, false);
                                        $set('new_password', $password);
                                        $set('new_password_confirmation', $password);
                                    })
                            ),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirmar contrasena')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->action(function (EmailAccount $record, array $data) {
                        try {
                            app(CPanelEmailService::class)->changePassword($record->email, $data['new_password']);

                            $hashedPassword = Hash::make($data['new_password']);
                            $record->update([
                                'password' => $hashedPassword,
                                'encrypted_password' => $data['new_password'],
                            ]);

                            if ($record->user_id) {
                                User::where('id', $record->user_id)->update([
                                    'password' => $hashedPassword,
                                ]);

                                Notification::make()
                                    ->title('Contrasena actualizada y sincronizada con el usuario')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Contrasena actualizada')
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al cambiar contrasena en cPanel')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make()
                    ->before(function (EmailAccount $record) {
                        try {
                            app(CPanelEmailService::class)->delete($record->email);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al eliminar en cPanel')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return false;
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
