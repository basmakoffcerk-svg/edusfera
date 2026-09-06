<?php

declare(strict_types=1);

namespace Tests\Unit\EventBus;

use App\Contracts\Events\EventActor;
use App\Contracts\Events\EventAggregate;
use App\Contracts\Events\EventEnvelope;
use App\Integrations\EventBus\NullEventBus;
use App\Integrations\EventBus\RedisStreamsEventBus;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Unit-тесты для EventBusInterface-реализаций.
 *
 * Покрывает требования 9.2 (RedisStreamsEventBus) и 9.3 (NullEventBus).
 */
class EventBusTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEnvelope(
        string $aggregateType = 'lesson',
        string|int $aggregateId = 1234,
    ): EventEnvelope {
        return new EventEnvelope(
            id: '01890af0-0000-7000-8000-000000000001',
            type: 'lesson.completed',
            version: 1,
            occurredAt: '2025-04-15T10:23:45+00:00',
            tenant: 'edusfera',
            traceId: 'req_test_001',
            actor: new EventActor('user', 42, 'tutor'),
            aggregate: new EventAggregate($aggregateType, $aggregateId),
            payload: ['lesson_id' => $aggregateId],
        );
    }

    // -------------------------------------------------------------------------
    // NullEventBus — требование 9.3
    // -------------------------------------------------------------------------

    public function test_null_event_bus_does_not_throw_on_publish(): void
    {
        $bus = new NullEventBus;

        // Не должно бросать никаких исключений
        $bus->publish($this->makeEnvelope());

        $this->assertTrue(true); // если дошли сюда — тест прошёл
    }

    public function test_null_event_bus_with_logging_does_not_throw(): void
    {
        $bus = new NullEventBus(logEnabled: true);

        // Даже с включённым логированием не должно бросать исключений
        $bus->publish($this->makeEnvelope());

        $this->assertTrue(true);
    }

    public function test_null_event_bus_accepts_various_aggregate_types(): void
    {
        $bus = new NullEventBus;

        foreach (['lesson', 'payment', 'homework', 'user'] as $type) {
            $bus->publish($this->makeEnvelope(aggregateType: $type));
        }

        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // RedisStreamsEventBus — требование 9.2
    // -------------------------------------------------------------------------

    public function test_redis_streams_event_bus_calls_xadd_with_correct_stream_key(): void
    {
        $envelope = $this->makeEnvelope(aggregateType: 'lesson', aggregateId: 1234);

        $expectedPayload = json_encode(
            $envelope->toArray(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        Redis::shouldReceive('xAdd')
            ->once()
            ->with('topic:lesson', '*', ['payload' => $expectedPayload]);

        $bus = new RedisStreamsEventBus;
        $bus->publish($envelope);
    }

    public function test_redis_streams_event_bus_uses_aggregate_type_in_stream_key(): void
    {
        foreach (['lesson', 'payment', 'homework'] as $type) {
            $envelope = $this->makeEnvelope(aggregateType: $type);

            $expectedPayload = json_encode(
                $envelope->toArray(),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            Redis::shouldReceive('xAdd')
                ->once()
                ->with('topic:'.$type, '*', ['payload' => $expectedPayload]);

            $bus = new RedisStreamsEventBus;
            $bus->publish($envelope);
        }
    }

    public function test_redis_streams_event_bus_uses_custom_stream_prefix(): void
    {
        $envelope = $this->makeEnvelope(aggregateType: 'lesson');

        $expectedPayload = json_encode(
            $envelope->toArray(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        Redis::shouldReceive('xAdd')
            ->once()
            ->with('events:lesson', '*', ['payload' => $expectedPayload]);

        $bus = new RedisStreamsEventBus(streamPrefix: 'events:');
        $bus->publish($envelope);
    }

    public function test_redis_streams_event_bus_payload_contains_full_envelope(): void
    {
        $envelope = $this->makeEnvelope();

        $capturedArgs = null;

        Redis::shouldReceive('xAdd')
            ->once()
            ->withArgs(function (string $stream, string $id, array $fields) use (&$capturedArgs) {
                $capturedArgs = $fields;

                return true;
            });

        $bus = new RedisStreamsEventBus;
        $bus->publish($envelope);

        $this->assertNotNull($capturedArgs);
        $this->assertArrayHasKey('payload', $capturedArgs);

        $decoded = json_decode($capturedArgs['payload'], true);

        $this->assertIsArray($decoded);
        $this->assertSame($envelope->id, $decoded['id']);
        $this->assertSame($envelope->type, $decoded['type']);
        $this->assertSame($envelope->version, $decoded['version']);
        $this->assertSame($envelope->tenant, $decoded['tenant']);
        $this->assertSame($envelope->traceId, $decoded['trace_id']);
        $this->assertArrayHasKey('actor', $decoded);
        $this->assertArrayHasKey('aggregate', $decoded);
        $this->assertArrayHasKey('payload', $decoded);
    }

    public function test_redis_streams_event_bus_rethrows_redis_exception(): void
    {
        $envelope = $this->makeEnvelope();

        Redis::shouldReceive('xAdd')
            ->once()
            ->andThrow(new \RuntimeException('Redis connection refused'));

        $bus = new RedisStreamsEventBus;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Redis connection refused');

        $bus->publish($envelope);
    }
}
