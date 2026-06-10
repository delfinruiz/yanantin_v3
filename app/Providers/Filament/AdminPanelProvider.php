<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\TenantEmailVerificationPrompt;
use App\Filament\Pages\Auth\TenantLogin;
use App\Filament\Pages\Auth\TenantRegister;
use App\Filament\Pages\Auth\TenantRequestPasswordReset;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditProfilePage;
use App\Filament\Pages\ManageSettings;
use App\Filament\Pages\Webmail;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Models\EmailAccount;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverPages(in: app_path('CpanelPages'), for: 'App\CpanelPages')
            ->pages([
                Dashboard::class,
                ManageSettings::class,
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
                    ->navigationGroup('Configuracion')
                    ->scopeToTenant(false),
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn () => auth()->user()->name)
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon('heroicon-o-user-circle'),
                'logout' => Action::make('logout')
                    ->label('Cerrar sesion')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('danger')
                    ->action(function () {
                        filament()->auth()->logout();

                        return redirect('/');
                    }),
            ])
            ->navigationItems([
                NavigationItem::make('Webmail')
                    ->group('Mis Aplicaciones')
                    ->icon('heroicon-o-envelope')
                    ->badge(function () {
                        $user = Auth::user();
                        if (! $user) {
                            return null;
                        }
                        $account = EmailAccount::where('user_id', $user->id)->first();
                        if (! $account) {
                            return null;
                        }
                        $count = Cache::get('imap_unread_'.$account->id);

                        return $count > 0 ? (string) $count : null;
                    }, 'danger')
                    ->url(fn (): string => Webmail::getUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (): bool => Webmail::canAccess()),
            ])
            ->userMenu(position: UserMenuPosition::Topbar)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => tenant()?->hasEntity('MoodPromptOverlay') ? Blade::render('@livewire(\'mood-prompt-overlay\')') : '',
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => tenant()?->hasEntity('EmailAccount') ? Blade::render('@livewire(\'webmail.webmail-badge-poll\')') : '',
            );
    }
}
