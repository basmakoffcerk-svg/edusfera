<?php

declare(strict_types=1);

namespace Tests\Feature\Outbox;

use App\Contracts\Events\EventActor;
use App\Domain\Shared\Events\EventEnvelopeFactory;
use App\Integrations\Outbox\InvalidEventPayloadException;
use App\Integrations\Outbox\OutboxRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Покрывает требования 8.1–8.4, 8.6, 7.5: OutboxRepository::append валидирует
 * payload по JSON Schema lesson.completed.v1, вставляет строку с полным
 * сериализованным envelope и бросает InvalidEventPayloadException без вставки
 * при невалидном payload.
 */
class OutboxRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function factory(): EventEnvelopeFactory
    {
        return $this->app->make(EventEnvelopeFactory::class);
    }

    private function repository(): OutboxRepository
    {
        return $this->app->make(OutboxRepository::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'lesson_id' => 1234,
            'tutor_id' => 42,
            'student_id' => 99,
            'started_at' => '2025-04-15T09:00:00+00:00',
            'ended_at' => '2025-04-15T10:00:00+00:00',
            'package_code' => 'pack_4',
        ];
    }

    public function test_append_inserts_row_for_valid_payload(): void
    {
        $envelope = $this->factory()->make(
            type: 'lesson.completed',
            version: 1,
            aggregateType: 'lesson',
            aggregateId: 1234,
            payload: $this->validPayload(),
            actor: new EventActor('user', 42, 'tutor'),
        );

        $this->repository()->append($envelope);

        $this->assertDatabaseCount('integration_outbox', 1);

        $row = DB::table('integration_outbox')->first();

        $this->assertSame($envelope->id, $row->id);
        $this->assertSame('lesson.completed.v1', $row->event_type);
        $this->assertSame('lesson', $row->aggregate_type);
        $this->assertSame('1234', $row->aggregate_id);
        $this->assertNull($row->published_at);
        $this->assertSame(0, (int) $row->attempts);
        $this->assertNull($row->last_error);
        $this->assertNotNull($row->available_at);
    }

    public function test_append_throws_and_inserts_nothing_for_invalid_payload(): void
    {
        // Отсутствует обязательное поле package_code.
        $payload = $this->validPayload();
        unset($payload['package_code']);

        $envelope = $this->factory()->make(
            type: 'lesson.completed',
            version: 1,
            aggregateType: 'lesson',
            aggregateId: 1234,
            payload: $payload,
            actor: new EventActor('user', 42, 'tutor'),
        );

        try {
            $this->repository()->append($envelope);
            $this->fail('Expected InvalidEventPayloadException was not thrown.');
        } catch (InvalidEventPayloadException $e) {
            $this->assertSame('lesson.completed.v1', $e->eventType);
            $this->assertNotEmpty($e->errors);
        }

        $this->assertDatabaseCount('integration_outbox', 0);
    }

    public function test_append_rejects_payload_with_wrong_type(): void
    {
        // lesson_id должен быть integer, а не строкой (требование 8.5 — тип не меняется).
        $payload = $this->validPayload();
        $payload['lesson_id'] = 'not-an-integer';

        $envelope = $this->factory()->make(
            type: 'lesson.completed',
            version: 1,
            aggregateType: 'lesson',
            aggregateId: 1234,
            payload: $payload,
            actor: new EventActor('user', 42, 'tutor'),
        );

        $this->expectException(InvalidEventPayloadException::class);

        try {
            $this->repository()->append($envelope);
        } finally {
            $this->assertDatabaseCount('integration_outbox', 0);
        }
    }

    public function test_appended_row_payload_round_trips_the_envelope(): void
    {
        $envelope = $this->factory()->make(
            type: 'lesson.completed',
            version: 1,
            aggregateType: 'lesson',
            aggregateId: 1234,
            payload: $this->validPayload(),
            actor: new EventActor('user', 42, 'tutor'),
        );

        $this->repository()->append($envelope);

        $row = DB::table('integration_outbox')->first();
        $stored = json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR);

        // Полный конверт сериализован в payload-столбец (требование 7.5).
        $this->assertSame($envelope->toArray(), $stored);
        $this->assertSame($envelope->id, $stored['id']);
        $this->assertSame('lesson.completed', $stored['type']);
        $this->assertSame(1, $stored['version']);
        $this->assertSame('edusfera', $stored['tenant']);
        $this->assertSame(['type' => 'user', 'id' => 42, 'role' => 'tutor'], $stored['actor']);
        $this->assertSame(['type' => 'lesson', 'id' => 1234], $stored['aggregate']);
        $this->assertSame($this->validPayload(), $stored['payload']);
    }

    public function test_append_allows_forward_compatible_extra_payload_fields(): void
    {
        // additionalProperties=true: неизвестные поля не ломают валидацию (требование 8.5).
        $payload = $this->validPayload();
        $payload['ai_summary'] = 'student improved on quadratic equations';

        $envelope = $this->factory()->make(
            type: 'lesson.completed',
            version: 1,
            aggregateType: 'lesson',
            aggregateId: 1234,
            payload: $payload,
            actor: new EventActor('user', 42, 'tutor'),
        );

        $this->repository()->append($envelope);

        $this->assertDatabaseCount('integration_outbox', 1);
    }
}
