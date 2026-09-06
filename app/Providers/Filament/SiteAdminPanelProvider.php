<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\MessagesPage;
use App\Filament\SiteAdmin\Auth\Login;
use App\Filament\SiteAdmin\Widgets\AdminOperationsWidget;
use App\Filament\SiteAdmin\Widgets\AdminOverviewStatsWidget;
use App\Filament\SiteAdmin\Widgets\AdminQuickActionsWidget;
use App\Http\Middleware\LogoutUnauthorizedSiteAdminUser;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SiteAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('site-admin')
            ->path('site-admin')
            ->homeUrl('/')
            ->login(Login::class)
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->maxContentWidth(MaxWidth::Full)
            ->simplePageMaxContentWidth(MaxWidth::Full)
            ->brandName('Edusfera Tech Admin')
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::Hex('#7D39EB'),
                'secondary' => Color::Hex('#C6FF33'),
                'success' => Color::Hex('#10B981'),
                'warning' => Color::Hex('#F59E0B'),
                'danger' => Color::Hex('#EF4444'),
                'info' => Color::Hex('#06B6D4'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                MessagesPage::class,
            ])
            ->widgets([
                AdminOverviewStatsWidget::class,
                AdminQuickActionsWidget::class,
                AdminOperationsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                LogoutUnauthorizedSiteAdminUser::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
