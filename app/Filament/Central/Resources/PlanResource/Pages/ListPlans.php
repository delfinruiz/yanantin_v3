<?php

namespace App\Filament\Central\Resources\PlanResource\Pages;

use App\Filament\Central\Resources\PlanResource;
use App\Filament\Central\Resources\PlanResource\Tables\PlansTable;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    public function table(Table $table): Table
    {
        return PlansTable::configure($table);
    }
}
