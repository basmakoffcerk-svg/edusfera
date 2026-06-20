<?php

declare(strict_types=1);

namespace Tests\Feature\Outbox;

use App\Domain\Shared\Events\EventEnvelopeFactory;
use App\Integrations\Outbox\OutboxRepository;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Покрывает требования 7.3, 7.4, 7.5, 8.6:
 * - lesson.completed.v1 записывается в integration_outbox атомарно с изменением статуса урока
 * - при rollback транзакции ни запись в outbox, ни изменение статуса не сохраняются
 * - payload содержит все обязательные поля
 */
class LessonCompletedEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Создать тестового репетитора и студента.
     *
     * @return array{tutor: User, student: User}
     */
    private function makeUsers(): array
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'phone' => '+375291000001']);
        $student = User::factory()->create(['role' => 'student', 'phone' => '+375291000002']);

        return ['tutor' => $tutor, 'student' => $student];
    }

    /**
     * Создать урок в статусе confirmed с end_time в прошлом.
     */
    private function makeCompletedLesson(User $tutor, User $student, string $packageCode = 'pack_4'): Lesson
    {
        return Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => Carbon::now('UTC')->subHours(2),
            'end_time' => Carbon::now('UTC')->subMinutes(5),
            'duration_minutes' => 60,
            'price' => '100.00',
            'platform_commission' => '15.00',
            'net_amount' => '85.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => $packageCode,
        ]);
    }

    /**
     * Тест 1: при успешном завершении урока строка в integration_outbox
     * появляется с event_type = 'lesson.completed.v1'.
     *
     * Requirements: 7.3, 7.4, 8.6
     */
    public function test_lesson_completed_event_is_written_to_outbox_on_success(): void
    {
        ['tutor' => $tutor, 'student' => $student] = $this->makeUsers();
        $lesson = $this->makeCompletedLesson($tutor, $student);

        // Мокаем PaymentService, чтобы не требовать финансовых фикстур
        $paymentMock = $this->createMock(PaymentService::class);
        $paymentMock->method('settleCompletedLesson');
        $this->app->instance(PaymentService::class, $paymentMock);

        $this->artisan('lessons:complete')->assertSuccessful();

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => Lesson::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseCount('integration_outbox', 1);

        $row = DB::table('integration_outbox')->first();
        $this->assertSame('lesson.completed.v1', $row->event_type);
        $this->assertSame('lesson', $row->aggregate_type);
        $this->assertSame((string) $lesson->id, $row->aggregate_id);
        $this->assertNull($row->published_at);
    }

    /**
     * Тест 2: payload содержит все обязательные поля согласно JSON Schema.
     *
     * Requirements: 7.5, 8.6
     */
    public function test_outbox_payload_contains_all_required_fields(): void
    {
        ['tutor' => $tutor, 'student' => $student] = $this->makeUsers();
        $lesson = $this->makeCompletedLesson($tutor, $student, 'pack_4');

        $paymentMock = $this->createMock(PaymentService::class);
        $paymentMock->method('settleCompletedLesson');
        $this->app->instance(PaymentService::class, $paymentMock);

        $this->artisan('lessons:complete')->assertSuccessful();

        $row = DB::table('integration_outbox')->first();
        $envelope = json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR);
        $payload = $envelope['payload'];

        // Все обязательные поля присутствуют
        $this->assertArrayHasKey('lesson_id', $payload);
        $this->assertArrayHasKey('tutor_id', $payload);
        $this->assertArrayHasKey('student_id', $payload);
        $this->assertArrayHasKey('started_at', $payload);
        $this->assertArrayHasKey('ended_at', $payload);
        $this->assertArrayHasKey('package_code', $payload);

        // Значения корректны
        $this->assertSame($lesson->id, $payload['lesson_id']);
        $this->assertSame($tutor->id, $payload['tutor_id']);
        $this->assertSame($student->id, $payload['student_id']);
        $this->assertSame('pack_4', $payload['package_code']);

        // started_at и ended_at — валидные ISO-8601 строки
        $this->assertNotEmpty($payload['started_at']);
        $this->assertNotEmpty($payload['ended_at']);
        $this->assertNotFalse(strtotime($payload['started_at']));
        $this->assertNotFalse(strtotime($payload['ended_at']));

        // Actor — системный (cron)
        $this->assertSame('system', $envelope['actor']['type']);
        $this->assertSame('cron', $envelope['actor']['id']);
    }

    /**
     * Тест 3: package_code = null → дефолт 'single' в payload события.
     *
     * Тестируем напрямую через фабрику, т.к. БД имеет NOT NULL constraint на package_code.
     *
     * Requirements: 8.6
     */
    public function test_null_package_code_defaults_to_single(): void
    {
        ['tutor' => $tutor, 'student' => $student] = $this->makeUsers();

        $factory = $this->app->make(EventEnvelopeFactory::class);

        // Создаём сохранённый урок, затем принудительно устанавливаем package_code = null
        $lesson = $this->makeCompletedLesson($tutor, $student, 'single');
        $lesson->package_code = null;

        $envelope = $factory->lessonCompleted($lesson);

        $this->assertSame('single', $envelope->payload['package_code']);
    }

    /**
     * Тест 4: при rollback транзакции запись в outbox отсутствует
     * И статус урока не изменился.
     *
     * Requirements: 7.3, 7.4
     */
    public function test_rollback_leaves_no_outbox_row_and_lesson_status_unchanged(): void
    {
        ['tutor' => $tutor, 'student' => $student] = $this->makeUsers();
        $lesson = $this->makeCompletedLesson($tutor, $student);

        $outboxRepository = $this->app->make(OutboxRepository::class);
        $envelopeFactory = $this->app->make(EventEnvelopeFactory::class);

        // Симулируем rollback: открываем транзакцию, делаем изменения, бросаем исключение
        try {
            DB::transaction(function () use ($lesson, $outboxRepository, $envelopeFactory): void {
                $lesson->update(['status' => Lesson::STATUS_COMPLETED]);
                $outboxRepository->append($envelopeFactory->lessonCompleted($lesson));

                // Искусственно откатываем транзакцию
                throw new \RuntimeException('Simulated rollback');
            });
        } catch (\RuntimeException) {
            // Ожидаемое исключение — транзакция откатилась
        }

        // Статус урока не изменился
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => Lesson::STATUS_CONFIRMED,
        ]);

        // Запись в outbox отсутствует
        $this->assertDatabaseCount('integration_outbox', 0);
    }

    /**
     * Тест 5: вне транзакции (без DB::transaction) запись в outbox появляется немедленно,
     * но при rollback внешней транзакции — исчезает.
     *
     * Дополнительная проверка атомарности (требование 7.4).
     */
    public function test_outbox_row_absent_when_outer_transaction_rolls_back(): void
    {
        ['tutor' => $tutor, 'student' => $student] = $this->makeUsers();
        $lesson = $this->makeCompletedLesson($tutor, $student);

        $outboxRepository = $this->app->make(OutboxRepository::class);
        $envelopeFactory = $this->app->make(EventEnvelopeFactory::class);

        // Начинаем транзакцию вручную
        DB::beginTransaction();

        try {
            $lesson->update(['status' => Lesson::STATUS_COMPLETED]);
            $outboxRepository->append($envelopeFactory->lessonCompleted($lesson));

            // Внутри транзакции запись видна в рамках той же транзакции
            $countInsideTransaction = DB::table('integration_outbox')->count();
            $this->assertSame(1, $countInsideTransaction);

            // Откатываем
            DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // После rollback — ни записи в outbox, ни изменения статуса
        $this->assertDatabaseCount('integration_outbox', 0);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => Lesson::STATUS_CONFIRMED,
        ]);
    }
}
