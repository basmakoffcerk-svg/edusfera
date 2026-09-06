<?php

declare(strict_types=1);

namespace App\Http\Api\Internal\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Health check эндпоинты для service-to-service мониторинга.
 *
 * Liveness (`GET /api/internal/v1/health`) — без аутентификации.
 *   Микросервис использует его для базовой проверки «ядро живо».
 *
 * Readiness (`GET /api/internal/v1/health/ready`) — под Passport client_credentials
 *   со scope `internal:metrics:read`. Проверяет DB, Redis и количество
 *   застрявших событий в outbox.
 */
final class InternalHealthController
{
    /**
     * Liveness probe — ядро запущено и отвечает на запросы.
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => Carbon::now('UTC')->toIso8601String(),
        ]);
    }

    /**
     * Readiness probe — ядро готово обслуживать запросы микросервисов.
     *
     * Проверяет:
     *  - PostgreSQL: `SELECT 1`
     *  - Redis: `PING`
     *  - Outbox: количество неопубликованных событий (pending)
     */
    public function readiness(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database
        try {
            DB::select('SELECT 1');
            $checks['database'] = ['status' => 'ok'];
        } catch (Throwable $e) {
            $checks['database'] = ['status' => 'fail', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Redis
        try {
            $pong = Redis::ping();
            $checks['redis'] = ['status' => is_string($pong) || $pong === true ? 'ok' : 'fail'];
        } catch (Throwable $e) {
            $checks['redis'] = ['status' => 'fail', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Outbox pending count
        try {
            $pending = DB::table('integration_outbox')
                ->whereNull('published_at')
                ->count();
            $checks['outbox'] = ['status' => 'ok', 'pending' => $pending];
        } catch (Throwable $e) {
            $checks['outbox'] = ['status' => 'fail', 'error' => $e->getMessage()];
            $healthy = false;
        }

        $status = $healthy ? 'ok' : 'degraded';
        $httpCode = $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return response()->json([
            'status' => $status,
            'timestamp' => Carbon::now('UTC')->toIso8601String(),
            'checks' => $checks,
        ], $httpCode);
    }
}
