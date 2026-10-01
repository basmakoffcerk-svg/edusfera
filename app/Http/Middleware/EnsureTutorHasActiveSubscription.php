<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTutorHasActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Only enforce paywall for tutors
        if (! $user || ! $user->isTutor()) {
            return $next($request);
        }

        // 2. Allow subscription page, logout, and payment routes
        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        // 3. Verify active subscription and onboarding completion
        $subscription = $user->subscription;
        $isOperational = $subscription !== null && $subscription->isActive();
        $isOnboarded = $subscription !== null && (bool) $subscription->is_onboarded;

        if (! $isOperational || ! $isOnboarded) {
            if ($request->hasSession()) {
                session()->flash('subscription_required', true);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'subscription_required',
                    'message' => 'Для доступа к возможностям платформы требуется активная подписка.',
                    'redirect' => route('filament.admin.pages.tutor-subscription-page'),
                ], 403);
            }

            return redirect()->route('filament.admin.pages.tutor-subscription-page');
        }

        return $next($request);
    }

    protected function isAllowedRoute(Request $request): bool
    {
        // Check route name
        $routeName = (string) $request->route()?->getName();
        if (
            $routeName === 'filament.admin.pages.tutor-subscription-page'
            || $routeName === 'filament.admin.auth.logout'
            || str_starts_with($routeName, 'alfabank.')
            || str_starts_with($routeName, 'payments.')
        ) {
            return true;
        }

        // Check path
        $path = trim($request->path(), '/');
        if (
            $path === 'admin/tutor-subscription-page'
            || $path === 'admin/logout'
            || str_starts_with($path, 'payments/alfabank')
            || str_starts_with($path, 'api/payments')
            || str_starts_with($path, 'api/subscription')
        ) {
            return true;
        }

        // Check Livewire requests on tutor-subscription-page
        if ($request->hasHeader('X-Livewire')) {
            $fingerprint = $request->input('fingerprint');
            $componentName = is_array($fingerprint) ? ($fingerprint['name'] ?? '') : '';
            if (
                str_contains((string) $componentName, 'tutor-subscription-page')
                || str_contains((string) $componentName, 'TutorSubscriptionPage')
            ) {
                return true;
            }

            $referer = (string) $request->header('referer');
            if (str_contains($referer, 'admin/tutor-subscription-page')) {
                return true;
            }
        }

        return false;
    }
}
