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

    /**
     * Check if tutor can respond to student requests.
     */
    public function canRespondToRequests(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        $limit = $sub->plan->maxResponsesPerMonth();
        if ($limit === null) {
            return true; // unlimited (Premium)
        }

        if ($limit === 0) {
            return false; // Basic cannot respond directly
        }

        return $sub->responses_used_this_month < $limit;
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
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && $sub->plan->allowsAnalytics();
    }

    /**
     * Check if tutor can sync with Google Calendar.
     */
    public function canSyncCalendar(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && $sub->plan->allowsCalendarSync();
    }

    /**
     * Check if tutor can host video calls up to 45 mins.
     */
    public function canHostVideoCalls(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && $sub->plan->allowsVideoCalls();
    }
}
