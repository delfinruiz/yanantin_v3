<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\FilamentShield;
use Illuminate\Support\ServiceProvider;

class PlanShieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped('filament-shield', fn () => new class extends FilamentShield
        {
            public function getResources(): ?array
            {
                $resources = parent::getResources();

                if (! $this->shouldFilter()) {
                    return $resources;
                }

                $allowed = $this->allowedEntities();

                return array_filter($resources, fn ($resource) => in_array($resource['model'], $allowed));
            }

            public function getPages(): ?array
            {
                $pages = parent::getPages();

                if (! $this->shouldFilter()) {
                    return $pages;
                }

                $allowed = $this->allowedEntities();

                return array_filter($pages, function ($page) use ($allowed) {
                    $basename = class_basename($page['pageFqcn']);

                    return in_array($basename, $allowed);
                });
            }

            public function getWidgets(): ?array
            {
                $widgets = parent::getWidgets();

                if (! $this->shouldFilter()) {
                    return $widgets;
                }

                $allowed = $this->allowedEntities();

                return array_filter($widgets, function ($widget) use ($allowed) {
                    $fqcn = $widget['widgetFqcn'] ?? $widget;
                    $basename = class_basename($fqcn);

                    return in_array($basename, $allowed);
                });
            }

            protected function shouldFilter(): bool
            {
                if (! tenancy()->initialized) {
                    return false;
                }

                $plan = tenant()?->tenantPlan;

                return $plan && ! empty($plan->features);
            }

            protected function allowedEntities(): array
            {
                return tenant()->allowedEntities();
            }
        });
    }
}
