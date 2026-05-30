<?php

declare(strict_types=1);

use App\Http\Webhooks\AiWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Входящие webhook'и от внешних микросервисов и провайдеров. Подключаются
| в bootstrap/app.php через ->withRouting(then: ...) с префиксом `webhooks`
| и middleware-группой `api` (для AssignRequestId/X-Request-Id и structured
| logging), но БЕЗ throttle:api.v1 и без auth — аутентификация здесь делается
| HMAC-подписью на уровне контроллера (требование 10).
|
| Каждый приёмник наследует App\Http\Webhooks\AbstractWebhookController и
| проходит проверки подписи (`X-Signature`), timestamp (`X-Timestamp`) и
| nonce (`X-Nonce`) до выполнения доменной логики.
*/

// AI-сервис → ядро: рекомендации/домашние задания (требование 10, см. design 3.6).
Route::post('/ai/recommendations', AiWebhookController::class)->name('ai.recommendations');
