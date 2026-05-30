<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\MessagesPage;
use App\Filament\Pages\Auth\Register;
use App\Filament\Widgets\CommissionLadderWidget;
use App\Filament\Widgets\TutorActionCenterWidget;
use App\Filament\Widgets\TutorFinanceSummaryWidget;
use App\Filament\Widgets\TutorOnboardingWidget;
use App\Filament\Widgets\TutorWelcomeWidget;
use App\Filament\Widgets\StudentWelcomeWidget;
use App\Filament\Widgets\StudentUpcomingLessonsWidget;
use App\Filament\Widgets\StudentTutorsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->homeUrl('/')
            ->login(Login::class)
            ->registration(Register::class)
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->simplePageMaxContentWidth(MaxWidth::Full)
            ->colors([
                'primary' => Color::Hex('#7D39EB'),
                'secondary' => Color::Hex('#C6FF33'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render("@vite('resources/css/app.css')")
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                MessagesPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                TutorWelcomeWidget::class,
                TutorFinanceSummaryWidget::class,
                TutorActionCenterWidget::class,
                CommissionLadderWidget::class,
                TutorOnboardingWidget::class,
                StudentWelcomeWidget::class,
                StudentUpcomingLessonsWidget::class,
                StudentTutorsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
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
