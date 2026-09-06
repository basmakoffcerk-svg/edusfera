<?php
header('Content-Type: application/json; charset=utf-8');

// --- ЗАГРУЗКА НАСТРОЕК ИЗ .ENV (ДЛЯ БЕЗОПАСНОСТИ СЕКРЕТОВ) ---
function loadEnv() {
    $paths = [
        __DIR__ . '/../../.env',
        __DIR__ . '/../.env',
        __DIR__ . '/.env'
    ];
    
    $appName = 'Edusfera';
    foreach ($paths as $path) {
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    $value = trim($value, '"\'');
                    
                    if ($key === 'APP_NAME' && !empty($value)) {
                        $appName = $value;
                    }
                    
                    // Резолвим плейсхолдеры вроде ${APP_NAME}
                    $value = str_replace('${APP_NAME}', $appName, $value);
                    
                    // Перезаписываем или устанавливаем значения в $_ENV, $_SERVER и getenv
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
            break; // Останавливаемся на первом найденном файле
        }
    }
}
loadEnv();

$getEnvVar = function($key, $default = '') {
    if (!empty($_ENV[$key])) return $_ENV[$key];
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
};

// Конфигурация с fallback значениями
$googleScriptUrl = $getEnvVar('PROMO_GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbyJv0SYgfdYbvzoygAjnQWV3ufonH8L2p1QuHVFSjWyYdbt4M_t2EXEuKwq5DX3IJmS/exec');

$smtpHost = $getEnvVar('MAIL_HOST', 'smtp.gmail.com');
$smtpPort = (int)$getEnvVar('MAIL_PORT', 465);
$smtpSecure = $getEnvVar('MAIL_ENCRYPTION', 'ssl');
$smtpUser = $getEnvVar('MAIL_USERNAME', 'edusferaby@gmail.com');
$smtpPass = $getEnvVar('MAIL_PASSWORD', '');
$smtpFromEmail = $getEnvVar('MAIL_FROM_ADDRESS', 'edusferaby@gmail.com');
$smtpFromName = $getEnvVar('MAIL_FROM_NAME', 'Edusfera');
if ($smtpFromName === '${APP_NAME}') {
    $smtpFromName = $getEnvVar('APP_NAME', 'Edusfera');
}
// -------------------------------------------------------------
// -------------------------------------------------------------

// Подключаем автономный PHPMailer
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Запускаем сессию для Rate Limiting
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Rate Limiting: отправка не чаще раза в 15 секунд с одного IP/сессии
$currentTime = time();
if (isset($_SESSION['last_submit_time']) && ($currentTime - $_SESSION['last_submit_time']) < 15) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Слишком много запросов. Пожалуйста, подождите 15 секунд перед следующей отправкой.']);
    exit;
}

// Получаем JSON из тела запроса
$input = json_decode(file_get_contents('php://input'), true);

// Honeypot защита от спам-ботов
if (!empty($input['mid_name'])) {
    // Имитируем успешный ответ для спам-бота, но прерываем выполнение без отправки
    echo json_encode([
        'status' => 'success',
        'inviteCode' => 'EDUSFERA-' . strtoupper(bin2hex(random_bytes(2))) . '-2026'
    ]);
    exit;
}

$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$role = isset($input['role']) ? trim($input['role']) : '';
$subject = isset($input['subject']) ? trim($input['subject']) : '';

// Ограничение по длине полей (защита от переполнения буфера / DOS)
if (strlen($name) > 100 || strlen($email) > 100 || strlen($phone) > 25 || strlen($role) > 20 || strlen($subject) > 100) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Превышена допустимая длина полей']);
    exit;
}

// Валидация роли
$allowedRoles = ['parent', 'tutor'];
if (!in_array($role, $allowedRoles)) {
    $role = 'parent';
}

// Валидация имени
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Пожалуйста, введите ваше имя']);
    exit;
}

// Валидация Email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Пожалуйста, введите корректный email']);
    exit;
}

// Валидация Телефона (Беларусь)
$cleanPhone = preg_replace('/[\s\(\)\-]/', '', $phone);
if (!preg_match('/^\+375\d{9}$/', $cleanPhone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Пожалуйста, введите корректный телефон в формате +375 (XX) XXX-XX-XX']);
    exit;
}

// Защита от Formula Injection в Google Sheets
// Удаляем символы '=', '+', '-', '@' в начале полей
function sanitizeFormula($str) {
    return ltrim($str, '=+-@');
}

$name = sanitizeFormula($name);
$phone = sanitizeFormula($phone);
$role = sanitizeFormula($role);
$subject = sanitizeFormula($subject);

// Генерация уникального инвайт-кода
$inviteCode = 'EDUSFERA-' . strtoupper(bin2hex(random_bytes(2))) . '-2026';

// 1. Отправка данных в Google Таблицу через cURL
$payload = json_encode([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'role' => $role,
    'subject' => $subject,
    'inviteCode' => $inviteCode
]);

$ch = curl_init($googleScriptUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Для следования редиректам Apps Script
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// 2. Отправка приветственного HTML-письма через PHPMailer
$mail = new PHPMailer(true);

try {
    // Настройки SMTP сервера
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = $smtpSecure;
    $mail->Port       = $smtpPort;
    $mail->CharSet    = 'UTF-8';

    // Получатели
    $mail->setFrom($smtpFromEmail, $smtpFromName);
    $mail->addAddress($email, $name);

    // Контент
    $mail->isHTML(true);
    $mail->Subject = 'Ваш инвайт-код Edusfera';
    
    // Загружаем HTML-шаблон письма
    $templatePath = __DIR__ . '/mail-template.html';
    if (file_exists($templatePath)) {
        $htmlContent = file_get_contents($templatePath);
        // Заменяем плейсхолдеры на реальные данные лида
        $htmlContent = str_replace('{{name}}', htmlspecialchars($name), $htmlContent);
        $htmlContent = str_replace('{{inviteCode}}', $inviteCode, $htmlContent);
        $mail->Body = $htmlContent;
        
        // Текстовая альтернатива для алгоритмов антиспама (Gmail require AltBody)
        $mail->AltBody = "Здравствуйте, {$name}!\n\nСпасибо за интерес к Edusfera.\nВаш личный инвайт-код: {$inviteCode}\n\nОфициальный сайт: https://edusfera.by/";
    } else {
        // Резервный текстовый контент, если файл шаблона не найден
        $mail->isHTML(false);
        $mail->Body = "Здравствуйте, {$name}!\n\nСпасибо за регистрацию. Ваш инвайт-код: {$inviteCode}";
    }

    $mail->send();
} catch (Exception $e) {
    // Записываем подробную информацию об ошибке в системный лог
    error_log('Ошибка отправки email через PHPMailer: ' . $e->getMessage());
}

// Записываем время успешной отправки для Rate Limiting
$_SESSION['last_submit_time'] = time();

// Возвращаем успешный ответ клиенту вместе со сгенерированным инвайт-кодом
echo json_encode([
    'status' => 'success',
    'inviteCode' => $inviteCode
]);
