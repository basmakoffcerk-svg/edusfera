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
        if (Auth::check()) {
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
            $normalizedPhone = preg_replace('/[^\d+]/', '', $login);
            if (! empty($normalizedPhone)) {
                $user = User::where('phone', $normalizedPhone)->first();
            }
        }

        if (! $user) {
            // Выравнивание времени ответа (анти-enumeration).
            Hash::check($credentials['password'], self::DUMMY_PASSWORD_HASH);

            return response()->json([
                'success' => false,
                'message' => 'Неверный e-mail или пароль.',
            ], 422);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный e-mail или пароль.',
            ], 422);
        }

        Auth::login($user, true);
        Filament::auth()->login($user, true);

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'redirect' => '/admin',
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'role' => ['nullable', 'string', 'in:student,tutor,parent'],
            'plan' => ['nullable', 'string', 'in:basic,pro,premium'],
        ]);

        $name = trim($data['firstName'].' '.($data['lastName'] ?? ''));

        $user = User::create([
            'name' => $name,
            'email' => mb_strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'offer_accepted_at' => now(),
        ]);

        // `role` не входит в $fillable (защита от mass assignment), поэтому
        // раньше она молча отбрасывалась — все «репетиторы» регистрировались
        // как ученики. Роль валидирована выше (student|tutor|parent) и
        // сохраняется явно.
        $userRole = UserRole::from($data['role'] ?? 'student');
        $user->role = $userRole;
        $user->save();

        if ($userRole === UserRole::Tutor) {
            \App\Models\TutorProfile::firstOrCreate([
                'user_id' => $user->id,
            ]);

            $plan = \App\Domain\Subscription\Enums\SubscriptionPlan::tryFrom($data['plan'] ?? 'pro') 
                ?? \App\Domain\Subscription\Enums\SubscriptionPlan::PRO;

            app(\App\Domain\Subscription\Services\SubscriptionService::class)->startTrial($user, $plan);
        }

        Auth::login($user, true);
        Filament::auth()->login($user, true);

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'role' => $userRole->value,
            'isTutor' => $userRole === UserRole::Tutor,
            'redirect' => '/admin',
        ]);
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

