# Security Audit Report — edusfera.by

**Дата**: 2026-06-14
**Методология**: Anthropic Cybersecurity Skills (MITRE ATT&CK, OWASP Top 10)
**Цель**: Laravel 12 модульный монолит

---

## Executive Summary

| Severity | Count |
|----------|-------|
| CRITICAL | 4 |
| HIGH | 5 |
| MEDIUM | 8 |
| LOW | 5 |

**Ключевые проблемы**: отсутствие security headers, optional webhook signatures, IDOR в ClassroomController, plaintext cookie для аккаунтов.

---

## CRITICAL Findings

### C1. Отсутствие Security Headers

Нет ни одного security header middleware:
- `Strict-Transport-Security` (HSTS) — **MISSING**
- `Content-Security-Policy` (CSP) — **MISSING**
- `X-Frame-Options` — **MISSING**
- `X-Content-Type-Options` — **MISSING**
- `Referrer-Policy` — **MISSING**

**Файл**: `bootstrap/app.php` — нет middleware для headers
**Риск**: Clickjacking, XSS, MIME sniffing, SSL stripping

**Fix**: Создать `app/Http/Middleware/SecurityHeaders.php`:
```php
class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        return $response;
    }
}
```

### C2. SESSION_SECURE_COOKIE не задан

`.env` не содержит `SESSION_SECURE_COOKIE=true`. Cookies отправляются по HTTP.

**Fix**: Добавить в `.env.production`:
```
SESSION_SECURE_COOKIE=true
```

### C3. Payment Webhook Signature — Optional

`PAYMENT_WEBHOOK_REQUIRE_SIGNATURE=false` по умолчанию. Webhook принимает поддельные запросы.

**Fix**: В `.env.production`:
```
PAYMENT_WEBHOOK_REQUIRE_SIGNATURE=true
PAYMENT_WEBHOOK_REQUIRE_IP_ALLOWLIST=true
PAYMENT_WEBHOOK_ALLOWED_IPS=<ip_платёжного_шлюза>
```

### C4. .env с реальными секретами на диске

`.env` содержит `APP_KEY`, `DB_PASSWORD=secret`, `SITE_ADMIN_PASSWORD=TechAdmin123!`, `CLASSROOM_JWT_SECRET`.

**Fix**: Ротировать все ключи перед деплоем, добавить `.env` в backup exclusion.

---

## HIGH Findings

### H1. IDOR — ClassroomController::downloadFile()

Файл привязан к уроку через route-model binding, но **не проверяется** принадлежит ли файл к session этого урока.

```
GET /classroom/1/files/99/download  ← файл 99 может принадлежать другому уроку
```

**Файл**: `app/Http/Controllers/ClassroomController.php:176`

**Fix**: Добавить проверку:
```php
abort_if($file->classroom_session_id !== $lesson->activeClassroom?->id, 403);
```

### H2. Plaintext Cookie для Account Switching

`MultiAccountService` хранит ID связанных аккаунтов в plaintext cookie (`edusfera_linked_ids`).

**Файл**: `app/Services/MultiAccountService.php:14`

**Fix**: Использовать encrypted/signed cookie:
```php
Cookie::queue(Cookie::make('edusfera_linked_ids', $ids, 43200, '/', null, true, true));
```

### H3. CORS: `allowed_origins: ['*']`

Нет `config/cors.php` — Laravel использует wildcard для всех origins.

**Fix**: Создать `config/cors.php`:
```php
return [
    'paths' => ['api/*'],
    'allowed_origins' => ['https://edusfera.by'],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

### H4. Account Switch Routes без auth middleware + CSRF bypass

`/account/switch` и `/account/add` не имеют `auth` middleware, а CSRF исключён.

**Файл**: `bootstrap/app.php:76-78`, `routes/web.php:39-42`

**Fix**: Добавить `auth` middleware на эти routes.

### H5. 12+ моделей без Policy classes

Только 3 из ~15 моделей имеют Policy. Classroom-related модели используют ad-hoc проверки в контроллерах.

**Модели без Policy**: TutorProfile, ClassroomSession, ClassroomFile, ClassroomNote, ClassroomChatMessage, HomeworkAssignment, StudentGoal, ProgressSnapshot, SkillGap, DiagnosticAttempt, StudentBalance, Conversation, TutorAvailability

---

## MEDIUM Findings

### M1. TutorProfile: sensitive fields в $fillable

`is_verified`, `verification_status`, `rating_avg` в `$fillable` — риск self-verification.

**Файл**: `app/Models/TutorProfile.php:37-43`

### M2. Classroom Internal Endpoint: hardcoded Bearer token

`POST /api/internal/classroom/{roomId}/whiteboard` использует статический Bearer token.

**Файл**: `app/Http/Controllers/ClassroomController.php:453`

### M3. File Upload: mimes vs mimetypes

`mimes` rule проверяет только расширение, не MIME type. Возможна загрузка исполняемого файла с расширением `.jpg`.

**Файл**: `app/Http/Controllers/ClassroomController.php:147`

### M4. File Download: path traversal потенциал

`$file->path` из БД используется в `storage_path()` без `realpath()` проверки.

**Файл**: `app/Http/Controllers/ClassroomController.php:184`

### M5. Chat Message XSS: хрупкий паттерн

`{!! $this->formatMessage($message) !!}` — безопасен сейчас, но `formatMessage()` вызывает `sanitizeMessageForDisplay()` + `e()`. Будущий рефакторинг может сломать.

**Файл**: `resources/views/filament/pages/messages-page.blade.php:752`

### M6. APP_DEBUG=true

Отладочная информация утекает в error pages.

### M7. No global rate limiting

Rate limiting только на отдельных routes, нет глобального.

### M8. SITE_ADMIN_PASSWORD=TechAdmin123!

Угадываемый пароль администратора.

---

## LOW Findings

### L1. Checkout canAccessLesson() исключает tutor

`CheckoutController` не проверяет `$lesson->tutor_id`, хотя `LessonPolicy::view()` его включает.

### L2. Checkout canAccessLesson() исключает parent

Аналогично — parent не имеет доступа к checkout.

### L3. readfile() without header sanitization

`$file->original_name` используется в `Content-Disposition` без санитизации newlines.

### L4. Checkout CanAccessLesson inconsistent with LessonPolicy

Разная логика проверки доступа.

### L5. No rate limiting on internal whiteboard endpoint

`POST /api/internal/classroom/{roomId}/whiteboard` без throttle.

---

## Что хорошо (безопасно)

| Проверка | Статус |
|----------|--------|
| Все 22 Eloquent модели имеют `$fillable` | ✅ |
| Нет `Model::unguard()` или `$guarded = []` | ✅ |
| Нет `forceCreate()` | ✅ |
| Контроллеры валидируют input перед `create()` | ✅ |
| SQL запросы параметризованы | ✅ |
| Нет command injection | ✅ |
| Нет SSRF | ✅ |
| Нет unsafe deserialization | ✅ |
| Site-Admin panel: role + email双重 check | ✅ |
| Internal API: auth:api + scope middleware | ✅ |
| Webhook signature verification реализован | ✅ (но optional) |
| Chat message sanitizer вызывает `e()` | ✅ |

---

## Priority Actions

| # | Действие | Severity | Сложность |
|---|----------|----------|-----------|
| 1 | Создать SecurityHeaders middleware | CRITICAL | Низкая |
| 2 | Задать SESSION_SECURE_COOKIE=true | CRITICAL | Низкая |
| 3 | Сделать webhook signature mandatory | CRITICAL | Низкая |
| 4 | Ротировать секреты | CRITICAL | Средняя |
| 5 | Исправить IDOR в downloadFile() | HIGH | Низкая |
| 6 | Зашифровать account switch cookie | HIGH | Низкая |
| 7 | Создать config/cors.php | HIGH | Низкая |
| 8 | Добавить auth middleware на account routes | HIGH | Низкая |
| 9 | Добавить Policies для Classroom моделей | HIGH | Средняя |
| 10 | Убрать sensitive fields из TutorProfile $fillable | MEDIUM | Низкая |
