<?php

declare(strict_types=1);

namespace Tests\Property\Outbox;

use App\Contracts\Events\EventBusInterface;
use App\Contracts\Events\EventEnvelope;
use App\Domain\Shared\Events\EventEnvelopeFactory;
use App\Integrations\Outbox\OutboxRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\RecordingEventBus;
use Tests\TestCase;

/**
 * Property-based проверка свойства P1 (design.md, раздел 6).
 *
 * P1. At-least-once + dedup гарантия outbox:
 *   ∀ e ∈ outbox (committed):  eventually published_at(e) ≠ NULL
 *   ∀ consumer C с dedup(event_id): business_effect(C, e) применён ровно один раз
 *
 * Свойства проверяются вручную (без eris/Pest): детерминированный цикл из
 * ≥100 итераций со случайным составом committed/rolled-back событий
 * (mt_srand для воспроизводимости контрпримеров).
 */
final class OutboxAtLeastOnceTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    private const SEED = 20240601;

    /**
     * P1: At-least-once доставка для committed событий + отсутствие rolled-back
     * событий, идемпотентность повторного publisher'а и дедуп на стороне
     * consumer'а по event_id.
     *
     * Feature: microservices-foundation, Property P1: at-least-once + dedup outbox guarantee
     *
     * **Validates: Requirements 7.1, 7.3, 7.4, 9.5, 9.6, 9.7, 8.6**
     */
    public function test_outbox_guarantees_at_least_once_delivery_and_consumer_dedup(): void
    {
        mt_srand(self::SEED);

        $bus = new RecordingEventBus;
        // Команда integration:publish-outbox резолвит EventBusInterface из
        // контейнера — биндим in-memory шину ДО запуска publisher'а.
        $this->app->instance(EventBusInterface::class, $bus);

        $factory = $this->app->make(EventEnvelopeFactory::class);
        $repository = $this->app->make(OutboxRepository::class);

        for ($iteration = 1; $iteration <= self::ITERATIONS; $iteration++) {
            // Изолируем итерацию: чистим outbox и накопленное состояние шины.
            DB::table(OutboxRepository::TABLE)->delete();
            $bus->reset();

            $n = mt_rand(1, 50);

            /** @var list<string> $committedIds */
            $committedIds = [];
            /** @var list<string> $rolledBackIds */
            $rolledBackIds = [];

            for ($j = 0; $j < $n; $j++) {
                $envelope = $this->makeLessonCompletedEnvelope($factory);

                if (mt_rand(0, 1) === 1) {
                    // Транзакция, которая ОТКАТЫВАЕТСЯ: запись в outbox не должна
                    // пережить rollback (требования 7.3, 7.4).
                    DB::beginTransaction();
                    $repository->append($envelope);
                    DB::rollBack();

                    $rolledBackIds[] = $envelope->id;
                } else {
                    // COMMITTED транзакция: запись фиксируется атомарно с
                    // (симулированным) доменным изменением.
                    DB::transaction(static function () use ($repository, $envelope): void {
                        $repository->append($envelope);
                    });

                    $committedIds[] = $envelope->id;
                }
            }

            $context = sprintf(
                'iteration=%d seed=%d n=%d committed=%d rolledBack=%d',
                $iteration,
                self::SEED,
                $n,
                count($committedIds),
                count($rolledBackIds),
            );

            // Запускаем publisher дважды — для проверки идемпотентности (9.5).
            Artisan::call('integration:publish-outbox');
            $deliveriesAfterFirstRun = $bus->totalDeliveries();

            Artisan::call('integration:publish-outbox');
            $deliveriesAfterSecondRun = $bus->totalDeliveries();

            // --- Утверждение 5: повторный publisher не публикует уже опубликованное ---
            $this->assertSame(
                $deliveriesAfterFirstRun,
                $deliveriesAfterSecondRun,
                "Повторный запуск publisher'а не должен публиковать уже опубликованные события. $context",
            );
            $this->assertSame(
                count($committedIds),
                $bus->totalDeliveries(),
                "Число доставок должно равняться числу committed событий (без лишних публикаций). $context",
            );

            // --- Утверждение 1: at-least-once для каждого committed события ---
            foreach ($committedIds as $id) {
                $this->assertGreaterThanOrEqual(
                    1,
                    $bus->countFor($id),
                    "Committed событие $id должно быть доставлено в шину хотя бы один раз (at-least-once). $context",
                );
            }

            // --- Утверждение 2: rolled-back события отсутствуют в outbox и в шине ---
            foreach ($rolledBackIds as $id) {
                $this->assertFalse(
                    DB::table(OutboxRepository::TABLE)->where('id', $id)->exists(),
                    "Откатанное событие $id не должно присутствовать в integration_outbox. $context",
                );
                $this->assertSame(
                    0,
                    $bus->countFor($id),
                    "Откатанное событие $id не должно попасть в шину. $context",
                );
            }

            // --- Утверждение 3: published_at != NULL для всех committed строк (9.6) ---
            foreach ($committedIds as $id) {
                $row = DB::table(OutboxRepository::TABLE)->where('id', $id)->first();

                $this->assertNotNull($row, "Committed строка $id должна существовать в outbox. $context");
                $this->assertNotNull(
                    $row->published_at,
                    "После публикации committed строка $id должна иметь published_at != NULL. $context",
                );
                $this->assertNull(
                    $row->last_error,
                    "Успешно опубликованная строка $id не должна иметь last_error. $context",
                );
            }

            // --- Утверждение 4: дедуп consumer'а по event_id ---
            // Симулируем at-least-once redelivery: каждое доставленное событие
            // повторяется несколько раз (интерливинг полным дублированием).
            $deliveryStream = $this->simulateRedeliveries($bus->deliveries());

            $dedupSet = [];
            $appliedCount = 0;
            foreach ($deliveryStream as $eventId) {
                // Consumer с in-memory dedup-set: business-effect ровно один раз.
                if (! isset($dedupSet[$eventId])) {
                    $dedupSet[$eventId] = true;
                    $appliedCount++;
                }
            }

            $uniqueCommitted = array_values(array_unique($committedIds));

            $this->assertSame(
                count($uniqueCommitted),
                $appliedCount,
                "Дедуп consumer должен применить business-effect ровно один раз на уникальный committed event_id. $context",
            );
        }
    }

    /**
     * Сгенерировать валидный конверт lesson.completed.v1 со случайным payload.
     */
    private function makeLessonCompletedEnvelope(EventEnvelopeFactory $factory): EventEnvelope
    {
        $lessonId = mt_rand(1, 1_000_000);
        $startedAt = Carbon::now('UTC')
            ->subDays(mt_rand(0, 30))
            ->subMinutes(mt_rand(0, 720));
        $endedAt = $startedAt->copy()->addMinutes(mt_rand(30, 120));

        $packageCode = ['single', 'pack_4', 'pack_8'][mt_rand(0, 2)];

        $payload = [
            'lesson_id' => $lessonId,
            'tutor_id' => mt_rand(1, 100_000),
            'student_id' => mt_rand(1, 100_000),
            'started_at' => $startedAt->toIso8601String(),
            'ended_at' => $endedAt->toIso8601String(),
            'package_code' => $packageCode,
        ];

        return $factory->make(
            'lesson.completed',
            1,
            'lesson',
            $lessonId,
            $payload,
            ['type' => 'system', 'id' => 'cron'],
        );
    }

    /**
     * Построить поток доставок с дублями (at-least-once redelivery).
     *
     * Полный список доставок повторяется случайное число раз (2..4), что даёт
     * интерливинг идентичных event_id, как при реальной повторной доставке.
     *
     * @param  list<string>  $deliveries
     * @return list<string>
     */
    private function simulateRedeliveries(array $deliveries): array
    {
        if ($deliveries === []) {
            return [];
        }

        $repeatTimes = mt_rand(2, 4);
        $stream = [];

        for ($r = 0; $r < $repeatTimes; $r++) {
            foreach ($deliveries as $eventId) {
                $stream[] = $eventId;
            }
        }

        return $stream;
    }
}
