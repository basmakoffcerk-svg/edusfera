<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Contracts\Events\EventActor;
use App\Contracts\Events\EventAggregate;
use App\Contracts\Events\EventEnvelope;
use PHPUnit\Framework\TestCase;

/**
 * Покрывает требование 8.1: EventEnvelope содержит все поля конверта и
 * сериализуется в snake_case-форму, совпадающую с примером из дизайна (3.4).
 */
class EventEnvelopeTest extends TestCase
{
    private function envelope(): EventEnvelope
    {
        return new EventEnvelope(
            id: '01890af0-0000-7000-8000-000000000000',
            type: 'lesson.completed',
            version: 1,
            occurredAt: '2025-04-15T10:23:45+00:00',
            tenant: 'edusfera',
            traceId: 'req_abc123',
            actor: new EventActor('user', 42, 'tutor'),
            aggregate: new EventAggregate('lesson', 1234),
            payload: ['lesson_id' => 1234],
        );
    }

    public function test_event_type_combines_type_and_version(): void
    {
        $this->assertSame('lesson.completed.v1', $this->envelope()->eventType());
    }

    public function test_to_array_produces_snake_case_envelope(): void
    {
        $this->assertSame([
            'id' => '01890af0-0000-7000-8000-000000000000',
            'type' => 'lesson.completed',
            'version' => 1,
            'occurred_at' => '2025-04-15T10:23:45+00:00',
            'tenant' => 'edusfera',
            'trace_id' => 'req_abc123',
            'actor' => ['type' => 'user', 'id' => 42, 'role' => 'tutor'],
            'aggregate' => ['type' => 'lesson', 'id' => 1234],
            'payload' => ['lesson_id' => 1234],
        ], $this->envelope()->toArray());
    }

    public function test_json_serialize_matches_to_array(): void
    {
        $envelope = $this->envelope();

        $this->assertSame($envelope->toArray(), $envelope->jsonSerialize());
    }

    public function test_actor_omits_role_when_absent(): void
    {
        $actor = new EventActor('system', 'cron');

        $this->assertSame(['type' => 'system', 'id' => 'cron'], $actor->toArray());
    }
}
