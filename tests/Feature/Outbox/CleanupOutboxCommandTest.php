<?php

declare(strict_types=1);

namespace Tests\Feature\Outbox;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Покрывает требование 7.6:
 *  - Старые опубликованные строки (published_at < now - 30d) удаляются.
 *  - Неопубликованные строки (published_at = null) НЕ удаляются, даже если
 *    они старые по created_at.
 *  - Недавно опубликованные строки (published_at > now - 30d) НЕ удаляются.
 *  - Удаление выполняется пачками по 1000 строк (проверяем на > 1000 строках).
 */
class CleanupOutboxCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Минимальный валидный envelope-payload для вставки в outbox.
     *
     * @return array<string, mixed>
     */
    private function envelopePayload(string $id): array
    {
        return [
            'id' => $id,
            'type' => 'lesson.completed',
            'version' => 1,
            'occurred_at' => '2025-01-01T10:00:00+00:00',
            'tenant' => 'edusfera',
            'trace_id' => 'trace-'.$id,
            'actor' => ['type' => 'user', 'id' => 42, 'role' => 'tutor'],
            'aggregate' => ['type' => 'lesson', 'id' => 1234],
            'payload' => [
                'lesson_id' => 1234,
                'tutor_id' => 42,
                'student_id' => 99,
                'started_at' => '2025-01-01T09:00:00+00:00',
                'ended_at' => '2025-01-01T10:00:00+00:00',
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
        $id = $overrides['id'] ?? 'row-'.uniqid('', true);
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
    // Тест 1: старые опубликованные строки удаляются
    // -------------------------------------------------------------------------

    public function test_deletes_old_published_rows(): void
    {
        $oldPublishedAt = Carbon::now('UTC')->subDays(31)->format('Y-m-d H:i:s');

        $id = $this->insertOutboxRow([
            'id' => 'old-published-001',
            'published_at' => $oldPublishedAt,
        ]);

        $this->artisan('integration:cleanup-outbox')->assertExitCode(0);

        $this->assertDatabaseMissing('integration_outbox', ['id' => $id]);
    }

    // -------------------------------------------------------------------------
    // Тест 2: неопубликованные строки НЕ удаляются, даже если старые
    // -------------------------------------------------------------------------

    public function test_keeps_unpublished_rows_even_if_old(): void
    {
        // Старая строка по created_at, но published_at = null — ещё ждёт публикации.
        $oldTimestamp = Carbon::now('UTC')->subDays(90)->format('Y-m-d H:i:s');

        $id = $this->insertOutboxRow([
            'id' => 'old-unpublished-001',
            'published_at' => null,
            'occurred_at' => $oldTimestamp,
            'available_at' => $oldTimestamp,
            'created_at' => $oldTimestamp,
            'updated_at' => $oldTimestamp,
        ]);

        $this->artisan('integration:cleanup-outbox')->assertExitCode(0);

        $this->assertDatabaseHas('integration_outbox', ['id' => $id]);
    }

    // -------------------------------------------------------------------------
    // Тест 3: недавно опубликованные строки НЕ удаляются
    // -------------------------------------------------------------------------

    public function test_keeps_recently_published_rows(): void
    {
        // Опубликовано 5 дней назад — внутри окна хранения (30 дней).
        $recentPublishedAt = Carbon::now('UTC')->subDays(5)->format('Y-m-d H:i:s');

        $id = $this->insertOutboxRow([
            'id' => 'recent-published-001',
            'published_at' => $recentPublishedAt,
        ]);

        $this->artisan('integration:cleanup-outbox')->assertExitCode(0);

        $this->assertDatabaseHas('integration_outbox', ['id' => $id]);
    }

    // -------------------------------------------------------------------------
    // Тест 4: удаление работает пачками (> 1000 строк)
    // -------------------------------------------------------------------------

    public function test_deletes_in_batches_more_than_one_thousand_rows(): void
    {
        $oldPublishedAt = Carbon::now('UTC')->subDays(45)->format('Y-m-d H:i:s');
        $recentPublishedAt = Carbon::now('UTC')->subDays(2)->format('Y-m-d H:i:s');
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s');

        // Вставляем 1500 старых опубликованных строк (> размер пачки 1000)
        // одним bulk-insert, чтобы тест оставался быстрым.
        $oldRows = [];
        for ($i = 0; $i < 1500; $i++) {
            $id = sprintf('old-batch-%04d', $i);
            $oldRows[] = [
                'id' => $id,
                'event_type' => 'lesson.completed.v1',
                'aggregate_type' => 'lesson',
                'aggregate_id' => '1234',
                'payload' => json_encode($this->envelopePayload($id), JSON_THROW_ON_ERROR),
                'occurred_at' => $oldPublishedAt,
                'available_at' => $oldPublishedAt,
                'published_at' => $oldPublishedAt,
                'attempts' => 0,
                'last_error' => null,
                'created_at' => $oldPublishedAt,
                'updated_at' => $oldPublishedAt,
            ];
        }
        // SQLite ограничивает число переменных на запрос, поэтому вставляем чанками.
        foreach (array_chunk($oldRows, 250) as $chunk) {
            DB::table('integration_outbox')->insert($chunk);
        }

        // Контрольные строки, которые должны остаться:
        $keepUnpublished = $this->insertOutboxRow([
            'id' => 'keep-unpublished',
            'published_at' => null,
            'created_at' => $oldPublishedAt,
        ]);
        $keepRecent = $this->insertOutboxRow([
            'id' => 'keep-recent',
            'published_at' => $recentPublishedAt,
        ]);

        $this->assertSame(1502, DB::table('integration_outbox')->count());

        $this->artisan('integration:cleanup-outbox')->assertExitCode(0);

        // Все 1500 старых опубликованных строк удалены.
        $this->assertSame(
            0,
            DB::table('integration_outbox')
                ->where('published_at', '<', $now)
                ->where('id', 'like', 'old-batch-%')
                ->count(),
        );

        // Контрольные строки на месте.
        $this->assertDatabaseHas('integration_outbox', ['id' => $keepUnpublished]);
        $this->assertDatabaseHas('integration_outbox', ['id' => $keepRecent]);
        $this->assertSame(2, DB::table('integration_outbox')->count());
    }
}
