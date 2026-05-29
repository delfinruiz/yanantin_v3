<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeZone;

class TenantTimezoneService
{
    protected string $defaultTimezone;

    public function __construct()
    {
        $this->defaultTimezone = config('app.timezone', 'UTC');
    }

    /**
     * Get the timezone configured for the current tenant, falling back to app default.
     */
    public function timezone(): string
    {
        if (! $tenant = tenant()) {
            return $this->defaultTimezone;
        }

        return $tenant->timezone ?: $this->defaultTimezone;
    }

    /**
     * Get a DateTimeZone instance for the current tenant.
     */
    public function dateTimeZone(): DateTimeZone
    {
        return new DateTimeZone($this->timezone());
    }

    /**
     * Get the current datetime in the tenant's timezone.
     */
    public function now(): Carbon
    {
        return Carbon::now($this->timezone());
    }

    /**
     * Get the current immutable datetime in the tenant's timezone.
     */
    public function nowImmutable(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    /**
     * Convert a datetime to the tenant's timezone.
     */
    public function convert(Carbon|CarbonImmutable|\DateTimeInterface|string $date, ?string $fromTimezone = null): Carbon
    {
        if (is_string($date)) {
            $date = Carbon::parse($date, $fromTimezone);
        }

        if ($date instanceof CarbonImmutable) {
            return $date->copy()->setTimezone($this->timezone())->settings(['toStringFormat' => $this->defaultFormat()]);
        }

        /** @var Carbon $date */
        return $date->copy()->setTimezone($this->timezone());
    }

    /**
     * Parse a date string assuming it's in the tenant's timezone.
     */
    public function parse(string $date): Carbon
    {
        return Carbon::parse($date, $this->timezone());
    }

    /**
     * Format a date for display in the tenant's timezone.
     */
    public function format(Carbon|CarbonImmutable|\DateTimeInterface|null $date, string $format = 'Y-m-d H:i:s'): string
    {
        if (! $date) {
            return '';
        }

        return $this->convert($date)->format($format);
    }

    /**
     * Get the default format for this tenant (Y-m-d H:i:s in tenant timezone).
     */
    public function defaultFormat(): string
    {
        return 'Y-m-d H:i:s T';
    }

    /**
     * Get today's date in the tenant's timezone.
     */
    public function today(): Carbon
    {
        return $this->now()->startOfDay();
    }

    /**
     * List all available timezone identifiers.
     */
    public static function availableTimezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    /**
     * Get the current timezone abbreviation (e.g. PST, EST, UTC).
     */
    public function abbreviation(): string
    {
        return $this->now()->abbreviatedTimezoneName;
    }
}
