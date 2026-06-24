<?php
header('Content-Type: application/json; charset=utf-8');

// --- НАСТРОЙКИ ДЛЯ ХОСТИНГА (впишите ваши данные перед загрузкой) ---
define('GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbyJv0SYgfdYbvzoygAjnQWV3ufonH8L2p1QuHVFSjWyYdbt4M_t2EXEuKwq5DX3IJmS/exec');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl'); // 'ssl' или 'tls'
define('SMTP_USER', 'edusferaby@gmail.com');
define('SMTP_PASS', 'ttvkqdwzfqawfqas'); // Пароль приложений Google
define('SMTP_FROM_EMAIL', 'edusferaby@gmail.com');
define('SMTP_FROM_NAME', 'Edusfera');
// ---------------------------------------------------------------------

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

// Получаем JSON из тела запроса
$input = json_decode(file_get_contents('php://input'), true);

$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$role = isset($input['role']) ? trim($input['role']) : '';
$subject = isset($input['subject']) ? trim($input['subject']) : '';

// Простая валидация
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Пожалуйста, введите ваше имя']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Пожалуйста, введите корректный email']);
    exit;
}

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

$ch = curl_init(GOOGLE_SCRIPT_URL);
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
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // Получатели
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
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
    } else {
        // Резервный текстовый контент, если файл шаблона не найден
        $mail->isHTML(false);
        $mail->Body = "Здравствуйте, {$name}!\n\nСпасибо за регистрацию. Ваш инвайт-код: {$inviteCode}";
    }

    // Служебные заголовки для уменьшения спам-рейтинга
    $mail->addCustomHeader('List-Unsubscribe', '<mailto:support@edusfera.by>, <https://edusfera.by>');
    $mail->addCustomHeader('Precedence', 'bulk');
    $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');

    $mail->send();
} catch (Exception $e) {
    // Записываем ошибку в системный лог хостинга, чтобы не ломать выполнение фронтенда
    error_log('Ошибка отправки email через PHPMailer: ' . $mail->ErrorInfo);
}

// Возвращаем успешный ответ клиенту вместе со сгенерированным инвайт-кодом
echo json_encode([
    'status' => 'success',
    'inviteCode' => $inviteCode
]);
