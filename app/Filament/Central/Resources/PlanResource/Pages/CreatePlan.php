<?php

namespace App\Filament\Central\Resources\PlanResource\Pages;

use App\Filament\Central\Resources\PlanResource;
use App\Filament\Central\Resources\PlanResource\Schemas\PlanForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    public function form(Schema $schema): Schema
    {
        return PlanForm::configure($schema);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
