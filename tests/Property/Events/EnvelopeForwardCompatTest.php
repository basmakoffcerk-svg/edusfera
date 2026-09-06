<?php

declare(strict_types=1);

namespace Tests\Property\Events;

use App\Contracts\Events\EventActor;
use App\Domain\Shared\Events\EventEnvelopeFactory;
use App\Integrations\Outbox\InvalidEventPayloadException;
use App\Integrations\Outbox\OutboxRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-based проверка свойства P8 (design.md, раздел 6).
 *
 * P8. Event envelope сохраняет совместимость в рамках major version:
 *   ∀ e with e.version = N:
 *       ∀ consumer C built for version N: parse(e, schema_N) succeeds
 *   Поля могут добавляться (forward compat), но не удаляться/менять тип.
 *
 * Свойство проверяется вручную (без eris/Pest): детерминированный цикл из
 * ≥100 итераций (mt_srand для воспроизводимости контрпримеров).
 *
 *   Часть A (≥60) — forward compatibility: к валидному payload lesson.completed.v1
 *     добавляются произвольные НЕИЗВЕСТНЫЕ поля; append() не бросает исключение
 *     (additionalProperties:true), запись появляется в integration_outbox, а
 *     consumer версии 1 успешно читает все обязательные поля без искажений.
 *   Часть B (≥40) — обратное: удаление любого обязательного поля (или смена его
 *     типа) приводит к InvalidEventPayloadException, запись в outbox не создаётся.
 */
final class EnvelopeForwardCompatTest extends TestCase
{
    use RefreshDatabase;

    private const SEED = 20240608;

    /** Часть A: forward compatibility (требование задачи — ≥60). */
    private const PART_A_ITERATIONS = 70;

    /** Часть B: удаление/смена типа обязательного поля (требование задачи — ≥40). */
    private const PART_B_ITERATIONS = 50;

    /**
     * Обязательные поля схемы docs/events/lesson.completed.v1.json.
     *
     * @var list<string>
     */
    private const REQUIRED_FIELDS = [
        'lesson_id',
        'tutor_id',
        'student_id',
        'started_at',
        'ended_at',
        'package_code',
    ];

    /**
     * P8 — Часть A: добавление произвольных неизвестных полей в payload сохраняет
     * валидность (forward compat), запись попадает в outbox, consumer версии 1
     * корректно читает все обязательные поля.
     *
     * Feature: microservices-foundation, Property P8: envelope forward compatibility within major version
     *
     * **Validates: Requirements 8.3, 8.5**
     */
    public function test_unknown_payload_fields_preserve_forward_compatibility(): void
    {
        mt_srand(self::SEED);

        $factory = $this->app->make(EventEnvelopeFactory::class);
        $repository = $this->app->make(OutboxRepository::class);

        // «Consumer версии 1»: читает ТОЛЬКО обязательные поля схемы v1,
        // игнорируя любые добавленные неизвестные поля (forward compat).
        $consumerV1 = static function (array $payload): array {
            $read = [];
            foreach (self::REQUIRED_FIELDS as $field) {
                $read[$field] = $payload[$field];
            }

            return $read;
        };

        for ($iteration = 1; $iteration <= self::PART_A_ITERATIONS; $iteration++) {
            DB::table(OutboxRepository::TABLE)->delete();

            $basePayload = $this->makeBasePayload();

            // 1..6 неизвестных полей со случайными именами и значениями
            // (скаляры, массивы, вложенные объекты). Имена не пересекаются
            // с обязательными — гарантируется префиксом.
            $extraFields = $this->makeUnknownFields(mt_rand(1, 6), $iteration);
            $payload = $basePayload + $extraFields;

            $lessonId = $basePayload['lesson_id'];

            $envelope = $factory->make(
                type: 'lesson.completed',
                version: 1,
                aggregateType: 'lesson',
                aggregateId: $lessonId,
                payload: $payload,
                actor: new EventActor('user', $basePayload['tutor_id'], 'tutor'),
            );

            $context = sprintf(
                'PART A iteration=%d seed=%d extras=%s',
                $iteration,
                self::SEED,
                implode(',', array_keys($extraFields)),
            );

            // --- Утверждение 1: append не бросает (additionalProperties:true) ---
            try {
                $repository->append($envelope);
            } catch (InvalidEventPayloadException $e) {
                $this->fail(sprintf(
                    'Добавление неизвестных полей не должно ломать валидацию схемы v1, '
                    .'но получено InvalidEventPayloadException: %s. %s',
                    implode('; ', $e->errors),
                    $context,
                ));
            }

            // --- Утверждение 2: запись появилась в integration_outbox ---
            $this->assertSame(
                1,
                DB::table(OutboxRepository::TABLE)->where('id', $envelope->id)->count(),
                "Валидный forward-compatible envelope должен попасть в outbox. $context",
            );

            // --- Утверждение 3: consumer v1 читает обязательные поля без искажений ---
            $this->assertSame(
                $consumerV1($basePayload),
                $consumerV1($payload),
                'Consumer версии 1 должен прочитать те же обязательные значения, '
                ."несмотря на добавленные неизвестные поля. $context",
            );

            // Дополнительно: значения обязательных полей действительно сохранены
            // в сериализованном конверте outbox без искажения добавленными полями.
            $storedPayload = $this->storedPayload($envelope->id);
            foreach (self::REQUIRED_FIELDS as $field) {
                $this->assertSame(
                    $basePayload[$field],
                    $storedPayload[$field],
                    "Обязательное поле '$field' должно сохраниться без изменений в outbox. $context",
                );
            }
        }
    }

    /**
     * P8 — Часть B: удаление любого обязательного поля (или смена его типа)
     * приводит к ошибке валидации схемы и не создаёт запись в outbox.
     *
     * Feature: microservices-foundation, Property P8: envelope forward compatibility within major version
     *
     * **Validates: Requirements 8.4, 8.5**
     */
    public function test_missing_or_retyped_required_field_fails_schema_validation(): void
    {
        // Отдельный сид от Части A, чтобы итерации не повторяли тот же поток.
        mt_srand(self::SEED + 1);

        $factory = $this->app->make(EventEnvelopeFactory::class);
        $repository = $this->app->make(OutboxRepository::class);

        for ($iteration = 1; $iteration <= self::PART_B_ITERATIONS; $iteration++) {
            // --- Подсценарий 1: удаление случайного обязательного поля (8.4) ---
            DB::table(OutboxRepository::TABLE)->delete();

            $payload = $this->makeBasePayload();
            $removed = self::REQUIRED_FIELDS[mt_rand(0, count(self::REQUIRED_FIELDS) - 1)];
            unset($payload[$removed]);

            $envelope = $factory->make(
                type: 'lesson.completed',
                version: 1,
                aggregateType: 'lesson',
                aggregateId: mt_rand(1, 1_000_000),
                payload: $payload,
                actor: new EventActor('system', 'cron'),
            );

            $context = sprintf(
                'PART B iteration=%d seed=%d removedField=%s',
                $iteration,
                self::SEED + 1,
                $removed,
            );

            $threw = false;
            try {
                $repository->append($envelope);
            } catch (InvalidEventPayloadException $e) {
                $threw = true;
                $this->assertSame('lesson.completed.v1', $e->eventType, "eventType в исключении. $context");
                $this->assertNotEmpty($e->errors, "Исключение должно нести ошибки схемы. $context");
            }

            $this->assertTrue(
                $threw,
                "Удаление обязательного поля '$removed' должно бросать InvalidEventPayloadException. $context",
            );
            $this->assertSame(
                0,
                DB::table(OutboxRepository::TABLE)->count(),
                "При невалидном payload запись в outbox не создаётся. $context",
            );

            // --- Подсценарий 2: смена ТИПА обязательного поля (8.5) ---
            DB::table(OutboxRepository::TABLE)->delete();

            $retypedPayload = $this->makeBasePayload();
            $retypedField = self::REQUIRED_FIELDS[mt_rand(0, count(self::REQUIRED_FIELDS) - 1)];
            $retypedPayload[$retypedField] = $this->wrongTypedValue($retypedField);

            $retypedEnvelope = $factory->make(
                type: 'lesson.completed',
                version: 1,
                aggregateType: 'lesson',
                aggregateId: mt_rand(1, 1_000_000),
                payload: $retypedPayload,
                actor: new EventActor('system', 'cron'),
            );

            $retypeContext = sprintf(
                'PART B iteration=%d seed=%d retypedField=%s',
                $iteration,
                self::SEED + 1,
                $retypedField,
            );

            $retypeThrew = false;
            try {
                $repository->append($retypedEnvelope);
            } catch (InvalidEventPayloadException $e) {
                $retypeThrew = true;
                $this->assertNotEmpty($e->errors, "Исключение смены типа должно нести ошибки схемы. $retypeContext");
            }

            $this->assertTrue(
                $retypeThrew,
                "Смена типа обязательного поля '$retypedField' должна бросать InvalidEventPayloadException. $retypeContext",
            );
            $this->assertSame(
                0,
                DB::table(OutboxRepository::TABLE)->count(),
                "При смене типа обязательного поля запись в outbox не создаётся. $retypeContext",
            );
        }
    }

    /**
     * Сгенерировать валидный base payload lesson.completed.v1 с корректными типами.
     *
     * @return array<string, mixed>
     */
    private function makeBasePayload(): array
    {
        $startedAt = Carbon::now('UTC')
            ->subDays(mt_rand(0, 30))
            ->subMinutes(mt_rand(0, 720));
        $endedAt = $startedAt->copy()->addMinutes(mt_rand(30, 120));

        return [
            'lesson_id' => mt_rand(1, 1_000_000),
            'tutor_id' => mt_rand(1, 100_000),
            'student_id' => mt_rand(1, 100_000),
            'started_at' => $startedAt->toIso8601String(),
            'ended_at' => $endedAt->toIso8601String(),
            'package_code' => ['single', 'pack_4', 'pack_8'][mt_rand(0, 2)],
        ];
    }

    /**
     * Сгенерировать набор НЕИЗВЕСТНЫХ полей, имена которых гарантированно не
     * пересекаются с обязательными (префикс ext_) — значения разных форм:
     * скаляры, массивы, вложенные объекты.
     *
     * @return array<string, mixed>
     */
    private function makeUnknownFields(int $count, int $iteration): array
    {
        $fields = [];

        for ($i = 0; $i < $count; $i++) {
            // Уникальное имя с префиксом ext_ — не может совпасть с required.
            $name = sprintf('ext_%d_%d_%d', $iteration, $i, mt_rand(0, 1_000_000));
            $fields[$name] = $this->randomValue(0);
        }

        return $fields;
    }

    /**
     * Случайное JSON-сериализуемое значение: скаляр, массив (list) или вложенный
     * объект (assoc). Глубина ограничена, чтобы payload оставался компактным.
     */
    private function randomValue(int $depth): mixed
    {
        $kinds = $depth >= 2
            ? ['int', 'string', 'bool', 'float', 'null']
            : ['int', 'string', 'bool', 'float', 'null', 'list', 'object'];

        $kind = $kinds[mt_rand(0, count($kinds) - 1)];

        return match ($kind) {
            'int' => mt_rand(-1_000_000, 1_000_000),
            'string' => 'val_'.mt_rand(0, 1_000_000),
            'bool' => mt_rand(0, 1) === 1,
            'float' => mt_rand(0, 1_000_000) / 100.0,
            'null' => null,
            'list' => array_map(
                fn (): mixed => $this->randomValue($depth + 1),
                range(0, mt_rand(0, 3)),
            ),
            'object' => $this->randomObject($depth + 1),
            default => null,
        };
    }

    /**
     * Случайный вложенный объект (assoc array) со случайными неизвестными ключами.
     *
     * @return array<string, mixed>
     */
    private function randomObject(int $depth): array
    {
        $object = [];
        $keys = mt_rand(1, 3);

        for ($i = 0; $i < $keys; $i++) {
            $object['k_'.mt_rand(0, 1_000_000)] = $this->randomValue($depth);
        }

        return $object;
    }

    /**
     * Значение НЕВЕРНОГО типа для обязательного поля (требование 8.5 — тип не
     * меняется в рамках major version: строка вместо integer, число вместо
     * date-time-строки и т.п.).
     */
    private function wrongTypedValue(string $field): mixed
    {
        return match ($field) {
            // integer-поля → строка
            'lesson_id', 'tutor_id', 'student_id' => 'not-an-integer-'.mt_rand(1, 999),
            // string date-time → integer
            'started_at', 'ended_at' => mt_rand(1, 1_000_000),
            // string → integer
            'package_code' => mt_rand(1, 1_000_000),
            default => null,
        };
    }

    /**
     * Прочитать payload из сериализованного конверта строки outbox.
     *
     * @return array<string, mixed>
     */
    private function storedPayload(string $id): array
    {
        $row = DB::table(OutboxRepository::TABLE)->where('id', $id)->first();
        $envelope = json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR);

        /** @var array<string, mixed> $payload */
        $payload = $envelope['payload'];

        return $payload;
    }
}
