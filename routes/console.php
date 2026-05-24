<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Каждые 5 минут: отмена неоплаченных бронирований
Schedule::command('lessons:expire-locks')->everyFiveMinutes();

// Каждые 15 минут: завершение прошедших уроков + settlement
Schedule::command('lessons:complete')->everyFifteenMinutes();
