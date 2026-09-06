<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\TutorProfile;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google'];

    /**
     * Redirect user to OAuth provider.
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404, 'Неподдерживаемый OAuth провайдер.');
        }

        // Store role and plan in session for callback
        $role = $request->query('role', 'student');
        $plan = $request->query('plan', 'pro');

        session([
            'oauth_role' => in_array($role, ['student', 'tutor', 'parent'], true) ? $role : 'student',
            'oauth_plan' => in_array($plan, ['start', 'pro', 'basic', 'premium'], true) ? $plan : 'pro',
        ]);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle OAuth callback from provider.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404, 'Неподдерживаемый OAuth провайдер.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            Log::warning("OAuth error with {$provider}: ".$e->getMessage());

            return redirect()->route('login')->withErrors([
                'email' => 'Не удалось войти через '.ucfirst($provider).'. Попробуйте снова или используйте e-mail.',
            ]);
        }

        $email = $socialUser->getEmail();
        if (! $email) {
            return redirect()->route('login')->withErrors([
                'email' => 'Сервис '.ucfirst($provider).' не передал ваш e-mail. Авторизация невозможна.',
            ]);
        }

        $normalizedEmail = mb_strtolower(trim($email));
        $providerId = (string) $socialUser->getId();
        $providerIdField = $provider === 'yandex' ? 'yandex_id' : 'google_id';

        // 1. Find user by provider ID or email
        $user = User::where($providerIdField, $providerId)
            ->orWhereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        $isNewUser = false;

        if ($user) {
            // Update provider ID and avatar if not set
            $updates = [];
            if (! $user->{$providerIdField}) {
                $updates[$providerIdField] = $providerId;
            }
            if (! $user->avatar && $socialUser->getAvatar()) {
                $updates['avatar'] = $socialUser->getAvatar();
            }
            if (! $user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            if (! empty($updates)) {
                $user->update($updates);
            }
        } else {
            // 2. Create new User
            $isNewUser = true;
            $roleStr = (string) ($request->query('role') ?? session('oauth_role', 'student'));
            $userRole = UserRole::tryFrom($roleStr) ?? UserRole::Student;

            $name = trim((string) ($socialUser->getName() ?? $socialUser->getNickname() ?? 'Пользователь'));
            if ($name === '') {
                $name = 'Пользователь';
            }

            $user = new User([
                'name' => $name,
                'email' => $normalizedEmail,
                'offer_accepted_at' => now(),
                'email_verified_at' => now(),
            ]);

            $user->{$providerIdField} = $providerId;
            $user->avatar = $socialUser->getAvatar();
            $user->role = $userRole;
            $user->is_verified = true;
            $user->save();

            // Handle Tutor Setup
            if ($userRole === UserRole::Tutor) {
                TutorProfile::firstOrCreate([
                    'user_id' => $user->id,
                ]);

                $planStr = (string) ($request->query('plan') ?? session('oauth_plan', 'pro'));
                $plan = match ($planStr) {
                    'start', 'basic' => SubscriptionPlan::START,
                    'pro' => SubscriptionPlan::PRO,
                    'premium' => SubscriptionPlan::PREMIUM,
                    default => SubscriptionPlan::PRO,
                };

                try {
                    app(SubscriptionService::class)->ensureTrialStarted($user, $plan);
                } catch (Throwable $e) {
                    Log::error('Failed to start trial for OAuth tutor: '.$e->getMessage());
                }
            }
        }

        // 3. Login
        Auth::login($user, true);
        Filament::auth()->login($user, true);
        $request->session()->regenerate();
        $request->session()->put('password_hash_web', $user->getAuthPassword());

        // 4. Clean session
        session()->forget(['oauth_role', 'oauth_plan']);

        return redirect('/admin');
    }
}
