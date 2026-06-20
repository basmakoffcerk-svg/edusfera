<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Support\Metrics\Registry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;

/**
 * Эндпоинт `GET /metrics` (требование 16.2).
 *
 * Возвращает все зарегистрированные метрики в Prometheus exposition format
 * с Content-Type `text/plain; version=0.0.4`.
 *
 * Перед рендером сэмплирует gauge `outbox_pending_total` актуальным числом
 * неопубликованных строк в `integration_outbox` (требование 16.4).
 *
 * Доступ к эндпоинту защищён middleware `App\Http\Middleware\MetricsAuth`
 * (scope `internal:metrics:read` ИЛИ basic-auth, требование 16.3).
 */
final class MetricsController
{
    public function __construct(private readonly Registry $registry) {}

    public function __invoke(): Response
    {
        // Обновляем gauge актуальным значением перед экспортом (требование 16.4).
        $this->registry->sampleOutboxPending();

        return new Response(
            $this->registry->render(),
            Response::HTTP_OK,
            ['Content-Type' => RenderTextFormat::MIME_TYPE],
        );
    }
}
