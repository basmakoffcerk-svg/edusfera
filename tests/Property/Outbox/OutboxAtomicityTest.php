<?php

declare(strict_types=1);

namespace Tests\Property\Outbox;

use App\Contracts\Events\EventEnvelope;
use App\Domain\Shared\Events\EventEnvelopeFactory;
use App\Integrations\Outbox\OutboxRepository;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Property-based проверка свойства P6 (design.md, раздел 6).
 *
 * P6. Outbox запись и доменное изменение атомарны:
 *   ∀ transaction T:
 *       committed(T)   ⟺ ( Δ ∈ DB ∧ e ∈ outbox )
 *       rolled_back(T) ⟹ ( Δ ∉ DB ∧ e ∉ outbox )
 *
 * Иными словами, доменное изменение (INSERT в `lessons`) и запись конверта в
 * `integration_outbox` фиксируются ровно вместе либо не фиксируются вовсе.
 * «Полусостояние» (урок есть, события нет, или наоборот) недопустимо.
 *
 * Так как `OutboxRepository::append()` НЕ открывает собственную транзакцию
 * (требование 7.3), он участвует в окружающей транзакции доменного сервиса —
 * именно это и обеспечивает атомарность (требование 7.4).
 *
 * Свойство проверяется вручную (без eris/Pest): детерминированный цикл из
 * ≥100 итераций со случайным выбором committed/aborted (mt_srand для
 * воспроизводимости контрпримеров).
 *
 * Feature: microservices-foundation, Property P6: outbox write and domain change are atomic
 */
final class OutboxAtomicityTest extends TestCase
{
    use RefreshDatabase;

    /** Число итераций property-теста (требование задачи: минимум 100). */
    private const ITERATIONS = 120;

    /** Фиксированный сид для воспроизводимости контрпримеров. */
    private const SEED = 20240615;

    private User $tutor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    /**
     * P6: для каждой транзакции доменное изменение (строка в `lessons`) и
     * запись конверта в `integration_outbox` появляются строго вместе при
     * commit и строго отсутствуют оба при rollback. Никогда не наблюдается
     * частичной фиксации.
     *
     * **Validates: Requirements 7.3, 7.4**
     */
    public function test_outbox_write_and_domain_change_are_atomic(): void
    {
        mt_srand(self::SEED);

        $factory = $this->app->make(EventEnvelopeFactory::class);
        $repository = $this->app->make(OutboxRepository::class);

        /** @var list<array{lessonId:int,eventId:string}> $committed */
        $committed = [];
        /** @var list<array{lessonId:int,eventId:string}> $aborted */
        $aborted = [];

        for ($iteration = 1; $iteration <= self::ITERATIONS; $iteration++) {
            $attributes = $this->makeLessonAttributes();

            // ≈50/50 выбор сценария фиксации/отката.
            $isCommitted = mt_rand(0, 1) === 1;

            $lessonId = null;
            $eventId = null;

            if ($isCommitted) {
                // COMMITTED: доменное изменение + append в одной транзакции.
                DB::transaction(function () use (
                    $repository,
                    $factory,
                    $attributes,
                    &$lessonId,
                    &$eventId,
                ): void {
                    $lesson = Lesson::forceCreate($attributes);
                    $envelope = $this->makeEnvelope($factory, $lesson);
                    $repository->append($envelope);

                    $lessonId = $lesson->id;
                    $eventId = $envelope->id;
                });
            } else {
                // ABORTED: те же операции, но искусственное исключение ПОСЛЕ
                // INSERT-ов и ДО COMMIT — транзакция откатывается целиком.
                try {
                    DB::transaction(function () use (
                        $repository,
                        $factory,
                        $attributes,
                        &$lessonId,
                        &$eventId,
                    ): void {
                        $lesson = Lesson::forceCreate($attributes);
                        $envelope = $this->makeEnvelope($factory, $lesson);
                        $repository->append($envelope);

                        $lessonId = $lesson->id;
                        $eventId = $envelope->id;

                        throw new RuntimeException('simulated failure before commit');
                    });

                    $this->fail('Транзакция должна была откатиться из-за исключения.');
                } catch (RuntimeException) {
                    // Ожидаемый откат — продолжаем проверки.
                }
            }

            $this->assertNotNull($lessonId, "lesson_id должен быть присвоен внутри транзакции. iteration=$iteration");
            $this->assertNotNull($eventId, "event_id должен быть присвоен внутри транзакции. iteration=$iteration");

            $context = sprintf(
                'iteration=%d seed=%d scenario=%s lesson_id=%s event_id=%s',
                $iteration,
                self::SEED,
                $isCommitted ? 'committed' : 'aborted',
                (string) $lessonId,
                (string) $eventId,
            );

            $lessonCount = DB::table('lessons')->where('id', $lessonId)->count();
            $outboxCount = DB::table(OutboxRepository::TABLE)->where('id', $eventId)->count();

            if ($isCommitted) {
                // committed(T) ⟺ Δ ∈ DB ∧ e ∈ outbox — ровно по одной строке.
                $this->assertSame(1, $lessonCount, "После commit должна существовать ровно 1 строка lessons. $context");
                $this->assertSame(1, $outboxCount, "После commit должна существовать ровно 1 строка integration_outbox. $context");

                $committed[] = ['lessonId' => $lessonId, 'eventId' => $eventId];
            } else {
                // rolled_back(T) ⟹ Δ ∉ DB ∧ e ∉ outbox — ни одной строки.
                // Проверяем немедленно после rollback: lesson_id ещё гарантированно
                // отсутствует (id может быть переиспользован SQLite позже).
                $this->assertSame(0, $lessonCount, "После rollback не должно быть строк lessons (нет частичной фиксации). $context");
                $this->assertSame(0, $outboxCount, "После rollback не должно быть строк integration_outbox (нет частичной фиксации). $context");

                $aborted[] = ['lessonId' => $lessonId, 'eventId' => $eventId];
            }
        }

        // Sanity: оба сценария реально отработали (тест покрывает обе ветки).
        $this->assertNotEmpty($committed, 'За прогон должны были встретиться committed-транзакции.');
        $this->assertNotEmpty($aborted, 'За прогон должны были встретиться aborted-транзакции.');

        // --- Глобальный инвариант атомарности по event_id (UUID, не переиспользуется) ---
        // committed: и доменное изменение, и событие присутствуют вместе.
        foreach ($committed as $pair) {
            $this->assertTrue(
                DB::table('lessons')->where('id', $pair['lessonId'])->exists(),
                "Committed lesson {$pair['lessonId']} должен присутствовать (атомарно с outbox).",
            );
            $this->assertTrue(
                DB::table(OutboxRepository::TABLE)->where('id', $pair['eventId'])->exists(),
                "Committed событие {$pair['eventId']} должно присутствовать (атомарно с lesson).",
            );
        }

        // aborted: событие никогда не зафиксировано (нет частичной фиксации).
        foreach ($aborted as $pair) {
            $this->assertFalse(
                DB::table(OutboxRepository::TABLE)->where('id', $pair['eventId'])->exists(),
                "Aborted событие {$pair['eventId']} не должно присутствовать в integration_outbox.",
            );
        }

        // Перекрёстная проверка: множества committed/aborted event_id не пересекаются.
        $committedEventIds = array_map(static fn (array $p): string => $p['eventId'], $committed);
        $abortedEventIds = array_map(static fn (array $p): string => $p['eventId'], $aborted);

        $this->assertEmpty(
            array_intersect($committedEventIds, $abortedEventIds),
            'event_id committed и aborted транзакций не должны пересекаться.',
        );
    }

    /**
     * Сгенерировать валидный набор атрибутов урока со случайными значениями.
     *
     * @return array<string, mixed>
     */
    private function makeLessonAttributes(): array
    {
        $startTime = Carbon::now('UTC')
            ->subDays(mt_rand(0, 30))
            ->subMinutes(mt_rand(0, 720));
        $duration = mt_rand(30, 120);
        $endTime = $startTime->copy()->addMinutes($duration);

        $price = mt_rand(500, 5000);
        $commission = (int) round($price * 0.2);
        $net = $price - $commission;

        $packageCode = ['single', 'pack_4', 'pack_8'][mt_rand(0, 2)];

        return [
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $duration,
            'price' => $price,
            'platform_commission' => $commission,
            'net_amount' => $net,
            'package_code' => $packageCode,
        ];
    }

    /**
     * Построить конверт lesson.completed.v1 для созданного урока.
     * event_id = envelope->id, aggregate_id = lesson_id.
     */
    private function makeEnvelope(EventEnvelopeFactory $factory, Lesson $lesson): EventEnvelope
    {
        return $factory->lessonCompleted($lesson);
    }
}
