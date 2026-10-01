<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\ResetPasswordCodeNotification;
use App\Services\Payment\PaymentGatewayInterface;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuroraAuthController extends Controller
{
    /**
     * Bcrypt-хеш случайной строки: проверка пароля при ненайденном пользователе,
     * чтобы время ответа не позволяло перечислять существующие e-mail/телефоны.
     */
    private const DUMMY_PASSWORD_HASH = '$2y$12$9JLUpOtGlHXEoLFduvJVouKfocFAhtWTAH2G8DXQk3ifVWw1C9L/q';

    public function showAuthPage(Request $request)
    {
        $isSiteAdmin = str_contains($request->path(), 'site-admin') || $request->routeIs('filament.site-admin.*');

        if (Auth::check() || Filament::auth()->check()) {
            $user = Auth::user() ?? Filament::auth()->user();
            if ($isSiteAdmin && $user && $user->isAdmin()) {
                return redirect('/site-admin');
            }

            return redirect('/admin');
        }

        return view('auth.register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $login = trim($credentials['email']);
        $user = null;

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($login)])->first();
        } else {
            $cleanLogin = mb_strtolower($login);

            // Handle Admin & TechAdmin aliases
            if (in_array($cleanLogin, ['admin', 'administrator', 'админ', 'администратор'], true)) {
                $user = User::where('email', 'admin@edusfera.by')
                    ->orWhere(function ($q) {
                        $q->where('role', 'admin')->orWhere('role', UserRole::Admin);
                    })
                    ->first();
            } elseif (in_array($cleanLogin, ['tech-admin', 'tech_admin', 'techadmin', 'техадмин', 'техадминистратор'], true)) {
                $techEmail = mb_strtolower((string) config('site_admin.email', 'tech-admin@edusfera.by'));
                $user = User::where('email', $techEmail)->first()
                    ?? User::where('email', 'tech-admin@edusfera.by')->first();
            } else {
                // Prefix match before @ (e.g. "admin" -> "admin@edusfera.by")
                $user = User::whereRaw('LOWER(email) = ?', [$cleanLogin.'@edusfera.by'])->first()
                    ?? User::whereRaw('LOWER(email) LIKE ?', [$cleanLogin.'@%'])->first();
            }

            if (! $user) {
                $normalizedPhone = $this->normalizePhone($login);
                $rawDigits = preg_replace('/\D/', '', $login) ?? '';
                if ($normalizedPhone !== '' && $rawDigits !== '') {
                    $user = User::query()
                        ->where('phone', $normalizedPhone)
                        ->orWhere('phone', ltrim($normalizedPhone, '+'))
                        ->orWhere('phone', $rawDigits)
                        ->first();
                }
            }
        }

        $passwordValid = false;
        if ($user && Hash::check($credentials['password'], $user->password)) {
            $passwordValid = true;
        }

        if (! $user || ! $passwordValid) {
            // Выравнивание времени ответа (анти-enumeration).
            Hash::check($credentials['password'], self::DUMMY_PASSWORD_HASH);

            return response()->json([
                'success' => false,
                'message' => 'Неверный email или пароль.',
            ], 422);
        }

        Auth::login($user, true);
        Filament::auth()->login($user, true);

        $request->session()->regenerate();

        $guard = Auth::guard('web');
        $hash = method_exists($guard, 'hashPasswordForCookie')
            ? $guard->hashPasswordForCookie($user->getAuthPassword())
            : $user->getAuthPassword();
        $request->session()->put('password_hash_web', $hash);

        // Determine destination redirect
        $referer = (string) $request->header('referer', '');
        $requestedRedirect = $request->input('redirect') ?? $request->query('redirect');

        $isSiteAdminContext = str_contains($referer, 'site-admin')
            || str_contains((string) $request->path(), 'site-admin')
            || (is_string($requestedRedirect) && str_contains($requestedRedirect, 'site-admin'));

        $redirect = '/admin';
        if ($user->isAdmin() && $isSiteAdminContext) {
            $redirect = '/site-admin';
        } elseif ($requestedRedirect && ! str_starts_with($requestedRedirect, '//')) {
            $redirect = $requestedRedirect;
        }

        return response()->json([
            'success' => true,
            'redirect' => $redirect,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role,
            ],
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'role' => ['nullable', 'string', 'in:student,tutor,parent'],
            'plan' => ['nullable', 'string', 'in:start,pro,basic,premium'],
        ], [
            'email.unique' => 'Пользователь с таким e-mail уже зарегистрирован. Пожалуйста, войдите в аккаунт или восстановите пароль.',
            'email.required' => 'Пожалуйста, укажите контактный e-mail.',
            'email.email' => 'Пожалуйста, введите корректный адрес электронной почты.',
            'phone.unique' => 'Пользователь с таким номером телефона уже зарегистрирован.',
            'firstName.required' => 'Пожалуйста, укажите ваше имя.',
            'password.required' => 'Пожалуйста, задайте пароль.',
            'password.min' => 'Пароль должен содержать не менее 8 символов.',
        ]);

        $name = trim($data['firstName'].' '.($data['lastName'] ?? ''));
        $rawPhone = $data['phone'] ?? null;
        $phone = $rawPhone ? $this->normalizePhone((string) $rawPhone) : null;

        $user = User::create([
            'name' => $name,
            'email' => mb_strtolower(trim($data['email'])),
            'phone' => $phone,
            'password' => Hash::make($data['password']),
            'offer_accepted_at' => now(),
        ]);

        $userRole = UserRole::tryFrom($data['role'] ?? 'student') ?? UserRole::Student;
        $user->role = $userRole;
        $user->save();

        if ($userRole === UserRole::Tutor) {
            TutorProfile::firstOrCreate([
                'user_id' => $user->id,
            ]);

            $planInput = $data['plan'] ?? 'pro';
            $plan = match ($planInput) {
                'start', 'basic' => SubscriptionPlan::START,
                'pro' => SubscriptionPlan::PRO,
                'premium' => SubscriptionPlan::PREMIUM,
                default => SubscriptionPlan::PRO,
            };

            $sub = app(SubscriptionService::class)->ensureTrialStarted($user, $plan);
            $sub->update(['is_onboarded' => false]);
        }

        Auth::login($user, true);
        Filament::auth()->login($user, true);

        $request->session()->regenerate();

        $guard = Auth::guard('web');
        $hash = method_exists($guard, 'hashPasswordForCookie')
            ? $guard->hashPasswordForCookie($user->getAuthPassword())
            : $user->getAuthPassword();
        $request->session()->put('password_hash_web', $hash);

        return response()->json([
            'success' => true,
            'role' => $userRole->value,
            'isTutor' => $userRole === UserRole::Tutor,
            'redirect' => $userRole === UserRole::Tutor
                ? '/admin/tutor-subscription-page?onboarding=1'
                : '/admin',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $userRole->value,
            ],
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '80') && strlen($digits) === 11) {
            $digits = '375'.substr($digits, 2);
        }

        if (strlen($digits) === 9 && in_array(substr($digits, 0, 2), ['29', '33', '44', '25', '17'], true)) {
            $digits = '375'.$digits;
        }

        if (str_starts_with($digits, '375')) {
            return '+'.$digits;
        }

        return $digits !== '' ? '+'.$digits : '';
    }

    public function initSubscriptionAlfaSdk(Request $request, PaymentGatewayInterface $gateway): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован как репетитор.'], 401);
        }

        $data = $request->validate([
            'plan' => ['required', 'string', 'in:basic,pro,premium'],
            'isYearly' => ['nullable', 'boolean'],
        ]);

        $plan = SubscriptionPlan::from($data['plan']);
        $subscription = Subscription::where('tutor_id', $user->id)->first();

        if ($subscription) {
            $subscription->update(['plan' => $plan]);
        } else {
            $subscription = app(SubscriptionService::class)->startTrial($user, $plan);
        }

        $result = $gateway->createPayment([
            'amount' => 0.00,
            'subscription' => true,
            'user_id' => $user->id,
            'plan' => $plan->value,
            'description' => 'Привязка карты и 30 дней триала Edusfera (Тариф '.$plan->title().')',
        ]);

        return response()->json([
            'success' => true,
            'mdOrder' => $result['mdOrder'] ?? $result['gateway_transaction_id'] ?? null,
            'web_sdk_url' => $result['web_sdk_url'] ?? config('payments.alfabank.web_sdk_url'),
            'api_context' => $result['api_context'] ?? config('payments.alfabank.api_context', '/payment'),
            'redirect_url' => $result['redirect_url'] ?? '/admin',
            'amount' => '0.00',
            'currency' => 'BYN',
        ]);
    }

    public function sendResetCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = mb_strtolower(trim($data['email']));
        Log::info('[PasswordReset] 1. Получен запрос на сброс пароля', [
            'email' => $email,
            'ip' => $request->ip(),
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            Log::warning('[PasswordReset] ❌ Пользователь с таким email не найден в базе данных', [
                'email' => $email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Пользователь с адресом '.$email.' не найден. Проверьте правильность написания email.',
            ], 422);
        }

        Log::info('[PasswordReset] 2. Пользователь найден', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $code = sprintf('%06d', random_int(100000, 999999));

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        Log::info("[PasswordReset] 🔑 СГЕНЕРИРОВАН КОД ВОССТАНОВЛЕНИЯ: {$code} (email: {$user->email})");

        Log::info('[PasswordReset] 3. Отправка письма через почтовый драйвер...', [
            'default_mailer' => config('mail.default'),
            'smtp_host' => config('mail.mailers.smtp.host'),
            'smtp_port' => config('mail.mailers.smtp.port'),
            'smtp_username' => config('mail.mailers.smtp.username'),
            'smtp_encryption' => config('mail.mailers.smtp.encryption'),
            'smtp_scheme' => config('mail.mailers.smtp.scheme'),
            'mail_from' => config('mail.from.address'),
        ]);

        try {
            $user->notify(new ResetPasswordCodeNotification($code));
            Log::info("[PasswordReset] ✅ Письмо с кодом успешно передано почтовому серверу для {$email}");
        } catch (\Throwable $e) {
            Log::error('[PasswordReset] ❌ ОШИБКА ОТПРАВКИ ПИСЬМА: '.$e->getMessage(), [
                'email' => $email,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось доставить письмо. Ошибка почтового сервера: '.$e->getMessage().
                    ' (Код зафиксирован в логах системы).',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Код подтверждения отправлен на вашу почту.',
        ]);
    }

    public function verifyResetCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string'],
        ]);

        $email = mb_strtolower(trim($data['email']));
        $code = trim($data['code']);

        Log::info('[PasswordReset] Запрос на проверку кода', [
            'email' => $email,
            'code_entered' => $code,
        ]);

        $record = DB::table('password_reset_tokens')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $record) {
            Log::warning('[PasswordReset] Токен не найден в БД для email', ['email' => $email]);

            return response()->json([
                'success' => false,
                'message' => 'Код не найден или истёк. Запросите код повторно.',
            ], 422);
        }

        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->whereRaw('LOWER(email) = ?', [$email])->delete();
            Log::warning('[PasswordReset] Срок действия кода истёк', ['email' => $email, 'created_at' => $record->created_at]);

            return response()->json([
                'success' => false,
                'message' => 'Срок действия кода истёк (15 минут). Запросите новый код.',
            ], 422);
        }

        if (! Hash::check($code, $record->token)) {
            Log::warning('[PasswordReset] Введён неверный код', ['email' => $email, 'entered' => $code]);

            return response()->json([
                'success' => false,
                'message' => 'Неверный код подтверждения. Проверьте цифры из письма.',
            ], 422);
        }

        Log::info('[PasswordReset] ✅ Код успешно подтверждён', ['email' => $email]);

        return response()->json([
            'success' => true,
            'message' => 'Код успешно подтверждён.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'email.required' => 'Укажите email.',
            'email.email' => 'Некорректный формат email.',
            'code.required' => 'Укажите код подтверждения.',
            'password.required' => 'Введите новый пароль.',
            'password.confirmed' => 'Пароли не совпадают.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'password' => 'Пароль должен содержать минимум 8 символов, включая буквы и цифры.',
        ]);

        $email = mb_strtolower(trim($data['email']));
        $code = trim($data['code']);

        Log::info('[PasswordReset] Запрос на установку нового пароля', ['email' => $email]);

        $record = DB::table('password_reset_tokens')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $record) {
            Log::warning('[PasswordReset] Запрос на сброс не найден в БД', ['email' => $email]);

            return response()->json([
                'success' => false,
                'message' => 'Запрос на сброс пароля не найден. Начните сначала.',
            ], 422);
        }

        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->whereRaw('LOWER(email) = ?', [$email])->delete();
            Log::warning('[PasswordReset] Срок действия кода истёк при сохранении пароля', ['email' => $email]);

            return response()->json([
                'success' => false,
                'message' => 'Срок действия кода истёк. Запросите новый код.',
            ], 422);
        }

        if (! Hash::check($code, $record->token)) {
            Log::warning('[PasswordReset] Неверный код при сохранении пароля', ['email' => $email]);

            return response()->json([
                'success' => false,
                'message' => 'Неверный код подтверждения.',
            ], 422);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            Log::error('[PasswordReset] Пользователь не найден при сохранении пароля', ['email' => $email]);

            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден.',
            ], 404);
        }

        // Проверка: новый пароль не должен совпадать с текущим
        if (Hash::check($data['password'], $user->password)) {
            Log::warning('[PasswordReset] Новый пароль совпадает с текущим', ['email' => $email]);

            return response()->json([
                'success' => false,
                'message' => 'Новый пароль не должен совпадать с текущим паролем. Придумайте другой пароль.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        DB::table('password_reset_tokens')->whereRaw('LOWER(email) = ?', [$email])->delete();

        Log::info('[PasswordReset] ✅ Пароль успешно обновлён в БД', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // Автоматически авторизуем пользователя
        Auth::login($user, true);
        Filament::auth()->login($user, true);
        $request->session()->regenerate();

        $guard = Auth::guard('web');
        $hash = method_exists($guard, 'hashPasswordForCookie')
            ? $guard->hashPasswordForCookie($user->getAuthPassword())
            : $user->getAuthPassword();
        $request->session()->put('password_hash_web', $hash);

        return response()->json([
            'success' => true,
            'message' => 'Пароль успешно изменён! Вы вошли в систему.',
            'redirect' => '/admin',
        ]);
    }
}
