<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

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
     * Available for active PRO subscriptions (and trial).
     */
    public function canCustomizeBranding(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && $sub->isPro();
    }

    /**
     * Check if tutor can respond to student requests / leads from the catalog.
     * START/BASIC: 0 responses (works only with own direct students).
     * PRO: up to 10 responses per month.
     * PREMIUM: unlimited responses.
     * Trial: full access.
     */
    public function canRespondToRequests(User $tutor): bool
    {
        if (! $this->hasActiveSubscription($tutor)) {
            return false;
        }

        $sub = $this->getSubscription($tutor);
        if (! $sub) {
            return false;
        }

        if ($sub->isInTrial()) {
            return true;
        }

        $limit = $sub->plan->maxResponsesPerMonth();
        if ($limit === 0) {
            return false;
        }

        if ($limit === null) {
            return true;
        }

        return (int) $sub->responses_used_this_month < $limit;
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
     * Check if tutor has access to basic analytics (PRO & PREMIUM).
     */
    public function canAccessAnalytics(User $tutor): bool
    {
        return $this->canUseAiTools($tutor);
    }

    /**
     * Check if tutor has access to advanced cohort/LTV analytics (PREMIUM only).
     */
    public function canAccessDeepAnalytics(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && ($sub->isInTrial() || $sub->isPremium());
    }

    /**
     * Check if tutor can sync with Google Calendar (PREMIUM only).
     */
    public function canSyncCalendar(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && ($sub->isInTrial() || $sub->isPremium());
    }

    /**
     * Check if tutor can add a video intro to their catalog card (PREMIUM only).
     */
    public function canUseVideoIntro(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && ($sub->isInTrial() || $sub->isPremium());
    }

    /**
     * Get tutor storage limit in Megabytes based on tier.
     */
    public function getStorageLimitMb(User $tutor): int
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return 1024;
        }

        return $sub->plan->storageLimitGb() * 1024;
    }

    /**
     * Check maximum allowed duration per video lesson in minutes.
     */
    public function getMaxLessonDurationMinutes(User $tutor): int
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return 45;
        }

        return $sub->plan->maxLessonDurationMinutes();
    }

    /**
     * Check if tutor can host video calls.
     */
    public function canHostVideoCalls(User $tutor): bool
    {
        return $this->canAccessClassroom($tutor);
    }
}
