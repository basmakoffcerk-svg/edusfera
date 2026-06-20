<?php

declare(strict_types=1);

namespace Tests\Feature\Logging;

use App\Http\Middleware\StructuredLogging;
use App\Logging\CanonicalJsonFormatter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Tests\TestCase;

/**
 * Покрывает требования 5.4 и 5.5 microservices-foundation:
 *  - 5.4: лог-канал `api` пишет каждую запись одной JSON-строкой с полями
 *         ts, level, request_id, route, user_id, client_id, latency_ms, message, ctx.
 *  - 5.5: middleware StructuredLogging по завершении запроса пишет в канал `api`
 *         запись со статусом ответа, длительностью в миллисекундах и маршрутом.
 */
class StructuredLoggingTest extends TestCase
{
    private TestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        // Подменяем handler'ы реального канала `api` на TestHandler, сохраняя
        // канонический JSON-форматтер. Так мы проверяем и факт записи
        // (требование 5.5), и форму JSON-записи (требование 5.4).
        $this->handler = new TestHandler;
        $this->handler->setFormatter(new CanonicalJsonFormatter);

        Log::channel('api')->getLogger()->setHandlers([$this->handler]);

        // Временный роут под группой `api`, чтобы реально прогнать стек
        // AssignRequestId → StructuredLogging. Небольшая задержка гарантирует
        // измеримую латентность (> 0 мс).
        Route::middleware('api')->get('/test-structured-logging-probe', function () {
            usleep(10_000); // 10 мс

            return response()->json(['ok' => true]);
        });
    }

    public function test_writes_structured_record_to_api_channel_on_terminate(): void
    {
        $response = $this->getJson('/test-structured-logging-probe');

        $response->assertOk();

        // Требование 5.5: запись появляется в канале `api` после обработки запроса.
        $records = $this->handler->getRecords();
        $this->assertCount(1, $records, 'StructuredLogging должен записать ровно одну запись в канал api');

        /** @var LogRecord $record */
        $record = $records[0];

        $this->assertSame('http_request_completed', $record->message);

        $context = $record->context;

        // Маршрут, статус и латентность присутствуют (требование 5.5).
        $this->assertSame('/test-structured-logging-probe', $context['route']);
        $this->assertSame('GET', $context['method']);
        $this->assertSame(200, $context['status']);
        $this->assertArrayHasKey('latency_ms', $context);
        $this->assertIsInt($context['latency_ms']);
        $this->assertGreaterThanOrEqual(1, $context['latency_ms'], 'latency_ms должен отражать измеренную длительность');

        // request_id подмешан из общего контекста (AssignRequestId::shareContext).
        $this->assertArrayHasKey('request_id', $context);
        $this->assertNotNull($context['request_id']);
        $this->assertSame(
            $response->headers->get('X-Request-Id'),
            $context['request_id'],
            'request_id в логе должен совпадать с X-Request-Id ответа',
        );
    }

    public function test_record_is_serialized_as_single_json_line_with_canonical_fields(): void
    {
        $this->getJson('/test-structured-logging-probe')->assertOk();

        $records = $this->handler->getRecords();
        $this->assertCount(1, $records);

        // Требование 5.4: одна запись = одна JSON-строка с каноническим набором полей.
        $formatted = (new CanonicalJsonFormatter)->format($records[0]);

        $this->assertStringEndsWith("\n", $formatted, 'Запись должна оканчиваться переводом строки');

        $line = rtrim($formatted, "\n");
        $this->assertStringNotContainsString("\n", $line, 'Запись должна быть одной JSON-строкой');

        $decoded = json_decode($line, true, flags: JSON_THROW_ON_ERROR);

        $expectedKeys = ['ts', 'level', 'request_id', 'route', 'user_id', 'client_id', 'latency_ms', 'message', 'ctx'];
        $this->assertSame($expectedKeys, array_keys($decoded), 'JSON должен содержать ровно канонический набор полей в порядке схемы');

        // Значения канонических полей соответствуют записи запроса.
        $this->assertSame('info', $decoded['level']);
        $this->assertSame('http_request_completed', $decoded['message']);
        $this->assertSame('/test-structured-logging-probe', $decoded['route']);
        $this->assertIsInt($decoded['latency_ms']);
        $this->assertNotNull($decoded['request_id']);

        // ts — ISO-8601 с миллисекундами и смещением таймзоны.
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}[+-]\d{2}:\d{2}$/',
            $decoded['ts'],
        );
    }

    public function test_middleware_is_registered_in_api_group(): void
    {
        // Требование 5.5: StructuredLogging зарегистрирован в стеке `api`.
        $apiMiddleware = app('router')->getMiddlewareGroups()['api'] ?? [];

        $this->assertContains(
            StructuredLogging::class,
            $apiMiddleware,
            'StructuredLogging должен быть зарегистрирован в middleware-группе api',
        );
    }
}
