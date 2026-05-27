<?php

namespace App\Filament\Central\Resources\PlanResource\Pages;

use App\Filament\Central\Resources\PlanResource;
use App\Filament\Central\Resources\PlanResource\Schemas\PlanForm;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    public function form(Schema $schema): Schema
    {
        return PlanForm::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
