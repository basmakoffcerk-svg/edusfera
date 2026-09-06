<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use App\Contracts\Events\EventActor;
use App\Contracts\Events\EventAggregate;
use App\Contracts\Events\EventEnvelope;
use App\Models\Lesson;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Фабрика интеграционных конвертов.
 *
 * Заполняет инфраструктурные поля конверта по единым правилам:
 *  - `id`          — UUID v7 (монотонно растущий, удобен для сортировки в outbox);
 *  - `occurred_at` — текущий момент в ISO-8601 UTC;
 *  - `tenant`      — из `config('events.tenant')` (по умолчанию `edusfera`);
 *  - `trace_id`    — request_id, проброшенный через цепочку запроса
 *                    (берётся из контейнера под ключом `request_id`, заданным
 *                    middleware AssignRequestId; при отсутствии — UUID v7).
 *
 * Метод `make()` намеренно гибкий, чтобы доменные сервисы (напр. задача 14 —
 * lesson.completed) могли строить любые события поверх него.
 */
final readonly class EventEnvelopeFactory
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Собрать конверт интеграционного события.
     *
     * @param  array<string, mixed>  $payload
     * @param  EventActor|array<string,mixed>  $actor  объект actor'а либо `['type'=>..,'id'=>..,'role'=>..]`
     */
    public function make(
        string $type,
        int $version,
        string $aggregateType,
        string|int $aggregateId,
        array $payload,
        EventActor|array $actor,
    ): EventEnvelope {
        return new EventEnvelope(
            id: (string) Str::uuid7(),
            type: $type,
            version: $version,
            occurredAt: $this->now(),
            tenant: $this->tenant(),
            traceId: $this->traceId(),
            actor: $this->normalizeActor($actor),
            aggregate: new EventAggregate($aggregateType, $aggregateId),
            payload: $payload,
        );
    }

    /**
     * Текущий момент в ISO-8601 UTC.
     */
    private function now(): string
    {
        return Carbon::now('UTC')->toIso8601String();
    }

    private function tenant(): string
    {
        $tenant = config('events.tenant', 'edusfera');

        return is_string($tenant) && $tenant !== '' ? $tenant : 'edusfera';
    }

    /**
     * request_id, проброшенный AssignRequestId-middleware. Вне HTTP-контекста
     * (cron, очереди) контейнер может не содержать ключ — тогда генерируем
     * новый UUID v7, чтобы trace_id всегда был непустым.
     */
    private function traceId(): string
    {
        if ($this->container->bound('request_id')) {
            $requestId = $this->container->make('request_id');

            if (is_string($requestId) && $requestId !== '') {
                return $requestId;
            }
        }

        return (string) Str::uuid7();
    }

    /**
     * Собрать конверт события `lesson.completed.v1`.
     *
     * Actor — системный (cron), т.к. команда запускается по расписанию.
     * Payload соответствует JSON Schema `docs/events/lesson.completed.v1.json`.
     */
    public function lessonCompleted(Lesson $lesson): EventEnvelope
    {
        $fallback = Carbon::now('UTC')->toIso8601String();

        return $this->make(
            type: 'lesson.completed',
            version: 1,
            aggregateType: 'lesson',
            aggregateId: $lesson->id,
            payload: [
                'lesson_id' => $lesson->id,
                'tutor_id' => $lesson->tutor_id,
                'student_id' => $lesson->student_id,
                'started_at' => $lesson->start_time !== null
                    ? Carbon::parse($lesson->start_time)->utc()->toIso8601String()
                    : $fallback,
                'ended_at' => $lesson->end_time !== null
                    ? Carbon::parse($lesson->end_time)->utc()->toIso8601String()
                    : $fallback,
                'package_code' => $lesson->package_code ?? 'single',
            ],
            actor: new EventActor('system', 'cron'),
        );
    }

    /**
     * Собрать конверт события `lesson.booked.v1`.
     *
     * Emitted при бронировании урока студентом.
     * Payload соответствует JSON Schema `docs/events/lesson.booked.v1.json`.
     */
    public function lessonBooked(Lesson $lesson, EventActor|array $actor): EventEnvelope
    {
        return $this->make(
            type: 'lesson.booked',
            version: 1,
            aggregateType: 'lesson',
            aggregateId: $lesson->id,
            payload: [
                'lesson_id' => $lesson->id,
                'tutor_id' => $lesson->tutor_id,
                'student_id' => $lesson->student_id,
                'start_time' => $lesson->start_time !== null
                    ? Carbon::parse($lesson->start_time)->utc()->toIso8601String()
                    : Carbon::now('UTC')->toIso8601String(),
                'end_time' => $lesson->end_time !== null
                    ? Carbon::parse($lesson->end_time)->utc()->toIso8601String()
                    : Carbon::now('UTC')->toIso8601String(),
                'duration_minutes' => (int) $lesson->duration_minutes,
                'price' => (string) $lesson->price,
                'package_code' => $lesson->package_code ?? 'single',
            ],
            actor: $actor,
        );
    }

    /**
     * Собрать конверт события `lesson.cancelled.v1`.
     *
     * Emitted при отмене урока.
     * Payload соответствует JSON Schema `docs/events/lesson.cancelled.v1.json`.
     */
    public function lessonCancelled(
        Lesson $lesson,
        string $cancelledBy,
        string $cancelReason,
        EventActor|array $actor,
    ): EventEnvelope {
        return $this->make(
            type: 'lesson.cancelled',
            version: 1,
            aggregateType: 'lesson',
            aggregateId: $lesson->id,
            payload: [
                'lesson_id' => $lesson->id,
                'tutor_id' => $lesson->tutor_id,
                'student_id' => $lesson->student_id,
                'cancelled_by' => $cancelledBy,
                'cancel_reason' => $cancelReason,
            ],
            actor: $actor,
        );
    }

    /**
     * Собрать конверт события `payment.completed.v1`.
     *
     * Emitted при успешной обработке оплаты урока.
     * Payload соответствует JSON Schema `docs/events/payment.completed.v1.json`.
     *
     * @param  array{lesson_id: int, student_id: int, tutor_id: int, amount: string, currency: string, transaction_id: int|null}  $data
     */
    public function paymentCompleted(array $data, EventActor|array $actor): EventEnvelope
    {
        return $this->make(
            type: 'payment.completed',
            version: 1,
            aggregateType: 'payment',
            aggregateId: $data['lesson_id'],
            payload: [
                'lesson_id' => $data['lesson_id'],
                'student_id' => $data['student_id'],
                'tutor_id' => $data['tutor_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'transaction_id' => $data['transaction_id'] ?? null,
            ],
            actor: $actor,
        );
    }

    /**
     * @param  EventActor|array<string,mixed>  $actor
     */
    private function normalizeActor(EventActor|array $actor): EventActor
    {
        if ($actor instanceof EventActor) {
            return $actor;
        }

        /** @var string $type */
        $type = $actor['type'] ?? 'system';
        /** @var string|int $id */
        $id = $actor['id'] ?? 'system';
        $role = isset($actor['role']) && is_string($actor['role']) ? $actor['role'] : null;

        return new EventActor($type, $id, $role);
    }
}
