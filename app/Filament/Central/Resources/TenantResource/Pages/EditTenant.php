<?php

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['favicon_url'] = $this->toDiskPath($data['favicon_url'] ?? null);
        $data['logo_light_url'] = $this->toDiskPath($data['logo_light_url'] ?? null);
        $data['logo_dark_url'] = $this->toDiskPath($data['logo_dark_url'] ?? null);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['favicon_url'] = $this->extractFilePath($data['favicon_url'] ?? null);
        $data['logo_light_url'] = $this->extractFilePath($data['logo_light_url'] ?? null);
        $data['logo_dark_url'] = $this->extractFilePath($data['logo_dark_url'] ?? null);

        return $data;
    }

    protected function toDiskPath(?string $value): ?string
    {
        if (! $value || ! is_string($value)) {
            return null;
        }

        $value = str_replace(Storage::disk('public')->url(''), '', $value);

        if (str_starts_with($value, '/storage/')) {
            $value = substr($value, 9);
        }

        return ltrim($value, '/');
    }

    protected function extractFilePath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[array_key_first($value)] ?? null;
        }

        if (! $value || ! is_string($value)) {
            return null;
        }

        return $value;
    }
}
