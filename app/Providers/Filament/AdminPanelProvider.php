<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\MessagesPage;
use App\Filament\Widgets\AdminWelcomeWidget;
use App\Filament\Widgets\CommissionLadderWidget;
use App\Filament\Widgets\StudentTutorsWidget;
use App\Filament\Widgets\StudentUpcomingLessonsWidget;
use App\Filament\Widgets\StudentWelcomeWidget;
use App\Filament\Widgets\TutorActionCenterWidget;
use App\Filament\Widgets\TutorFinanceOverview;
use App\Filament\Widgets\TutorOnboardingWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->homeUrl('/')
            ->brandName('Edusfera')
            ->favicon(asset('favicon.svg'))
            ->login(Login::class)
            ->registration(Register::class)
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->maxContentWidth(MaxWidth::Full)
            ->simplePageMaxContentWidth(MaxWidth::Full)
            ->colors([
                'primary' => Color::Hex('#7D39EB'),
                'secondary' => Color::Hex('#C6FF33'),
                'success' => Color::Hex('#10B981'),
                'warning' => Color::Hex('#F59E0B'),
                'danger' => Color::Hex('#EF4444'),
                'info' => Color::Hex('#06B6D4'),
            ])
            ->navigationGroups([
                'Занятия',
                'Подготовка',
                'Связь',
                'Финансы',
                'Профиль',
                'Управление пользователями',
                'Модерация',
                'Диспуты и безопасность',
                'Хранилище и SaaS-тарифы',
                'Управление ИИ и Моделями',
                'Микросервисы и Шлюз (Gateway)',
                'Контент',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                MessagesPage::class,
            ])
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => auth()->user()?->isTutor()
                    ? view('filament.widgets.partials.tutor-dashboard-styles')->render()
                    : '',
            )
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\SiteAdmin\Widgets\AdminOverviewStatsWidget::class,
                AdminWelcomeWidget::class,
                \App\Filament\Widgets\TutorHeroWidget::class,
                TutorFinanceOverview::class,
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
