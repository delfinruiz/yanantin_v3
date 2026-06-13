<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetRoomResource\Pages;
use App\Models\MeetRoom;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class MeetRoomResource extends Resource
{
    protected static ?string $model = MeetRoom::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-video-camera';

    protected static UnitEnum|string|null $navigationGroup = 'Mis Aplicaciones';

    protected static ?string $navigationLabel = 'Mis Reuniones';

    protected static ?string $modelLabel = 'Sala de Reunion';

    protected static ?string $pluralModelLabel = 'Mis Reuniones';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        if (! (tenant()?->hasEntity('MeetRoom') ?? false)) {
            return false;
        }

        return Auth::user()?->can('ViewAny:MeetRoom') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }
        $count = MeetRoom::pendingCountForUser($user->id);

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informacion de la Reunion')
                    ->icon('heroicon-o-calendar-days')
                    ->description('Datos basicos de la reunion')
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('name')
                            ->label('Nombre de la reunion')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Descripcion')
                            ->rows(2)
                            ->maxLength(1000),

                        Select::make('type')
                            ->label('Tipo de sala')
                            ->options([
                                'unique' => 'Unica',
                                'recurrent' => 'Recurrente',
                            ])
                            ->default('unique')
                            ->required()
                            ->live(),

                        DatePicker::make('scheduled_date')
                            ->label('Fecha programada')
                            ->required(fn (callable $get) => $get('type') === 'unique')
                            ->minDate(today())
                            ->native(false)
                            ->disabled(fn (callable $get) => $get('type') === 'recurrent')
                            ->helperText('No aplica para salas recurrentes'),

                        TimePicker::make('scheduled_time')
                            ->label('Hora programada')
                            ->required(fn (callable $get) => $get('type') === 'unique')
                            ->seconds(false)
                            ->disabled(fn (callable $get) => $get('type') === 'recurrent')
                            ->helperText('No aplica para salas recurrentes'),

                        TextInput::make('duration_minutes')
                            ->label('Duracion (minutos)')
                            ->numeric()
                            ->default(60)
                            ->minValue(15)
                            ->maxValue(480)
                            ->disabled(fn (callable $get) => $get('type') === 'recurrent')
                            ->helperText('No aplica para salas recurrentes'),

                        Fieldset::make('Sala de Espera')
                            ->columnSpanFull()
                            ->components([
                                TextInput::make('waiting_room_video_url')
                                    ->label('URL del video de espera (YouTube)')
                                    ->url()
                                    ->placeholder('https://www.youtube.com/embed/...')
                                    ->helperText('Si no se proporciona, se mostrara un mensaje predeterminado'),

                                Textarea::make('waiting_room_message')
                                    ->label('Mensaje personalizado')
                                    ->rows(2)
                                    ->helperText('Mensaje que se mostrara si no hay video configurado'),
                            ]),
                    ])->columns(2),

                Section::make('Invitados')
                    ->icon('heroicon-o-user-group')
                    ->description('Lista de invitados a la reunion')
                    ->columnSpanFull()
                    ->components([
                        Repeater::make('invitations')
                            ->label('Lista de invitados')
                            ->components([
                                Hidden::make('id'),

                                Select::make('invitation_type')
                                    ->label('Tipo de invitado')
                                    ->options([
                                        'internal' => 'Usuario Interno',
                                        'external' => 'Usuario Externo',
                                    ])
                                    ->default('internal')
                                    ->required()
                                    ->reactive(),

                                Select::make('invitable_id')
                                    ->label('Usuario')
                                    ->options(fn () => User::where('id', '!=', Auth::id())->pluck('name', 'id'))
                                    ->searchable()
                                    ->visible(fn (callable $get) => $get('invitation_type') === 'internal')
                                    ->required(fn (callable $get) => $get('invitation_type') === 'internal'),

                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->visible(fn (callable $get) => $get('invitation_type') === 'external')
                                    ->required(fn (callable $get) => $get('invitation_type') === 'external'),

                                TextInput::make('name')
                                    ->label('Nombre del invitado')
                                    ->placeholder('Ej: Juan Pérez')
                                    ->visible(fn (callable $get) => $get('invitation_type') === 'external'),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Agregar Invitado')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['email'] ?? $state['invitable_id'] ?? 'Nuevo invitado'),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room_code')
                    ->label('Codigo')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('user.name')
                    ->label('Moderador')
                    ->sortable(),

                TextColumn::make('scheduled_date')
                    ->label('Fecha')
                    ->formatStateUsing(fn ($state) => $state ? $state->format('d/m/Y') : '—')
                    ->sortable(),

                TextColumn::make('scheduled_time')
                    ->label('Hora')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('H:i') : '—')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'active' => 'Activa',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    }),

                BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'info' => 'unique',
                        'gray' => 'recurrent',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unique' => 'Unica',
                        'recurrent' => 'Recurrente',
                    }),

                TextColumn::make('invitations_count')
                    ->label('Invitados')
                    ->counts('invitations')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->action(
                        Action::make('viewInvitations')
                            ->label('Ver invitados')
                            ->modalHeading('Invitados')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar')
                            ->modalContent(fn (MeetRoom $record) => view('filament.resources.meet-room.partials.invitations-modal', [
                                'invitations' => $record->invitations,
                            ]))
                    ),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'active' => 'Activa',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    ]),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'unique' => 'Unica',
                        'recurrent' => 'Recurrente',
                    ]),
            ])
            ->actions([
                Action::make('join')
                    ->label('Unirse')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success')
                    ->url(fn (MeetRoom $record): string => route('meet.join', ['roomCode' => $record->room_code]))
                    ->openUrlInNewTab()
                    ->visible(function (MeetRoom $record): bool {
                        $user = Auth::user();
                        $result = $record->canAccess($user);

                        Log::debug('[MeetRoom] Accion "Unirse" evaluada', [
                            'room_id' => $record->id,
                            'room_name' => $record->name,
                            'room_user_id' => $record->user_id,
                            'auth_user_id' => $user?->id,
                            'auth_user_name' => $user?->name,
                            'isOwner' => $user ? $record->isOwner($user) : 'auth_null',
                            'isInvited' => $user ? $record->isInvited($user) : 'auth_null',
                            'canAccess' => $result,
                            'visible' => $result,
                        ]);

                        return $result;
                    }),
                EditAction::make()
                    ->visible(function (MeetRoom $record): bool {
                        $user = Auth::user();
                        $result = $record->isOwner($user);

                        Log::debug('[MeetRoom] Accion "Editar" evaluada', [
                            'room_id' => $record->id,
                            'room_user_id' => $record->user_id,
                            'auth_user_id' => $user?->id,
                            'isOwner' => $result,
                            'visible' => $result,
                        ]);

                        return $result;
                    }),
                DeleteAction::make()
                    ->visible(function (MeetRoom $record): bool {
                        $user = Auth::user();
                        $result = $record->isOwner($user);

                        Log::debug('[MeetRoom] Accion "Eliminar" evaluada', [
                            'room_id' => $record->id,
                            'room_user_id' => $record->user_id,
                            'auth_user_id' => $user?->id,
                            'isOwner' => $result,
                            'visible' => $result,
                        ]);

                        return $result;
                    }),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        Log::debug('[MeetRoom] getEloquentQuery llamado', [
            'auth_id' => Auth::id(),
            'auth_check' => Auth::check(),
        ]);

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->accessibleBy(Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetRooms::route('/'),
            'create' => Pages\CreateMeetRoom::route('/create'),
            'view' => Pages\ViewMeetRoom::route('/{record}'),
            'edit' => Pages\EditMeetRoom::route('/{record}/edit'),
        ];
    }

    public static function canEdit($record): bool
    {
        return $record->isOwner(Auth::user());
    }

    public static function canDelete($record): bool
    {
        return $record->isOwner(Auth::user());
    }
}
