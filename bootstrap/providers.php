<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\SiteAdminPanelProvider;
use App\Providers\MetricsServiceProvider;
use App\Providers\RouteServiceProvider;
use Laravel\Passport\PassportServiceProvider;

return [
    AppServiceProvider::class,
    MetricsServiceProvider::class,
    RouteServiceProvider::class,
    AdminPanelProvider::class,
    SiteAdminPanelProvider::class,
    PassportServiceProvider::class,
];
