<?php

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use App\Models\Plan;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $domain = $data['domain'] ?? null;
        unset($data['domain'], $data['domain_checked'], $data['domain_available'], $data['favicon_url'], $data['logo_light_url'], $data['logo_dark_url']);

        if ($domain) {
            $data['domain_name'] = $domain;
        }

        if ($planId = $data['plan_id'] ?? null) {
            $plan = Plan::find($planId);
            $data['plan'] = $plan?->slug;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $domain = $this->data['domain'] ?? null;

        if ($domain) {
            $this->record->domains()->create(['domain' => $domain]);
        }

        $this->record->update([
            'favicon_url' => $this->extractFilePath($this->data['favicon_url'] ?? null),
            'logo_light_url' => $this->extractFilePath($this->data['logo_light_url'] ?? null),
            'logo_dark_url' => $this->extractFilePath($this->data['logo_dark_url'] ?? null),
        ]);
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
