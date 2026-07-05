<?php

declare(strict_types=1);

namespace Tests\Feature\Reconciliation;

use App\Contracts\Integrations\AiAssistantClient;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Покрывает требования 15.1–15.6:
 * - сверка completed-уроков ядра с обработанными AI lesson_id за период;
 * - повторная публикация lesson.completed.v1 для расхождений (15.2);
 * - логирование расхождений в канал `api` (15.3);
 * - idempotency-окно 24 часа через ключ кеша reconcile:lesson:{id}:{date} (15.4).
 */
class ReconcileLessonsWithAiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{tutor: User, student: User}
     */
    private function makeUsers(): array
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'phone' => '+375291000001']);
        $student = User::factory()->create(['role' => 'student', 'phone' => '+375291000002']);

        return ['tutor' => $tutor, 'student' => $student];
    }

    /**
     * Создать урок в статусе completed с end_time в недавнем прошлом.
     */
    private function makeCompletedLesson(User $tutor, User $student, string $packageCode = 'pack_4'): Lesson
    {
        return Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => Carbon::now('UTC')->subHours(2),
            'end_time' => Carbon::now('UTC')->subMinutes(30),
            'duration_minutes' => 60,
            'price' => '100.00',
            'platform_commission' => '15.00',
            'net_amount' => '85.00',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => $packageCode,
        ]);
    }

    /**
     * Замокать AI-клиент так, чтобы он вернул заданный набор обработанных id.
     *
     * @param  list<int>  $processedIds
     */
    private function fakeAiClient(array $processedIds): void
    {
        $mock = $this->createMock(AiAssistantClient::class);
        $mock->method('processedLessonIds')->willReturn($processedIds);
        $this->app->instance(AiAssistantClient::class, $mock);
    }

    /**
     * Требование 15.1, 15.2: при completed-уроках в ядре и пустом ответе AI
     * (NullAiAssistantClient) для каждого расхождения создаётся запись в
     * integration_outbox с event_type lesson.completed.v1.
     */
    public function test_republishes_lesson_completed_for_each_discrepancy_when_ai_returns_empty(): void
    {
        ['tutor' => $tutor, 'student' => $student] = $this->makeUsers();
        $lessonA = $this->makeCompletedLesson($tutor, $student);
        $lessonB = $this->makeCompletedLesson($tutor, $student);

        // NullAiAssistantClient (дефолтный биндинг) возвращает пустой набор.
        $this->artisan('reconcile:lessons-with-ai')->assertSuccessful();

        $this->assertDatabaseCount('integration_outbox', 2);

        $eventTypes = DB::table('integration_outbox')->pluck('event_type')->all();
        $this->assertSame(['lesson.completed.v1', 'lesson.completed.v1'], $eventTypes);

        $aggregateIds = DB::table('integration_outbox')->pluck('aggregate_id')->sort()->values()->all();
        $expected = collect([$lessonA->id, $lessonB->id])
            ->map(fn ($id) => (string) $id)
            ->sort()
            ->values()
            ->all();
        $this->assertSame($expected, $aggregateIds);
    }

    /**
     * Требование 15.4: повторный запуск в тот же день НЕ создаёт дубли —
     * idempotency-окно (ключ кеша reconcile:lesson:{id}:{date}) блокирует.
     */
    public function test_idempotency_window_prevents_duplicate_republish_within_same_day(): void
    {
        ['tutor' => $tutor, 'student' => $student] = $this->makeUsers();
        $lesson = $this->makeCompletedLesson($tutor, $student);

        $this->artisan('reconcile:lessons-with-ai')->assertSuccessful();
        $this->assertDatabaseCount('integration_outbox', 1);

        // Idempotency-ключ выставлен на сегодня.
        $today = Carbon::now('UTC')->format('Y-m-d');
        $this->assertTrue(
            Cache::store(config('cache.default'))->has(sprintf('reconcile:lesson:%d:%s', $lesson->id, $today)),
            'Idempotency-ключ должен быть установлен после republish.'
        );

        // Повторный запуск в тот же день — дублей нет.
        $this->artisan('reconcile:lessons-with-ai')->assertSuccessful();
        $this->assertDatabaseCount('integration_outbox', 1);
    }

    /**
     * Требование 15.2 (от противного): урок, который AI уже обработал,
     * НЕ должен republish'иться.
     */
    public function test_lesson_processed_by_ai_is_not_republished(): void
    {
        ['tutor' => $tutor, 'student' => $student] = $this->makeUsers();
        $processed = $this->makeCompletedLesson($tutor, $student);
        $missing = $this->makeCompletedLesson($tutor, $student);

        // AI обработал только $processed.
        $this->fakeAiClient([(int) $processed->id]);

        $this->artisan('reconcile:lessons-with-ai')->assertSuccessful();

        // Только расхождение ($missing) попало в outbox.
        $this->assertDatabaseCount('integration_outbox', 1);
        $row = DB::table('integration_outbox')->first();
        $this->assertSame((string) $missing->id, $row->aggregate_id);
        $this->assertSame('lesson.completed.v1', $row->event_type);
    }

    /**
     * Требование 15.3: каждое расхождение логируется в канал `api` с полями
     * lesson_id, core_state, remote_state, action_taken.
     */
    public function test_discrepancy_is_logged_to_api_channel(): void
    {
        ['tutor' => $tutor, 'student' => $student] = $this->makeUsers();
        $lesson = $this->makeCompletedLesson($tutor, $student);

        // Любой вызов Log::channel(...) вернёт spy-логгер, на котором проверим info().
        $logSpy = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        Log::shouldReceive('channel')->andReturn($logSpy);

        $this->artisan('reconcile:lessons-with-ai')->assertSuccessful();

        $logSpy->shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($lesson): bool {
                return $message === 'reconcile:lessons-with-ai discrepancy'
                    && ($context['lesson_id'] ?? null) === (int) $lesson->id
                    && ($context['core_state'] ?? null) === 'completed'
                    && ($context['remote_state'] ?? null) === 'missing'
                    && ($context['action_taken'] ?? null) === 'republished';
            });
    }
}
