<?php

declare(strict_types=1);

namespace Tests\Feature\Outbox;

use App\Contracts\Events\EventBusInterface;
use App\Contracts\Events\EventEnvelope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Покрывает требования 9.5, 9.6, 9.7, 9.8:
 *  - Команда публикует pending-строки и выставляет published_at (9.5, 9.6)
 *  - При ошибке EventBus: attempts++, last_error заполняется, published_at = NULL (9.7)
 *  - Exponential backoff при attempts >= 10 (9.8)
 *  - Строки с available_at > now() пропускаются (9.5)
 */
class PublishOutboxCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Минимальный валидный envelope-payload для вставки в outbox.
     *
     * @return array<string, mixed>
     */
    private function envelopePayload(string $id = 'test-id-001'): array
    {
        return [
            'id' => $id,
            'type' => 'lesson.completed',
            'version' => 1,
            'occurred_at' => '2025-04-15T10:00:00+00:00',
            'tenant' => 'edusfera',
            'trace_id' => 'trace-001',
            'actor' => ['type' => 'user', 'id' => 42, 'role' => 'tutor'],
            'aggregate' => ['type' => 'lesson', 'id' => 1234],
            'payload' => [
                'lesson_id' => 1234,
                'tutor_id' => 42,
                'student_id' => 99,
                'started_at' => '2025-04-15T09:00:00+00:00',
                'ended_at' => '2025-04-15T10:00:00+00:00',
                'package_code' => 'pack_4',
            ],
        ];
    }

    /**
     * Вставить строку в integration_outbox напрямую через DB.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function insertOutboxRow(array $overrides = []): string
    {
        $id = $overrides['id'] ?? 'row-'.uniqid();
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s');

        DB::table('integration_outbox')->insert(array_merge([
            'id' => $id,
            'event_type' => 'lesson.completed.v1',
            'aggregate_type' => 'lesson',
            'aggregate_id' => '1234',
            'payload' => json_encode($this->envelopePayload($id), JSON_THROW_ON_ERROR),
            'occurred_at' => $now,
            'available_at' => $now,
            'published_at' => null,
            'attempts' => 0,
            'last_error' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides));

        return $id;
    }

    // -------------------------------------------------------------------------
    // Тест 1: успешная публикация — published_at выставляется
    // -------------------------------------------------------------------------

    public function test_publishes_pending_rows_and_sets_published_at(): void
    {
        // Arrange: NullEventBus (no-op) — публикация всегда успешна.
        $this->app->bind(EventBusInterface::class, fn () => new class implements EventBusInterface
        {
            public function publish(EventEnvelope $envelope): void
            {
                // успех — ничего не делаем
            }
        });

        $id1 = $this->insertOutboxRow(['id' => 'pub-001']);
        $id2 = $this->insertOutboxRow(['id' => 'pub-002']);

        // Act
        $this->artisan('integration:publish-outbox')->assertExitCode(0);

        // Assert
        $row1 = DB::table('integration_outbox')->where('id', $id1)->first();
        $row2 = DB::table('integration_outbox')->where('id', $id2)->first();

        $this->assertNotNull($row1->published_at, 'published_at должен быть выставлен для первой строки');
        $this->assertNotNull($row2->published_at, 'published_at должен быть выставлен для второй строки');
        $this->assertNull($row1->last_error);
        $this->assertNull($row2->last_error);
    }

    // -------------------------------------------------------------------------
    // Тест 2: ошибка EventBus — attempts++, last_error, published_at = NULL
    // -------------------------------------------------------------------------

    public function test_on_eventbus_error_increments_attempts_and_sets_last_error(): void
    {
        // Arrange: EventBus всегда бросает исключение.
        $this->app->bind(EventBusInterface::class, fn () => new class implements EventBusInterface
        {
            public function publish(EventEnvelope $envelope): void
            {
                throw new RuntimeException('Redis connection refused');
            }
        });

        $id = $this->insertOutboxRow(['id' => 'fail-001', 'attempts' => 3]);

        // Act
        $this->artisan('integration:publish-outbox')->assertExitCode(0);

        // Assert
        $row = DB::table('integration_outbox')->where('id', $id)->first();

        $this->assertNull($row->published_at, 'published_at должен оставаться NULL при ошибке');
        $this->assertSame(4, (int) $row->attempts, 'attempts должен увеличиться на 1');
        $this->assertSame('Redis connection refused', $row->last_error);
    }

    // -------------------------------------------------------------------------
    // Тест 3: exponential backoff при attempts >= 10
    // -------------------------------------------------------------------------

    public function test_exponential_backoff_applied_when_attempts_reaches_10(): void
    {
        // Arrange: EventBus всегда бросает исключение.
        $this->app->bind(EventBusInterface::class, fn () => new class implements EventBusInterface
        {
            public function publish(EventEnvelope $envelope): void
            {
                throw new RuntimeException('transport error');
            }
        });

        // Строка с attempts = 9 — после ошибки станет 10, backoff должен применяться.
        $id = $this->insertOutboxRow(['id' => 'backoff-001', 'attempts' => 9]);

        $before = Carbon::now('UTC');

        // Act
        $this->artisan('integration:publish-outbox')->assertExitCode(0);

        // Assert
        $row = DB::table('integration_outbox')->where('id', $id)->first();

        $this->assertSame(10, (int) $row->attempts);
        $this->assertNotNull($row->available_at);

        // При attempts = 10: backoff = min(2^10, 3600) = min(1024, 3600) = 1024 сек.
        $expectedBackoff = min(2 ** 10, 3600); // 1024
        $availableAt = Carbon::parse($row->available_at, 'UTC');

        // available_at должен быть примерно now + 1024 сек (допуск ±5 сек).
        $this->assertGreaterThanOrEqual(
            $before->copy()->addSeconds($expectedBackoff - 5)->timestamp,
            $availableAt->timestamp,
            'available_at должен быть не раньше now + backoff - 5s',
        );
        $this->assertLessThanOrEqual(
            $before->copy()->addSeconds($expectedBackoff + 5)->timestamp,
            $availableAt->timestamp,
            'available_at должен быть не позже now + backoff + 5s',
        );
    }

    // -------------------------------------------------------------------------
    // Тест 4: строки с available_at > now() пропускаются
    // -------------------------------------------------------------------------

    public function test_skips_rows_with_available_at_in_future(): void
    {
        // Arrange: NullEventBus — публикация всегда успешна.
        $this->app->bind(EventBusInterface::class, fn () => new class implements EventBusInterface
        {
            public function publish(EventEnvelope $envelope): void
            {
                // успех
            }
        });

        $futureTime = Carbon::now('UTC')->addMinutes(5)->format('Y-m-d H:i:s');
        $pastTime = Carbon::now('UTC')->subMinutes(1)->format('Y-m-d H:i:s');

        $idFuture = $this->insertOutboxRow(['id' => 'future-001', 'available_at' => $futureTime]);
        $idPast = $this->insertOutboxRow(['id' => 'past-001', 'available_at' => $pastTime]);

        // Act
        $this->artisan('integration:publish-outbox')->assertExitCode(0);

        // Assert: только past-строка опубликована.
        $rowFuture = DB::table('integration_outbox')->where('id', $idFuture)->first();
        $rowPast = DB::table('integration_outbox')->where('id', $idPast)->first();

        $this->assertNull($rowFuture->published_at, 'Строка с future available_at не должна публиковаться');
        $this->assertNotNull($rowPast->published_at, 'Строка с past available_at должна публиковаться');
    }

    // -------------------------------------------------------------------------
    // Тест 5: уже опубликованные строки не обрабатываются повторно
    // -------------------------------------------------------------------------

    public function test_already_published_rows_are_not_reprocessed(): void
    {
        // Используем вспомогательный класс со статическим счётчиком.
        OutboxPublishCounter::reset();
        $this->app->bind(EventBusInterface::class, fn () => new OutboxPublishCounter);

        $publishedAt = Carbon::now('UTC')->subMinutes(10)->format('Y-m-d H:i:s');
        $this->insertOutboxRow([
            'id' => 'already-published',
            'published_at' => $publishedAt,
        ]);

        // Act
        $this->artisan('integration:publish-outbox')->assertExitCode(0);

        // Assert: EventBus не вызывался.
        $this->assertSame(0, OutboxPublishCounter::$count, 'Уже опубликованные строки не должны обрабатываться');
    }

    // -------------------------------------------------------------------------
    // Тест 6: backoff НЕ применяется при attempts < 10
    // -------------------------------------------------------------------------

    public function test_no_backoff_when_attempts_below_10(): void
    {
        $this->app->bind(EventBusInterface::class, fn () => new class implements EventBusInterface
        {
            public function publish(EventEnvelope $envelope): void
            {
                throw new RuntimeException('error');
            }
        });

        $originalAvailableAt = Carbon::now('UTC')->subMinutes(1)->format('Y-m-d H:i:s');
        $id = $this->insertOutboxRow([
            'id' => 'no-backoff-001',
            'attempts' => 5,
            'available_at' => $originalAvailableAt,
        ]);

        // Act
        $this->artisan('integration:publish-outbox')->assertExitCode(0);

        // Assert: available_at не изменился (backoff не применяется при attempts < 10).
        $row = DB::table('integration_outbox')->where('id', $id)->first();

        $this->assertSame(6, (int) $row->attempts);
        $this->assertSame(
            Carbon::parse($originalAvailableAt)->format('Y-m-d H:i:s'),
            Carbon::parse($row->available_at)->format('Y-m-d H:i:s'),
            'available_at не должен меняться при attempts < 10',
        );
    }
}

/**
 * Вспомогательный EventBus со статическим счётчиком вызовов publish().
 * Используется вместо замыканий с &ref (не поддерживаются в анонимных классах PHP).
 */
class OutboxPublishCounter implements EventBusInterface
{
    public static int $count = 0;

    public static function reset(): void
    {
        self::$count = 0;
    }

    public function publish(EventEnvelope $envelope): void
    {
        self::$count++;
    }
}
