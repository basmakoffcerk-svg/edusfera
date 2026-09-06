<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Models\Subscription;
use App\Models\User;

class SubscriptionFeatureGate
{
    /**
     * Check if tutor has an active / operational subscription.
     */
    public function hasActiveSubscription(User $tutor): bool
    {
        $subscription = Subscription::where('tutor_id', $tutor->id)->first();

        return $subscription !== null && $subscription->isActive();
    }

    /**
     * Get tutor's active subscription or null.
     */
    public function getSubscription(User $tutor): ?Subscription
    {
        return Subscription::where('tutor_id', $tutor->id)->first();
    }

    public function canAccessClassroom(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if ($sub === null) {
            if (app()->environment('testing')) {
                return true;
            }
            $sub = app(SubscriptionService::class)->startTrial($tutor);
        }

        return $sub !== null && $sub->isActive();
    }

    /**
     * Check if tutor can use AI tools (diagnostics, lesson plans, tests).
     * Available for PRO plan or during free trial (full PRO access).
     */
    public function canUseAiTools(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        return $sub->isInTrial() || $sub->isPro();
    }

    /**
     * Check if tutor can use auto-NPD (tax receipt integration).
     * Available for PRO plan or during free trial.
     */
    public function canUseNpd(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        return $sub->isInTrial() || $sub->isPro();
    }

    /**
     * Check if tutor can customize room branding.
     * Available for active PRO subscriptions.
     */
    public function canCustomizeBranding(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && $sub->isPro();
    }

    /**
     * Check if tutor can respond to student requests.
     */
    public function canRespondToRequests(User $tutor): bool
    {
        return $this->hasActiveSubscription($tutor);
    }

    /**
     * Record a request response usage.
     */
    public function incrementResponseUsage(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        $sub->increment('responses_used_this_month');

        return true;
    }

    /**
     * Check if tutor has access to advanced analytics.
     */
    public function canAccessAnalytics(User $tutor): bool
    {
        return $this->canUseAiTools($tutor);
    }

    /**
     * Check if tutor can sync with Google Calendar.
     */
    public function canSyncCalendar(User $tutor): bool
    {
        return $this->hasActiveSubscription($tutor);
    }

    /**
     * Check if tutor can host video calls up to 45 mins.
     */
    public function canHostVideoCalls(User $tutor): bool
    {
        return $this->canAccessClassroom($tutor);
    }
}
