<?php

declare(strict_types=1);

namespace App\Http\Api\Internal\Controllers;

use App\Http\Middleware\AssignRequestId;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Мониторинг integration outbox для микросервисов.
 *
 * Эндпоинт `GET /api/internal/v1/outbox/pending` возвращает статистику
 * неопубликованных событий, сгруппированных по `event_type`.
 *
 * Аутентификация — Passport client_credentials + scope `internal:outbox:read`.
 */
final class InternalOutboxController
{
    /**
     * GET /api/internal/v1/outbox/pending
     *
     * Возвращает количество неопубликованных и застрявших (attempts >= 10) событий.
     */
    public function pending(): JsonResponse
    {
        $stats = DB::table('integration_outbox')
            ->whereNull('published_at')
            ->selectRaw('event_type, COUNT(*) as pending, MAX(attempts) as max_attempts')
            ->groupBy('event_type')
            ->get();

        $totalPending = $stats->sum('pending');
        $stuckCount = DB::table('integration_outbox')
            ->whereNull('published_at')
            ->where('attempts', '>=', 10)
            ->count();

        return response()->json([
            'data' => [
                'total_pending' => $totalPending,
                'stuck' => $stuckCount,
                'by_event_type' => $stats->map(fn ($row) => [
                    'event_type' => $row->event_type,
                    'pending' => (int) $row->pending,
                    'max_attempts' => (int) $row->max_attempts,
                ])->all(),
            ],
            'meta' => [
                'request_id' => $this->resolveRequestId(),
            ],
        ]);
    }

    private function resolveRequestId(): string
    {
        try {
            return (string) app(AssignRequestId::CONTAINER_KEY);
        } catch (\Throwable) {
            return '';
        }
    }
}
