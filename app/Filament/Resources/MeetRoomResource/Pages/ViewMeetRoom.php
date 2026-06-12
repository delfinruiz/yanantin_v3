<?php

namespace App\Filament\Resources\MeetRoomResource\Pages;

use App\Filament\Resources\MeetRoomResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewMeetRoom extends ViewRecord
{
    protected static string $resource = MeetRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('join')
                ->label('Unirse a la reunion')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('success')
                ->url(fn () => route('meet.join', ['roomCode' => $this->record->room_code]))
                ->openUrlInNewTab(),
            Action::make('edit')
                ->label('Editar')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => MeetRoomResource::getUrl('edit', ['record' => $this->record]))
                ->visible(fn () => $this->record->isOwner(auth()->user())),
        ];
    }
}
