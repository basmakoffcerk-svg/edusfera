<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Регламентные задачи Edusfera ───

// 1. Автоматический биллинг подписок репетиторов (ежедневно в 02:00)
Schedule::command('edusfera:subscriptions-process-billing')
    ->dailyAt('02:00')
    ->runInBackground();

// 2. Снятие просроченных блокировок со слотов неоплаченных уроков (каждую минуту)
Schedule::command('lessons:expire-locks')
    ->everyMinute()
    ->withoutOverlapping();

// 3. Автозавершение прошедших уроков и расчет выплат (каждые 15 минут)
Schedule::command('lessons:complete')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// 4. Публикация доменных событий интеграций через Outbox (каждую минуту)
Schedule::command('integration:publish-outbox')
    ->everyMinute()
    ->withoutOverlapping();

// 5. Очистка старых событий Outbox (ежедневно в 03:00)
Schedule::command('integration:cleanup-outbox')
    ->dailyAt('03:00');

// 6. Сверка холдов баланса учеников (каждые 30 минут)
Schedule::command('wallet:reconcile-holds')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// 7. Оптимизация и очистка временных файлов хранилища (еженедельно)
Schedule::command('app:optimize-storage')
    ->weekly();
