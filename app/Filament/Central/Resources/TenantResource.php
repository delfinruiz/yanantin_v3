<?php

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\TenantResource\Pages;
use App\Filament\Central\Resources\TenantResource\Schemas\TenantForm;
use App\Filament\Central\Resources\TenantResource\Tables\TenantsTable;
use App\Models\Tenant;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $panel = 'central';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Suscripcion';

    protected static ?string $pluralModelLabel = 'Suscripciones';

    protected static UnitEnum|string|null $navigationGroup = 'Administracion';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getRouteBaseName(?Panel $panel = null): string
    {
        $panel = Filament::getPanel('central');

        return parent::getRouteBaseName($panel);
    }

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
