<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasFactory;

    protected $appends = ['next_billing_date'];

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'plan', 'plan_id', 'status', 'status_changed_at', 'token_ai'];
    }

    public function isActive(): bool
    {
        return $this->status === 'activa';
    }

    public function isPaused(): bool
    {
        return $this->status === 'pausada';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspendida';
    }

    protected static function booted(): void
    {
        static::saving(function (Tenant $tenant) {
            if ($tenant->isDirty('status')) {
                $tenant->status_changed_at = now();
            }
        });
    }

    public function tenantPlan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function maxUsers(): ?int
    {
        return $this->tenantPlan?->max_users;
    }

    public function allowedEntities(): array
    {
        $plan = $this->tenantPlan;

        if (! $plan || empty($plan->features)) {
            return [];
        }

        return collect(config('plans.features', []))
            ->only($plan->features)
            ->pluck('entities')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }

    public function hasEntity(string $basename): bool
    {
        return in_array($basename, $this->allowedEntities());
    }

    public function getNextBillingDateAttribute(): ?Carbon
    {
        if (! $this->created_at) {
            return null;
        }

        $signupDay = (int) $this->created_at->day;
        $next = now()->startOfMonth()->addMonth();
        $lastDay = (int) $next->daysInMonth;

        return $next->setDay(min($signupDay, $lastDay));
    }

    public function getAdminEmailAttribute(): string
    {
        $domain = $this->domain_name
            ?? $this->domains()->first()?->domain
            ?? $this->id;

        return 'admin@'.$domain.'.localhost';
    }

    public function slug(): string
    {
        return $this->domain_name
            ?? $this->domains()->first()?->domain
            ?? $this->id;
    }

    public function faviconUrl(): ?string
    {
        return $this->pathToUrl($this->favicon_url);
    }

    public function logoLightUrl(): ?string
    {
        return $this->pathToUrl($this->logo_light_url);
    }

    public function logoDarkUrl(): ?string
    {
        return $this->pathToUrl($this->logo_dark_url);
    }

    public function loginBackgroundUrl(): ?string
    {
        return $this->pathToUrl($this->login_background_image);
    }

    protected function pathToUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, 9);
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
