<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuroraAuthController extends Controller
{
    /**
     * Bcrypt-хеш случайной строки: проверка пароля при ненайденном пользователе,
     * чтобы время ответа не позволяло перечислять существующие e-mail/телефоны.
     */
    private const DUMMY_PASSWORD_HASH = '$2y$12$9JLUpOtGlHXEoLFduvJVouKfocFAhtWTAH2G8DXQk3ifVWw1C9L/q';

    public function showAuthPage()
    {
        if (Auth::check() || Filament::auth()->check()) {
            return redirect('/admin');
        }

        return view('auth.register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['email']);
        $user = null;

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($login)])->first();
        } else {
            $normalizedPhone = $this->normalizePhone($login);
            $rawDigits = preg_replace('/\D/', '', $login) ?? '';
            if ($normalizedPhone !== '') {
                $user = User::query()
                    ->where('phone', $normalizedPhone)
                    ->orWhere('phone', ltrim($normalizedPhone, '+'))
                    ->orWhere('phone', $rawDigits)
                    ->first();
            }
        }

        if (! $user) {
            // Выравнивание времени ответа (анти-enumeration).
            Hash::check($credentials['password'], self::DUMMY_PASSWORD_HASH);

            return response()->json([
                'success' => false,
                'message' => 'Неверный e-mail/телефон или пароль.',
            ], 422);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный e-mail/телефон или пароль.',
            ], 422);
        }

        Auth::login($user, true);
        Filament::auth()->login($user, true);

        $request->session()->regenerate();
        $request->session()->put('password_hash_web', $user->getAuthPassword());

        return response()->json([
            'success' => true,
            'redirect' => '/admin',
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
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'role' => ['nullable', 'string', 'in:student,tutor,parent'],
            'plan' => ['nullable', 'string', 'in:start,pro,basic,premium'],
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
            \App\Models\TutorProfile::firstOrCreate([
                'user_id' => $user->id,
            ]);

            $planInput = $data['plan'] ?? 'pro';
            $plan = match ($planInput) {
                'start', 'basic' => \App\Domain\Subscription\Enums\SubscriptionPlan::START,
                'pro' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
                'premium' => \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM,
                default => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
            };

            app(\App\Domain\Subscription\Services\SubscriptionService::class)->ensureTrialStarted($user, $plan);
        }

        Auth::login($user, true);
        Filament::auth()->login($user, true);

        $request->session()->regenerate();
        $request->session()->put('password_hash_web', $user->getAuthPassword());

        return response()->json([
            'success' => true,
            'role' => $userRole->value,
            'isTutor' => $userRole === UserRole::Tutor,
            'redirect' => '/admin',
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

        if (str_starts_with($digits, '375')) {
            return '+'.$digits;
        }

        return $digits !== '' ? '+'.$digits : '';
    }

    public function confirmPlan(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован как репетитор.'], 401);
        }

        $data = $request->validate([
            'plan' => ['required', 'string', 'in:basic,pro,premium'],
            'isYearly' => ['nullable', 'boolean'],
            'paymentMethod' => ['nullable', 'string', 'in:alfa,erip'],
        ]);

        $plan = \App\Domain\Subscription\Enums\SubscriptionPlan::from($data['plan']);
        $subscription = \App\Domain\Subscription\Models\Subscription::where('tutor_id', $user->id)->first();

        if ($subscription) {
            $subscription->update(['plan' => $plan]);
        } else {
            $subscription = app(\App\Domain\Subscription\Services\SubscriptionService::class)->startTrial($user, $plan);
        }

        app(\App\Domain\Subscription\Services\SubscriptionService::class)->createInvoice(
            $subscription,
            $plan,
            ($data['isYearly'] ?? false) ? 12 : 1
        );

        return response()->json([
            'success' => true,
            'redirect' => '/admin',
            'message' => 'Тариф успешно активирован! 30 дней бесплатного пробного периода начались.',
        ]);
    }

    public function initSubscriptionAlfaSdk(Request $request, \App\Services\Payment\PaymentGatewayInterface $gateway): \Illuminate\Http\JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован как репетитор.'], 401);
        }

        $data = $request->validate([
            'plan' => ['required', 'string', 'in:basic,pro,premium'],
            'isYearly' => ['nullable', 'boolean'],
        ]);

        $plan = \App\Domain\Subscription\Enums\SubscriptionPlan::from($data['plan']);
        $subscription = \App\Domain\Subscription\Models\Subscription::where('tutor_id', $user->id)->first();

        if ($subscription) {
            $subscription->update(['plan' => $plan]);
        } else {
            $subscription = app(\App\Domain\Subscription\Services\SubscriptionService::class)->startTrial($user, $plan);
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
}

