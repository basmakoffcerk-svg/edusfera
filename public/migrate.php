<?php

use App\Models\ClassroomSession;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\Ai\GeminiService;
use App\Services\Classroom\LiveKitService;
use App\Services\ClassroomService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Одноразовый веб-скрипт запуска миграций, обслуживания базы данных и настройки WebRTC Edusfera.
 * Использование: https://edusfera.by/migrate.php?key=edusfera2026
 */
$authKey = 'edusfera2026';
$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';

if ($providedKey !== $authKey) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Обслуживание платформы — Edusfera</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0F172A; color: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
            .card { background: #1E293B; border: 1px solid #334155; border-radius: 20px; padding: 32px; max-width: 480px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
            h2 { margin: 0 0 10px 0; font-size: 1.35rem; color: #FFFFFF; }
            p { color: #94A3B8; font-size: 0.9rem; line-height: 1.5; margin: 0 0 20px 0; }
            .btn { display: inline-flex; align-items: center; justify-content: center; width: 100%; height: 46px; background: #10B981; color: #FFFFFF; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; border: none; cursor: pointer; transition: background 0.15s ease; box-sizing: border-box; }
            .btn:hover { background: #059669; }
            input[type="text"], input[type="password"] { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #334155; background: #0F172A; color: #fff; margin-bottom: 12px; box-sizing: border-box; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>🔒 Вход в панель обслуживания Edusfera</h2>
            <p>Введите ключ доступа для работы с миграциями, кодом и настройками WebRTC:</p>
            <form method="get" action="">
                <input type="password" name="key" placeholder="Введите ключ доступа..." required autofocus>
                <button type="submit" class="btn">Войти</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$action = $_GET['action'] ?? $_POST['action'] ?? 'menu';

// Helper function to update .env
function updateEnvKey(string $key, string $val): bool
{
    $envPath = __DIR__.'/../.env';
    if (! file_exists($envPath)) {
        return false;
    }
    $content = file_get_contents($envPath);
    $pattern = "/^{$key}=.*/m";
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, "{$key}={$val}", $content);
    } else {
        $content .= "\n{$key}={$val}";
    }

    return file_put_contents($envPath, $content) !== false;
}

// Current patch info
$localPatch = __DIR__.'/edusfera-patch.zip';
$rootPatch = __DIR__.'/../edusfera-patch.zip';
$activePatchFile = file_exists($localPatch) ? $localPatch : (file_exists($rootPatch) ? $rootPatch : null);
$patchSizeMb = $activePatchFile ? round(filesize($activePatchFile) / 1024 / 1024, 2) : 0;
$patchDate = $activePatchFile ? date('Y-m-d H:i:s', filemtime($activePatchFile)) : 'не найден';

// Current WebRTC settings
$currentMeteredApp = (string) env('METERED_APP_NAME', config('classroom.metered.app_name', ''));
$currentMeteredKey = (string) env('METERED_API_KEY', config('classroom.metered.api_key', ''));
$currentTurnUrl = (string) env('TURN_SERVER_URL', config('classroom.turn.url', ''));
$currentLiveKitUrl = (string) env('LIVEKIT_URL', env('LIVEKIT_HOST', config('classroom.livekit_host', '')));
$currentLiveKitKey = (string) env('LIVEKIT_API_KEY', config('classroom.livekit_api_key', ''));
$currentLiveKitSecret = (string) env('LIVEKIT_API_SECRET', config('classroom.livekit_api_secret', ''));

// Current Gemini AI settings
$currentGeminiKey = (string) env('GEMINI_API_KEY', config('services.gemini.api_key', ''));
$currentGeminiModel = (string) env('GEMINI_MODEL', config('services.gemini.model', 'gemini-flash-latest'));
$currentGeminiLiteModel = (string) env('GEMINI_LITE_MODEL', config('services.gemini.lite_model', 'gemini-3.5-flash-lite'));
$currentGeminiBaseUrl = (string) env('GEMINI_BASE_URL', config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'));
$currentGeminiProxy = (string) env('GEMINI_PROXY', config('services.gemini.proxy', ''));
$currentGeminiUseXboxDns = (bool) env('GEMINI_USE_XBOX_DNS', config('services.gemini.use_xbox_dns', true));

if ($action === 'menu') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Панель обслуживания Edusfera (Без Docker)</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0F172A; color: #F8FAFC; margin: 0; padding: 24px; display: flex; justify-content: center; }
            .container { max-width: 680px; width: 100%; }
            .card { background: #1E293B; border: 1px solid #334155; border-radius: 16px; padding: 24px; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
            h2 { margin: 0 0 8px 0; font-size: 1.25rem; color: #FFFFFF; display: flex; align-items: center; gap: 8px; }
            p { color: #94A3B8; font-size: 0.9rem; line-height: 1.5; margin: 0 0 16px 0; }
            .badge { display: inline-block; padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; }
            .badge-green { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
            .badge-yellow { background: rgba(234, 179, 8, 0.15); color: #eab308; }
            .badge-blue { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
            .btn { display: inline-flex; align-items: center; justify-content: center; height: 42px; padding: 0 18px; color: #FFFFFF; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: opacity 0.15s; }
            .btn:hover { opacity: 0.9; }
            .btn-green { background: #10B981; }
            .btn-blue { background: #0284c7; }
            .btn-purple { background: #7D39EB; }
            .btn-gray { background: #334155; }
            .btn-full { width: 100%; box-sizing: border-box; margin-top: 8px; }
            input[type="text"], input[type="password"] { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #475569; background: #0F172A; color: #fff; margin-bottom: 10px; box-sizing: border-box; }
            label { display: block; font-size: 0.85rem; color: #94A3B8; margin-bottom: 4px; }
            .box { background: #0F172A; border: 1px solid #334155; border-radius: 10px; padding: 14px; margin-bottom: 16px; font-size: 0.85rem; }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div style="margin-bottom: 24px; text-align: center;">
                <h1 style="font-size: 1.6rem; margin: 0 0 6px 0; color: #fff;">Edusfera.by — Обслуживание сервера</h1>
                <span class="badge badge-blue">Режим: Bare-Metal (Без Docker)</span>
            </div>

            <!-- 1. Patch Updater -->
            <div class="card">
                <h2>📦 1. Обновление кода и ассетов (edusfera-patch.zip)</h2>
                <div class="box">
                    <div>Текущий архив на сервере: <b><?= $activePatchFile ? 'Найден' : 'Отсутствует' ?></b></div>
                    <div>Размер: <b><?= $patchSizeMb ?> МБ</b> | Дата: <b><?= $patchDate ?></b></div>
                    <?php if ($patchSizeMb < 2 && $patchSizeMb > 0) { ?>
                        <div style="color: #eab308; margin-top: 6px;">⚠️ Внимание: На сервере старый архив (< 2 МБ). Для поддержки Excalidraw и связи 4G/LTE загрузите свежий архив (~3.3 МБ).</div>
                    <?php } ?>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                    <?php if ($activePatchFile) { ?>
                    <a href="?key=edusfera2026&action=unpack_patch" class="btn btn-blue" style="flex: 1;">
                        ⚡ Распаковать текущий <?= $patchSizeMb ?> МБ патч
                    </a>
                    <?php } ?>
                </div>

                <!-- Direct Upload form -->
                <form action="?key=edusfera2026&action=upload_patch" method="post" enctype="multipart/form-data" style="border-top: 1px solid #334155; padding-top: 16px;">
                    <label><b>Загрузить свежий edusfera-patch.zip прямо с ПК:</b></label>
                    <input type="file" name="patch_file" accept=".zip" required style="margin-bottom: 12px;">
                    <button type="submit" class="btn btn-purple btn-full">
                        🚀 Загрузить с компьютера и сразу применить код
                    </button>
                </form>
            </div>

            <!-- 2. LiveKit Cloud SFU -->
            <div class="card">
                <h2>📡 2. Видеосвязь через LiveKit Cloud SFU</h2>
                <p>Высокоскоростной медиа-сервер SFU для мгновенной двусторонней видеосвязи на ПК, планшетах и смартфонах через 4G/LTE без задержек и сбоев:</p>

                <div class="box">
                    <div>Статус LiveKit: <b><?= $currentLiveKitKey ? '<span style="color:#22c55e">Подключен ('.$currentLiveKitKey.')</span>' : '<span style="color:#eab308">Не настроен</span>' ?></b></div>
                    <div>WebSocket URL: <b><?= $currentLiveKitUrl ? htmlspecialchars($currentLiveKitUrl) : 'Не задан' ?></b></div>
                </div>

                <form action="?key=edusfera2026&action=save_livekit" method="post">
                    <label>LiveKit WebSocket URL:</label>
                    <input type="text" name="livekit_url" value="<?= htmlspecialchars($currentLiveKitUrl ?: 'wss://edusfera-iae194s8.livekit.cloud') ?>" placeholder="wss://edusfera-iae194s8.livekit.cloud" required>

                    <label>LiveKit API Key:</label>
                    <input type="text" name="livekit_key" value="<?= htmlspecialchars($currentLiveKitKey) ?>" placeholder="например: APImGcEym6iCNi2" required>

                    <label>LiveKit API Secret:</label>
                    <input type="password" name="livekit_secret" value="<?= htmlspecialchars($currentLiveKitSecret) ?>" placeholder="секретный ключ API Secret" required>

                    <button type="submit" class="btn btn-green btn-full">
                        💾 Сохранить LiveKit ключи в .env
                    </button>
                </form>

                <div style="margin-top: 12px; text-align: right;">
                    <a href="?key=edusfera2026&action=test_livekit" class="btn btn-gray" style="font-size: 0.8rem; height: 36px;">
                        🔍 Проверить подключение и выпуск токена LiveKit
                    </a>
                </div>
            </div>

            <!-- 3. Google Gemini AI & Copilot -->
            <div class="card">
                <h2>🤖 3. Google Gemini AI (Edusfera Copilot & Методист)</h2>
                <p>Нейросетевой помощник для всех кабинетов платформы (ИИ-Методист для репетитора, ИИ-Тьютор для ученика):</p>

                <div class="box">
                    <div>Статус Gemini: <b><?= $currentGeminiKey ? '<span style="color:#22c55e">Ключ задан ('.substr($currentGeminiKey, 0, 8).'...)</span>' : '<span style="color:#eab308">Не настроен</span>' ?></b></div>
                    <div>Модель: <b><?= htmlspecialchars($currentGeminiModel) ?></b> (Lite: <?= htmlspecialchars($currentGeminiLiteModel) ?>)</div>
                    <div>Маршрутизация: <b><?= $currentGeminiUseXboxDns ? '<span style="color:#22c55e">🎮 Через xbox-dns.ru (SNI Proxy)</span>' : ($currentGeminiBaseUrl !== 'https://generativelanguage.googleapis.com/v1beta' ? '<span style="color:#38bdf8">Через Reverse Proxy</span>' : ($currentGeminiProxy ? '<span style="color:#38bdf8">Через Прокси</span>' : '<span style="color:#eab308">Прямое подключение (проверьте регион)</span>')) ?></b></div>
                </div>

                <form action="?key=edusfera2026&action=save_gemini" method="post">
                    <label>Google Gemini API Key:</label>
                    <input type="text" name="gemini_key" value="<?= htmlspecialchars($currentGeminiKey) ?>" placeholder="AIzaSy..." required>

                    <label style="display: flex; align-items: center; gap: 8px; margin: 10px 0; cursor: pointer; color: #f1f5f9; font-weight: 500;">
                        <input type="checkbox" name="gemini_use_xbox_dns" value="1" <?= $currentGeminiUseXboxDns ? 'checked' : '' ?> style="width: auto; margin: 0;">
                        <span>Маршрутизировать через <b>xbox-dns.ru</b> (обход региональной блокировки Google в РБ)</span>
                    </label>

                    <label>Base URL (Reverse Proxy / Cloudflare Worker):</label>
                    <input type="text" name="gemini_base_url" value="<?= htmlspecialchars($currentGeminiBaseUrl) ?>" placeholder="https://generativelanguage.googleapis.com/v1beta">

                    <label>HTTP/SOCKS Прокси (если используется без xbox-dns):</label>
                    <input type="text" name="gemini_proxy" value="<?= htmlspecialchars($currentGeminiProxy) ?>" placeholder="например: http://user:pass@proxy.host:port">

                    <div style="display: flex; gap: 10px;">
                        <div style="flex: 1;">
                            <label>Основная модель:</label>
                            <input type="text" name="gemini_model" value="<?= htmlspecialchars($currentGeminiModel ?: 'gemini-flash-latest') ?>">
                        </div>
                        <div style="flex: 1;">
                            <label>Lite модель:</label>
                            <input type="text" name="gemini_lite_model" value="<?= htmlspecialchars($currentGeminiLiteModel ?: 'gemini-3.5-flash-lite') ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-green btn-full">
                        💾 Сохранить настройки Gemini в .env
                    </button>
                </form>

                <div style="margin-top: 12px; display: flex; gap: 10px;">
                    <a href="?key=edusfera2026&action=test_ai" class="btn btn-purple" style="font-size: 0.85rem; height: 38px; width: auto; padding: 0 16px; flex: 1;">
                        🔍 Протестировать Gemini Service
                    </a>
                    <a href="?key=edusfera2026&action=test_xbox_dns" class="btn btn-cyan" style="font-size: 0.85rem; height: 38px; width: auto; padding: 0 16px; flex: 1;">
                        🎮 Диагностика узлов xbox-dns.ru
                    </a>
                </div>

                <details style="margin-top: 14px; background: rgba(15,23,42,0.6); padding: 12px; border-radius: 8px; font-size: 0.82rem; color: #94A3B8;">
                    <summary style="cursor: pointer; font-weight: 600; color: #38bdf8;">
                        ℹ️ Если Google выдает "User location is not supported" (для IP Беларуси):
                    </summary>
                    <div style="margin-top: 8px; line-height: 1.5;">
                        Google AI Studio ограничивает прямые запросы с IP Беларуси/РФ. Чтобы запросы шли без ограничений, достаточно создать бесплатный Cloudflare Worker (или указать европейский прокси):<br><br>
                        <b>5 строк кода в Cloudflare Workers:</b>
                        <pre style="background: #020617; padding: 8px; border-radius: 6px; color: #a5f3fc; overflow-x: auto; margin: 6px 0;">export default {
  async fetch(req) {
    const url = new URL(req.url);
    url.hostname = 'generativelanguage.googleapis.com';
    return fetch(url.toString(), { method: req.method, headers: req.headers, body: req.body });
  }
};</pre>
                        После развертывания вставьте полученный URL воркера (например: <code>https://my-gemini.workers.dev/v1beta</code>) в поле Base URL выше.
                    </div>
                </details>
            </div>

            <!-- 4. Database & Classroom Test -->
            <div class="card">
                <h2>🛠️ 4. База данных и Тестирование класса</h2>
                <p>Запуск системных миграций и подготовка тестовой пары:</p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="?key=edusfera2026&action=classroom_test" class="btn btn-purple">
                        🎥 Подготовить тестового ученика и урок
                    </a>
                    <a href="?key=edusfera2026&action=run_migrations" class="btn btn-green">
                        ▶ Запустить обновление базы и очистку кэша
                    </a>
                    <a href="?key=edusfera2026&action=view_log" class="btn btn-gray" style="font-size:0.85rem;">
                        📋 Просмотреть логи ошибок (storage/logs/laravel.log)
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='background:#0f172a; color:#22c55e; padding:24px; font-family:monospace; line-height:1.5; font-size:14px; border-radius:12px; margin:16px; overflow-x:auto;'>";

try {
    // ── ACTION: Upload patch ──────────────────────────────────────────
    if ($action === 'upload_patch') {
        echo "🚀 <b>Загрузка и распаковка архива патча...</b>\n\n";
        if (! isset($_FILES['patch_file']) || $_FILES['patch_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка при загрузке файла: код '.($_FILES['patch_file']['error'] ?? 'empty'));
        }
        $targetZip = __DIR__.'/edusfera-patch.zip';
        if (! move_uploaded_file($_FILES['patch_file']['tmp_name'], $targetZip)) {
            throw new Exception('Не удалось сохранить загруженный файл в '.$targetZip);
        }
        echo '   ✓ Файл успешно сохранен как public/edusfera-patch.zip ('.round(filesize($targetZip) / 1024 / 1024, 2)." МБ)\n";

        $zip = new ZipArchive;
        if ($zip->open($targetZip) === true) {
            $targetDir = realpath(__DIR__.'/..');
            $zip->extractTo($targetDir);
            $numFiles = $zip->numFiles;
            $zip->close();
            echo "   ✓ Успешно распаковано файлов: {$numFiles} в корень сайта ({$targetDir})\n";
            $kernel->call('optimize:clear');
            echo "   ✓ Кэш оптимизации Laravel сброшен:\n".$kernel->output()."\n";
            echo "\n🎉 <b>КОД И АССЕТЫ EXCALIDRAW УСПЕШНО ОБНОВЛЕНЫ!</b>\n";
            echo "<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        } else {
            throw new Exception('Не удалось открыть zip-архив.');
        }
        echo '</pre>';
        exit;
    }

    // ── ACTION: Unpack local patch ────────────────────────────────────
    if ($action === 'unpack_patch') {
        echo "🚀 <b>Распаковка архива edusfera-patch.zip...</b>\n\n";
        $zipPaths = [
            __DIR__.'/edusfera-patch.zip',
            __DIR__.'/../edusfera-patch.zip',
        ];
        $zipFile = null;
        foreach ($zipPaths as $zp) {
            if (file_exists($zp)) {
                $zipFile = $zp;
                break;
            }
        }
        if (! $zipFile) {
            echo "   ❌ Архив edusfera-patch.zip не найден ни в public/, ни в корне.\n";
        } else {
            echo "   Архив: {$zipFile}\n";
            echo '   Размер: '.round(filesize($zipFile) / 1024 / 1024, 2).' МБ, Дата: '.date('Y-m-d H:i:s', filemtime($zipFile))."\n\n";

            $zip = new ZipArchive;
            if ($zip->open($zipFile) === true) {
                $targetDir = realpath(__DIR__.'/..');
                $zip->extractTo($targetDir);
                $numFiles = $zip->numFiles;
                $zip->close();
                echo "   ✓ Успешно распаковано файлов: {$numFiles} в {$targetDir}\n";
                $kernel->call('optimize:clear');
                echo "   ✓ Кэш оптимизации Laravel сброшен:\n".$kernel->output()."\n";
                echo "\n🎉 <b>ОБНОВЛЕНИЕ ЗАВЕРШЕНО!</b>\n";
                echo "<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
            } else {
                echo "   ❌ Не удалось открыть zip-архив: {$zipFile}\n";
            }
        }
        echo '</pre>';
        exit;
    }

    // ── ACTION: Save LiveKit settings ────────────────────────────────
    if ($action === 'save_livekit') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "⚙️ <b>Сохранение настроек LiveKit Cloud в .env...</b>\n\n";
        $url = trim($_POST['livekit_url'] ?? '');
        $key = trim($_POST['livekit_key'] ?? '');
        $secret = trim($_POST['livekit_secret'] ?? '');

        updateEnvKey('LIVEKIT_URL', $url);
        updateEnvKey('LIVEKIT_HOST', $url);
        updateEnvKey('LIVEKIT_API_KEY', $key);
        updateEnvKey('LIVEKIT_API_SECRET', $secret);

        $kernel->call('config:clear');
        echo "   ✓ Настройки записаны в .env:\n";
        echo "     LIVEKIT_URL={$url}\n";
        echo "     LIVEKIT_API_KEY={$key}\n";
        echo '     LIVEKIT_API_SECRET='.($secret ? substr($secret, 0, 5).'***' : '')."\n\n";
        echo "   ✓ Кэш конфигурации сброшен.\n\n";
        echo "<a href='?key=edusfera2026&action=test_livekit' style='color:#38bdf8; font-weight:bold;'>👉 Проверить статус и выпуск токена LiveKit</a> | ";
        echo "<a href='?key=edusfera2026' style='color:#94a3b8;'>← В меню</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Test LiveKit connection ───────────────────────────────
    if ($action === 'test_livekit') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "🔍 <b>Диагностика подключения к LiveKit Cloud:</b>\n\n";
        $service = app(LiveKitService::class);
        $isConfigured = $service->isConfigured();

        echo '1. Статус конфигурации LiveKit: '.($isConfigured ? '✅ НАСТРОЕН' : '❌ НЕ НАСТРОЕН')."\n";
        echo '   Host / URL: '.$service->getWsUrl()."\n";
        echo '   API Key: '.(config('classroom.livekit_api_key') ?: 'пусто')."\n";
        echo '   API Secret: '.(config('classroom.livekit_api_secret') ? 'задан ('.strlen(config('classroom.livekit_api_secret')).' симв.)' : 'пусто')."\n\n";

        if ($isConfigured) {
            $lesson = Lesson::find(23) ?? Lesson::latest('id')->first();
            $user = User::first();
            if ($lesson && $user) {
                try {
                    $token = $service->generateToken($lesson, $user);
                    echo "2. Тест генерации JWT токена комнаты: ✅ УСПЕШНО\n";
                    echo "   Урок ID: {$lesson->id}\n";
                    echo "   Комната: edusfera_lesson_{$lesson->id}\n";
                    echo '   Токен (первые 40 симв.): '.substr($token, 0, 40)."...\n";
                } catch (Throwable $e) {
                    echo '2. Ошибка генерации токена: ❌ '.$e->getMessage()."\n";
                }
            }
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Save Gemini AI settings ───────────────────────────────
    if ($action === 'save_gemini') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "⚙️ <b>Сохранение настроек Google Gemini AI в .env...</b>\n\n";
        $key = trim($_POST['gemini_key'] ?? '');
        $baseUrl = trim($_POST['gemini_base_url'] ?? '');
        $proxy = trim($_POST['gemini_proxy'] ?? '');
        $model = trim($_POST['gemini_model'] ?? 'gemini-flash-latest');
        $liteModel = trim($_POST['gemini_lite_model'] ?? 'gemini-3.5-flash-lite');
        $useXboxDns = ! empty($_POST['gemini_use_xbox_dns']) ? 'true' : 'false';

        updateEnvKey('GEMINI_API_KEY', $key);
        updateEnvKey('GEMINI_BASE_URL', $baseUrl ?: 'https://generativelanguage.googleapis.com/v1beta');
        updateEnvKey('GEMINI_PROXY', $proxy);
        updateEnvKey('GEMINI_MODEL', $model);
        updateEnvKey('GEMINI_LITE_MODEL', $liteModel);
        updateEnvKey('GEMINI_USE_XBOX_DNS', $useXboxDns);

        $kernel->call('config:clear');
        echo "   ✓ Настройки записаны в .env:\n";
        echo '     GEMINI_API_KEY='.($key ? substr($key, 0, 8).'...' : 'пусто')."\n";
        echo '     GEMINI_BASE_URL='.($baseUrl ?: 'https://generativelanguage.googleapis.com/v1beta')."\n";
        echo '     GEMINI_PROXY='.($proxy ?: 'отключен')."\n";
        echo "     GEMINI_MODEL={$model}\n";
        echo "     GEMINI_LITE_MODEL={$liteModel}\n";
        echo "     GEMINI_USE_XBOX_DNS={$useXboxDns}\n\n";
        echo "   ✓ Кэш конфигурации сброшен.\n\n";
        echo "<a href='?key=edusfera2026&action=test_ai' style='color:#38bdf8; font-weight:bold;'>👉 Проверить вызов Gemini API</a> | ";
        echo "<a href='?key=edusfera2026' style='color:#94a3b8;'>← В меню</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Test AI / Gemini ──────────────────────────────────────
    if ($action === 'test_ai') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "🤖 <b>Диагностика Google Gemini API:</b>\n\n";

        // Check if local proxy exists (e.g. 1080, 8080, 3128, etc)
        $proxyPorts = [1080, 8080, 3128, 8888, 9050];
        $openProxies = [];
        foreach ($proxyPorts as $p) {
            $fp = @fsockopen('127.0.0.1', $p, $errno, $errstr, 0.2);
            if ($fp) {
                $openProxies[] = $p;
                fclose($fp);
            }
        }
        echo '   Локальные порты (proxy?): '.(! empty($openProxies) ? implode(', ', $openProxies) : 'нет активных')."\n\n";

        $service = app(GeminiService::class);
        $isConfigured = $service->isConfigured();
        $useXbox = config('services.gemini.use_xbox_dns', true);
        echo '1. Статус конфигурации: '.($isConfigured ? '✅ НАСТРОЕН' : '❌ НЕ НАСТРОЕН')."\n";
        echo '   Model: '.config('services.gemini.model')."\n";
        echo '   Lite Model: '.config('services.gemini.lite_model')."\n";
        echo '   API Key: '.(config('services.gemini.api_key') ? substr(config('services.gemini.api_key'), 0, 8).'...' : 'пусто')."\n";
        echo '   Маршрутизация xbox-dns.ru: '.($useXbox ? '✅ ВКЛЮЧЕНА (SNI узлы: '.implode(', ', $service->getXboxDnsIps()).')' : '❌ ВЫКЛЮЧЕНА')."\n\n";

        if ($isConfigured) {
            try {
                $connResult = $service->testConnection();
                echo "2. Тест прямого подключения:\n";
                echo '   Успех: '.($connResult['success'] ? '✅ ДА' : '❌ НЕТ')."\n";
                echo '   Сообщение: '.($connResult['message'] ?? '')."\n";
                echo '   Задержка: '.($connResult['latency_ms'] ?? 0)." мс\n\n";

                $start = microtime(true);
                $resp = $service->generateText('Ответь одним коротким предложением: кто ты?', 'Ты умный методист Edusfera.');
                $dur = round((microtime(true) - $start), 2);
                echo '3. Тестовый запрос Gemini generateText: '.(! empty($resp) ? "✅ УСПЕШНО ({$dur} сек)" : '⚠️ ПУСТОЙ ОТВЕТ')."\n";
                echo "   Ответ: {$resp}\n";
            } catch (Throwable $e) {
                echo '2. Ошибка вызова Gemini: ❌ '.$e->getMessage()."\n";
            }
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Test Xbox-DNS proxy ────────────────────────────────────
    if ($action === 'test_xbox_dns') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "🎮 <b>Тестирование доступа через xbox-dns.ru:</b>\n\n";

        $apiKey = config('services.gemini.api_key');
        echo '1. API Key: '.($apiKey ? substr($apiKey, 0, 8).'...' : 'пусто')."\n";

        // Step 1: Resolve IPs via DoH or fallback
        $ips = ['188.68.214.130', '188.68.214.143'];
        echo "2. Прокси-узлы xbox-dns.ru для generativelanguage.googleapis.com:\n";
        foreach ($ips as $ip) {
            echo "   - {$ip}\n";
        }

        // Step 2: Try curl with CURLOPT_RESOLVE for each IP
        foreach ($ips as $idx => $ip) {
            echo "\n3. Проверка узла #{$idx} ({$ip}):\n";
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => 'Привет! Ответь одним коротким предложением: кто ты?']]],
                ],
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_RESOLVE, [
                "generativelanguage.googleapis.com:443:{$ip}",
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $t0 = microtime(true);
            $raw = curl_exec($ch);
            $dur = round((microtime(true) - $t0) * 1000);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            echo "   HTTP Code: {$code} (заняло {$dur} мс)\n";
            if ($err) {
                echo "   cURL Error: {$err}\n";
            }
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                    echo "   ✅ УСПЕХ! Ответ модели:\n";
                    echo '   >>> '.trim($decoded['candidates'][0]['content']['parts'][0]['text'])."\n";
                    break;
                } else {
                    echo '   Ответ сервера: '.substr($raw, 0, 300)."\n";
                }
            }
        }

        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Save TURN settings ────────────────────────────────────
    if ($action === 'save_turn') {
        echo "⚙️ <b>Сохранение настроек WebRTC / TURN в .env...</b>\n\n";
        $mApp = trim($_POST['metered_app'] ?? '');
        $mKey = trim($_POST['metered_key'] ?? '');

        updateEnvKey('METERED_APP_NAME', $mApp);
        updateEnvKey('METERED_API_KEY', $mKey);

        $kernel->call('config:clear');
        echo "   ✓ Настройки записаны в .env:\n";
        echo "     METERED_APP_NAME={$mApp}\n";
        echo '     METERED_API_KEY='.($mKey ? substr($mKey, 0, 5).'***' : '')."\n\n";
        echo "   ✓ Кэш конфигурации сброшен.\n\n";
        echo "<a href='?key=edusfera2026&action=test_turn' style='color:#38bdf8; font-weight:bold;'>👉 Проверить генерацию ICE/TURN серверов</a> | ";
        echo "<a href='?key=edusfera2026' style='color:#94a3b8;'>← В меню</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Test TURN / ICE servers ───────────────────────────────
    if ($action === 'test_turn') {
        echo "🔍 <b>Диагностика серверов WebRTC ICE & TURN для обхода 4G CGNAT:</b>\n\n";
        $service = app(ClassroomService::class);
        $iceServers = $service->getIceServers();

        echo 'Всего сконфигурировано ICE серверов: '.count($iceServers)."\n\n";
        $hasTurn = false;
        foreach ($iceServers as $i => $s) {
            $urls = (array) ($s['urls'] ?? []);
            $urlsStr = implode(', ', $urls);
            $isTurn = str_contains($urlsStr, 'turn:') || str_contains($urlsStr, 'turns:');
            if ($isTurn) {
                $hasTurn = true;
            }
            $type = $isTurn ? '🛡️ TURN Relay' : '⚡ STUN';
            echo "   [{$i}] {$type}: {$urlsStr}\n";
            if (isset($s['username'])) {
                echo '       User: '.$s['username']."\n";
            }
        }

        echo "\n";
        if ($hasTurn) {
            echo "   ✅ <b>TURN Relay сервер найден!</b>\n";
            echo "   Связь между мобильным телефоном на 4G/LTE и домашним Wi-Fi защищена и сможет обходить NAT.\n";
        } else {
            echo "   ⚠️ <b>ВНИМАНИЕ: Нет активного TURN Relay сервера!</b>\n";
            echo "   Для работы видео со смартфонов через 4G/LTE (без Docker) укажите Metered App/Key в меню обслуживания.\n";
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Classroom test ────────────────────────────────────────
    if ($action === 'classroom_test') {
        echo "🎥 <b>Создание тестового ученика, связки с репетитором и урока:</b>\n\n";
        $useTestTutor = isset($_GET['test_tutor']);
        if ($useTestTutor) {
            $testTutor = User::withTrashed()->where('email', 'tutor-test@edusfera.by')->first();
            if (! $testTutor) {
                $testTutor = User::create([
                    'name' => 'Виктор Репетитор',
                    'email' => 'tutor-test@edusfera.by',
                    'password' => Hash::make('Password123!'),
                    'role' => 'tutor',
                    'phone' => '+37529'.rand(1000000, 9999999),
                    'email_verified_at' => now(),
                ]);
            } else {
                if ($testTutor->trashed()) {
                    $testTutor->restore();
                }
                $testTutor->password = Hash::make('Password123!');
                $testTutor->save();
            }
            TutorProfile::firstOrCreate(
                ['user_id' => $testTutor->id],
                ['headline' => 'Тестовый репетитор', 'hourly_rate' => 35.00, 'is_approved' => true, 'is_active' => true]
            );
            $kernel->call('classroom:prepare-test', ['--tutor-email' => 'tutor-test@edusfera.by']);
        } else {
            $kernel->call('classroom:prepare-test');
        }
        echo $kernel->output();
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Inspect signals ──────────────────────────────────────
    if ($action === 'inspect_signals') {
        echo "🔍 <b>Содержимое кэша сигналов WebRTC:</b>\n\n";
        $targetLid = (int) ($_GET['lesson_id'] ?? 23);
        $checkList = array_unique([$targetLid, 23, 22, 21, 20]);
        foreach ($checkList as $lid) {
            $sigKey = 'classroom_signals_'.$lid;
            $sigs = Cache::get($sigKey, []);
            echo "--- Lesson {$lid} ({$sigKey}): ".count($sigs)." сигналов ---\n";
            foreach (array_slice($sigs, -25) as $s) {
                $type = $s['type'] ?? 'unknown';
                $role = $s['sender_role'] ?? 'unknown';
                $sender = $s['sender_id'] ?? 'unknown';
                $cid = $s['client_id'] ?? 'unknown';
                $seq = $s['seq'] ?? '-';
                $t = isset($s['created_at_ms']) ? date('H:i:s', (int) ($s['created_at_ms'] / 1000)) : '?';
                echo "   [{$t} | seq:{$seq}] Type: {$type} | Role: {$role} | Sender: {$sender} | Client: {$cid}\n";
            }
            echo "\n";
        }
        echo "\nПоследние 150 строк laravel.log:\n";
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $lines = array_slice(file($logPath), -150);
            echo htmlspecialchars(implode('', $lines));
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8;'>← В меню</a></pre>";
        exit;
    }

    // ── ACTION: Inspect lesson ───────────────────────────────────────
    if ($action === 'inspect_lesson') {
        echo "📋 <b>Информация об уроке и пользователях:</b>\n\n";
        $lid = (int) ($_GET['lesson_id'] ?? 23);
        $lesson = Lesson::find($lid);
        if ($lesson) {
            echo "Урок #{$lesson->id}:\n";
            echo "  Статус: {$lesson->status}\n";
            echo "  Tutor ID: {$lesson->tutor_id} ({$lesson->tutor?->name} / {$lesson->tutor?->email})\n";
            echo "  Student ID: {$lesson->student_id} ({$lesson->student?->name} / {$lesson->student?->email})\n";
            echo "  Start: {$lesson->start_time} | End: {$lesson->end_time}\n";
            $session = $lesson->activeClassroom ?? ClassroomSession::where('lesson_id', $lid)->latest('id')->first();
            if ($session) {
                echo "  ClassroomSession #{$session->id}: room_id={$session->room_id}, status={$session->status}, started_at={$session->started_at}\n";
            } else {
                echo "  ClassroomSession: отсутствует\n";
            }
        } else {
            echo "Урок #{$lid} не найден в БД.\n";
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8;'>← В меню</a></pre>";
        exit;
    }

    if ($action === 'view_log') {
        echo "📋 <b>Последние строки storage/logs/laravel.log:</b>\n\n";
        $logPath = __DIR__.'/../storage/logs/laravel.log';
        if (file_exists($logPath)) {
            $content = file_get_contents($logPath);
            $chunk = substr($content, -40000);
            echo htmlspecialchars($chunk);
        } else {
            echo "Файл лога не найден: {$logPath}\n";
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8;'>← В меню</a></pre>";
        exit;
    }

    // ── ACTION: Run migrations (default) ──────────────────────────────
    echo "🛠️ <b>Запуск миграций и оптимизации базы данных...</b>\n\n";
    $db = $app->make('db');

    try {
        $hasPromoCodes = ! empty($db->select("SHOW TABLES LIKE 'promo_codes'"));
        if ($hasPromoCodes) {
            $existingCols = $db->select('SHOW COLUMNS FROM promo_codes');
            $colNames = array_map(fn ($c) => $c->Field, $existingCols);
            $db->statement("ALTER TABLE promo_codes MODIFY discount_type VARCHAR(32) NOT NULL DEFAULT 'percent'");
            $db->statement('ALTER TABLE promo_codes MODIFY discount_value DECIMAL(10, 2) NOT NULL DEFAULT 0.00');
            if (! in_array('subscription_period', $colNames, true)) {
                $db->statement('ALTER TABLE promo_codes ADD COLUMN subscription_period VARCHAR(32) NULL AFTER scope');
            }
            if (! in_array('subscription_plan', $colNames, true)) {
                $db->statement('ALTER TABLE promo_codes ADD COLUMN subscription_plan VARCHAR(32) NULL AFTER subscription_period');
            }
            echo "   ✓ Таблица promo_codes обновлена.\n";
        }
    } catch (Throwable $e) {
        echo '   ⚠️ '.$e->getMessage()."\n";
    }

    echo "\n⚡ <b>Запуск php artisan migrate --force:</b>\n";
    $kernel->call('migrate', ['--force' => true]);
    echo $kernel->output();

    echo "\n⚡ <b>Очистка системного кэша Laravel:</b>\n";
    $kernel->call('optimize:clear');
    echo $kernel->output();

    echo "\n🎉 <b>ВСЕ ОПЕРАЦИИ ВЫПОЛНЕНЫ!</b>\n";
    echo "<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в панель обслуживания</a>\n";

} catch (Throwable $e) {
    echo "\n❌ <b>ОШИБКА:</b> ".htmlspecialchars($e->getMessage())."\n\n";
    echo htmlspecialchars($e->getTraceAsString());
}

echo '</pre>';
