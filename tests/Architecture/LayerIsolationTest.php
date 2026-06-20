<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Архитектурный тест изоляции слоёв (требования 6.4, 6.5, 14.1–14.6).
 *
 * ── Почему НЕ pest-plugin-arch ───────────────────────────────────────────────
 * Требование 14.1 допускает `pestphp/pest-plugin-arch` ИЛИ `qossmic/deptrac`.
 * Проект использует чистый PHPUnit 11 (см. phpunit.xml и tests/*), Pest core
 * не установлен. Установка Pest поверх PHPUnit — рискованное вмешательство в
 * рабочий набор из 128 тестов. Поэтому правила реализованы как обычный
 * PHPUnit-тест, который анализирует исходники через токенизацию PHP
 * ({@see token_get_all()}). Токенайзер по своей природе игнорирует комментарии
 * и строковые литералы, поэтому проверка ловит ТОЛЬКО реальные ссылки в коде
 * (например, `@var \App\Models\User` в докблоке MeController не является
 * нарушением — это комментарий, а не зависимость).
 *
 * Правила (по требованию 14):
 *   14.2  app/Http/Api/V1/*   НЕ ссылается на App\Models\*           (читает домен через DTO/контракты)
 *   14.3  app/Filament/*      доменные мутации идут через App\Contracts\*, не через
 *                             конкретные реализации App\Services\Lesson\* (прагматичная трактовка, см. ниже)
 *   14.4  app/Domain/*        НЕ ссылается на App\Filament\* и App\Http\*
 *   14.5  app/Http/Webhooks/* НЕ вызывает DB-фасад и НЕ обращается к Eloquent (App\Models\*) напрямую
 *
 * ── Прагматичная трактовка 14.3 ──────────────────────────────────────────────
 * Filament 3 по своей природе строится на Eloquent (Resource::$model,
 * getEloquentQuery(), таблицы/формы поверх моделей). Тотальный запрет
 * App\Models\* в app/Filament/* сломал бы весь CRUD и противоречил бы фреймворку.
 * Поэтому защищаем РЕАЛЬНО достигнутую границу (задача 8): из доменных контекстов,
 * у которых уже выделены контракты, Filament обязан ходить через интерфейс из
 * App\Contracts\*, а НЕ через конкретную реализацию из App\Services\{Context}\*.
 * На сегодня такой контекст — Lesson (App\Contracts\Lesson\LessonReader/LessonBooker),
 * поэтому правило запрещает импорт App\Services\Lesson\* в слое Filament.
 * Это зелёный, осмысленный инвариант, защищающий разрыв связи UI ↔ Eloquent.
 */
final class LayerIsolationTest extends TestCase
{
    /**
     * Корень `app/`. Тест лежит в tests/Architecture, поэтому поднимаемся на два уровня.
     */
    private function appPath(string $relative = ''): string
    {
        $base = \dirname(__DIR__, 2).'/app';

        return $relative === '' ? $base : $base.'/'.ltrim($relative, '/');
    }

    /**
     * Требование 14.2: классы под app/Http/Api/V1/* не импортируют и не
     * используют классы из пространства имён App\Models\* напрямую.
     */
    public function test_api_v1_does_not_reference_eloquent_models(): void
    {
        $violations = $this->scan(
            $this->appPath('Http/Api/V1'),
            fn (string $name): bool => $this->matchesNamespace($name, 'App\\Models'),
        );

        $this->assertSame(
            [],
            $violations,
            "Слой API (app/Http/Api/V1/*) не должен ссылаться на App\\Models\\* напрямую.\n".
            "API обязан читать домен через DTO/контракты (требования 6.4, 14.2).\n".
            "Нарушения:\n".$this->format($violations),
        );
    }

    /**
     * Требование 14.3 (прагматично): классы под app/Filament/* обращаются к
     * выделенному доменному коду через интерфейсы App\Contracts\*, а не через
     * конкретные реализации App\Services\Lesson\*.
     */
    public function test_filament_uses_lesson_domain_through_contracts(): void
    {
        $violations = $this->scan(
            $this->appPath('Filament'),
            fn (string $name): bool => $this->matchesNamespace($name, 'App\\Services\\Lesson'),
        );

        $this->assertSame(
            [],
            $violations,
            "Слой Filament (app/Filament/*) должен обращаться к контексту Lesson только\n".
            "через App\\Contracts\\Lesson\\* (LessonReader/LessonBooker), а не через конкретные\n".
            "реализации App\\Services\\Lesson\\* (требование 14.3, разрыв связи UI ↔ Eloquent).\n".
            "Нарушения:\n".$this->format($violations),
        );
    }

    /**
     * Требование 14.4: классы под app/Domain/* не импортируют классы из
     * App\Filament\* и App\Http\* (домен не знает о UI и транспортном слое).
     */
    public function test_domain_does_not_depend_on_filament_or_http(): void
    {
        $violations = $this->scan(
            $this->appPath('Domain'),
            fn (string $name): bool => $this->matchesNamespace($name, 'App\\Filament')
                || $this->matchesNamespace($name, 'App\\Http'),
        );

        $this->assertSame(
            [],
            $violations,
            "Слой Domain (app/Domain/*) не должен зависеть от App\\Filament\\* или App\\Http\\*\n".
            "(требование 14.4: домен не знает о UI и HTTP-транспорте).\n".
            "Нарушения:\n".$this->format($violations),
        );
    }

    /**
     * Требование 14.5: классы под app/Http/Webhooks/* не имеют прямых вызовов
     * DB-фасада и не обращаются к Eloquent-моделям (App\Models\*) в обход сервисов.
     */
    public function test_webhooks_do_not_touch_database_directly(): void
    {
        $violations = $this->scan(
            $this->appPath('Http/Webhooks'),
            fn (string $name): bool => $this->matchesNamespace($name, 'App\\Models')
                || $name === 'DB'
                || $name === '\\DB'
                || $name === 'Illuminate\\Support\\Facades\\DB',
        );

        $this->assertSame(
            [],
            $violations,
            "Webhook-приёмники (app/Http/Webhooks/*) не должны дёргать DB-фасад\n".
            "или обращаться к Eloquent (App\\Models\\*) напрямую — только через сервисы\n".
            "(требование 14.5).\n".
            "Нарушения:\n".$this->format($violations),
        );
    }

    /**
     * Защитный sanity-тест: каждая сканируемая директория существует и содержит
     * PHP-файлы, иначе зелёный результат был бы ложноположительным.
     */
    public function test_scanned_directories_contain_php_sources(): void
    {
        foreach (['Http/Api/V1', 'Filament', 'Domain', 'Http/Webhooks'] as $relative) {
            $path = $this->appPath($relative);

            $this->assertDirectoryExists($path, "Ожидалась директория app/{$relative}.");
            $this->assertNotEmpty(
                $this->phpFiles($path),
                "В app/{$relative} не найдено PHP-файлов — проверка границы была бы бессмысленной.",
            );
        }
    }

    /**
     * Сканирует все PHP-файлы под $dir и возвращает нарушения для ссылок,
     * удовлетворяющих $isForbidden. Ссылки извлекаются токенайзером, поэтому
     * комментарии и строковые литералы игнорируются автоматически.
     *
     * @param  callable(string):bool  $isForbidden
     * @return list<array{file: string, line: int, name: string}>
     */
    private function scan(string $dir, callable $isForbidden): array
    {
        $violations = [];

        foreach ($this->phpFiles($dir) as $file) {
            $code = file_get_contents($file);

            if ($code === false) {
                continue;
            }

            foreach ($this->references($code) as $reference) {
                if ($isForbidden($reference['name'])) {
                    $violations[] = [
                        'file' => $file,
                        'line' => $reference['line'],
                        'name' => $reference['name'],
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Извлекает из исходника все ссылки на классы НА УРОВНЕ КОДА:
     *   - полные/частичные имена (T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE),
     *     покрывают `use ...;`, `new App\Models\X`, `App\Models\X::...`, type-hints;
     *   - голые имена классов в статическом контексте `Foo::` (T_STRING + `::`),
     *     что позволяет отловить вызовы фасада вида `DB::table(...)`.
     *
     * Комментарии (T_COMMENT, T_DOC_COMMENT) и строки токенайзером не относятся к
     * именам, поэтому докблоки вроде `@var \App\Models\User` не дают ложных срабатываний.
     *
     * @return list<array{name: string, line: int}>
     */
    private function references(string $code): array
    {
        $tokens = token_get_all($code);
        $count = \count($tokens);
        $references = [];

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (! \is_array($token)) {
                continue;
            }

            [$id, $text, $line] = [$token[0], $token[1], $token[2]];

            // Полные/частичные namespace-имена в коде.
            if (\in_array($id, [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true)) {
                $references[] = ['name' => $text, 'line' => $line];

                continue;
            }

            // Голое имя класса, использованное как `Foo::` (статический доступ/фасад).
            if ($id === T_STRING && $this->isStaticClassUsage($tokens, $i)) {
                $references[] = ['name' => $text, 'line' => $line];
            }
        }

        return $references;
    }

    /**
     * True, если T_STRING на позиции $i используется как имя класса в статическом
     * контексте: за ним (через пробелы) следует `::`, и перед ним нет оператора
     * доступа к члену (`->`, `?->`, `::`) или ключевого слова объявления.
     *
     * @param  array<int, array{0:int,1:string,2:int}|string>  $tokens
     */
    private function isStaticClassUsage(array $tokens, int $i): bool
    {
        // Следующий значимый токен должен быть `::`.
        $next = $this->nextMeaningful($tokens, $i);

        if ($next === null || ! \is_array($tokens[$next]) || $tokens[$next][0] !== T_DOUBLE_COLON) {
            return false;
        }

        // Предыдущий значимый токен не должен быть оператором доступа/объявлением.
        $prev = $this->prevMeaningful($tokens, $i);

        if ($prev !== null && \is_array($tokens[$prev])) {
            $forbiddenPrev = [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_CONST, T_NEW];

            if (\defined('T_NULLSAFE_OBJECT_OPERATOR')) {
                $forbiddenPrev[] = T_NULLSAFE_OBJECT_OPERATOR;
            }

            if (\in_array($tokens[$prev][0], $forbiddenPrev, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Индекс следующего токена, не являющегося пробелом/комментарием.
     *
     * @param  array<int, array{0:int,1:string,2:int}|string>  $tokens
     */
    private function nextMeaningful(array $tokens, int $i): ?int
    {
        $count = \count($tokens);

        for ($j = $i + 1; $j < $count; $j++) {
            if ($this->isSkippable($tokens[$j])) {
                continue;
            }

            return $j;
        }

        return null;
    }

    /**
     * Индекс предыдущего токена, не являющегося пробелом/комментарием.
     *
     * @param  array<int, array{0:int,1:string,2:int}|string>  $tokens
     */
    private function prevMeaningful(array $tokens, int $i): ?int
    {
        for ($j = $i - 1; $j >= 0; $j--) {
            if ($this->isSkippable($tokens[$j])) {
                continue;
            }

            return $j;
        }

        return null;
    }

    /**
     * Пробел или комментарий — пропускаем при поиске соседних значимых токенов.
     *
     * @param  array{0:int,1:string,2:int}|string  $token
     */
    private function isSkippable(array|string $token): bool
    {
        return \is_array($token)
            && \in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }

    /**
     * Проверяет, что $name принадлежит пространству имён $namespace (с учётом
     * возможного ведущего обратного слэша у fully-qualified имён).
     */
    private function matchesNamespace(string $name, string $namespace): bool
    {
        $name = ltrim($name, '\\');

        return $name === $namespace || str_starts_with($name, $namespace.'\\');
    }

    /**
     * Все .php-файлы под $dir (рекурсивно).
     *
     * @return list<string>
     */
    private function phpFiles(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];

        /** @var SplFileInfo $info */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $info) {
            if ($info->isFile() && strtolower($info->getExtension()) === 'php') {
                $files[] = $info->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Человекочитаемый список нарушений для сообщения об ошибке.
     *
     * @param  list<array{file: string, line: int, name: string}>  $violations
     */
    private function format(array $violations): string
    {
        if ($violations === []) {
            return '  (нет)';
        }

        $root = \dirname(__DIR__, 2).'/';

        return implode("\n", array_map(
            static fn (array $v): string => sprintf(
                '  - %s:%d  →  %s',
                str_replace($root, '', $v['file']),
                $v['line'],
                $v['name'],
            ),
            $violations,
        ));
    }
}
