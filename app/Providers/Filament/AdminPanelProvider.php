<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\TenantEmailVerificationPrompt;
use App\Filament\Pages\Auth\TenantLogin;
use App\Filament\Pages\Auth\TenantRegister;
use App\Filament\Pages\Auth\TenantRequestPasswordReset;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\CheckSubscriptionStatus;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login(TenantLogin::class)
            ->passwordReset(TenantRequestPasswordReset::class)
            ->registration(TenantRegister::class)
            ->emailVerification(TenantEmailVerificationPrompt::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName(fn () => tenant()?->name ?? 'Admin')
            ->brandLogo(fn () => ($url = tenant()?->logoLightUrl()) ? new HtmlString('<img src="'.$url.'" alt="Logo" style="height:2.5rem;max-height:2.5rem;width:auto" class="object-contain">') : null)
            ->darkModeBrandLogo(fn () => ($url = tenant()?->logoDarkUrl()) ? new HtmlString('<img src="'.$url.'" alt="Logo" style="height:2.5rem;max-height:2.5rem;width:auto" class="object-contain">') : null)
            ->favicon(fn () => tenant()?->faviconUrl())
            ->maxContentWidth('full')
            ->simplePageMaxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                InitializeTenancyBySubdomain::class,
                CheckSubscriptionStatus::class,
                PreventAccessFromCentralDomains::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->scopeToTenant(false),
            ])
            ->userMenuItems([
                'logout' => Action::make('logout')
                    ->label('Cerrar sesion')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('danger')
                    ->action(function () {
                        filament()->auth()->logout();

                        return redirect('/');
                    }),
            ])
            ->userMenu(position: UserMenuPosition::Topbar)
            ->sidebarCollapsibleOnDesktop();
    }
}
