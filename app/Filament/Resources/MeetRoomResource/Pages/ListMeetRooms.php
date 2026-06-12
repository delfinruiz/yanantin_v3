<?php

namespace App\Filament\Resources\MeetRoomResource\Pages;

use App\Filament\Resources\MeetRoomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeetRooms extends ListRecords
{
    protected static string $resource = MeetRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
