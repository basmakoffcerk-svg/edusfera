<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Models\User;
use App\Notifications\SubscriptionGracePeriodNotification;
use App\Services\Payment\AlfaBankPaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    public const FOUNDER_LIMIT = 50;
    public const TRIAL_DAYS = 14;
    public const GRACE_PERIOD_DAYS = 3;

    /**
     * Get subscription for a tutor.
     */
    public function getSubscription(User $tutor): ?Subscription
    {
        return Subscription::where('tutor_id', $tutor->id)->first();
    }

    /**
     * Ensure a 14-day PRO trial is started for tutor if they don't have one yet.
     */
    public function ensureTrialStarted(User $tutor): Subscription
    {
        return DB::transaction(function () use ($tutor) {
            $existing = Subscription::where('tutor_id', $tutor->id)->first();
            if ($existing) {
                return $existing;
            }

            $founderCount = Subscription::where('is_founder', true)->count();
            $isFounder = $founderCount < self::FOUNDER_LIMIT;

            $now = CarbonImmutable::now();

            return Subscription::create([
                'tutor_id' => $tutor->id,
                'plan' => SubscriptionPlan::PRO,
                'status' => SubscriptionStatus::TRIAL,
                'is_founder' => $isFounder,
                'trial_ends_at' => $now->addDays(self::TRIAL_DAYS),
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $now->addDays(self::TRIAL_DAYS),
                'grace_period_ends_at' => null,
                'responses_used_this_month' => 0,
            ]);
        });
    }

    /**
     * Start a free trial for a tutor with a specific plan (default PRO, 14 days).
     */
    public function startTrial(User $tutor, SubscriptionPlan $plan = SubscriptionPlan::PRO): Subscription
    {
        return DB::transaction(function () use ($tutor, $plan) {
            $existing = Subscription::where('tutor_id', $tutor->id)->first();
            if ($existing) {
                return $existing;
            }

            $founderCount = Subscription::where('is_founder', true)->count();
            $isFounder = $founderCount < self::FOUNDER_LIMIT;

            $now = CarbonImmutable::now();

            return Subscription::create([
                'tutor_id' => $tutor->id,
                'plan' => $plan,
                'status' => SubscriptionStatus::TRIAL,
                'is_founder' => $isFounder,
                'trial_ends_at' => $now->addDays(self::TRIAL_DAYS),
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $now->addDays(self::TRIAL_DAYS),
                'grace_period_ends_at' => null,
                'responses_used_this_month' => 0,
            ]);
        });
    }

    /**
     * Subscribe tutor to a plan: creates invoice, sets period, activates subscription, saves payment token.
     */
    public function subscribe(
        User $tutor,
        SubscriptionPlan $plan,
        int $periodMonths = 1,
        ?string $paymentToken = null
    ): Subscription {
        return DB::transaction(function () use ($tutor, $plan, $periodMonths, $paymentToken) {
            $now = CarbonImmutable::now();
            $subscription = Subscription::where('tutor_id', $tutor->id)->first();

            if (! $subscription) {
                $subscription = Subscription::create([
                    'tutor_id' => $tutor->id,
                    'plan' => $plan,
                    'status' => SubscriptionStatus::ACTIVE,
                    'is_founder' => false,
                    'trial_ends_at' => null,
                    'current_period_starts_at' => $now,
                    'current_period_ends_at' => $now->addMonths($periodMonths),
                    'grace_period_ends_at' => null,
                    'canceled_at' => null,
                    'responses_used_this_month' => 0,
                    'payment_token' => $paymentToken,
                ]);
            } else {
                $currentEnd = ($subscription->current_period_ends_at && $subscription->current_period_ends_at->isFuture())
                    ? CarbonImmutable::parse($subscription->current_period_ends_at)
                    : $now;
                $newEnd = $currentEnd->addMonths($periodMonths);

                $subscription->update([
                    'plan' => $plan,
                    'status' => SubscriptionStatus::ACTIVE,
                    'current_period_starts_at' => $now,
                    'current_period_ends_at' => $newEnd,
                    'grace_period_ends_at' => null,
                    'canceled_at' => null,
                    'payment_token' => $paymentToken ?? $subscription->payment_token,
                ]);
            }

            $invoice = $this->createInvoice($subscription, $plan, $periodMonths);
            $invoice->update([
                'status' => InvoiceStatus::PAID,
                'paid_at' => $now,
                'payment_method' => $paymentToken ? 'card' : 'erip',
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Cancel renewal at end of current period.
     */
    public function cancel(User $tutor): Subscription
    {
        $subscription = Subscription::where('tutor_id', $tutor->id)->firstOrFail();
        $subscription->update([
            'canceled_at' => CarbonImmutable::now(),
        ]);

        return $subscription->fresh();
    }

    /**
     * Handle payment failure: move to PAST_DUE with 3 days grace period.
     */
    public function handlePaymentFailure(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::PAST_DUE,
            'grace_period_ends_at' => CarbonImmutable::now()->addDays(self::GRACE_PERIOD_DAYS),
        ]);

        return $subscription->fresh();
    }

    /**
     * Expire grace period: transition status to EXPIRED.
     */
    public function expireGracePeriod(Subscription $subscription): Subscription
    {
        $now = CarbonImmutable::now();
        if ($subscription->grace_period_ends_at === null || $subscription->grace_period_ends_at->lte($now)) {
            $subscription->update([
                'status' => SubscriptionStatus::EXPIRED,
            ]);
        }

        return $subscription->fresh();
    }

    /**
     * Generate an invoice for upcoming subscription period.
     */
    public function createInvoice(
        Subscription $subscription,
        ?SubscriptionPlan $plan = null,
        int $periodMonths = 1
    ): SubscriptionInvoice {
        $plan = $plan ?? $subscription->plan;
        $now = CarbonImmutable::now();

        $amountKopecks = $periodMonths === 12
            ? $plan->yearlyPriceKopecks()
            : ($plan->monthlyPriceKopecks() * $periodMonths);

        do {
            $invoiceNumber = sprintf(
                'INV-EDU-%s-%s',
                $now->format('Ymd'),
                strtoupper(Str::random(6))
            );
        } while (SubscriptionInvoice::where('invoice_number', $invoiceNumber)->exists());

        $eripAccount = sprintf('EDU%05d', $subscription->tutor_id);

        return SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'tutor_id' => $subscription->tutor_id,
            'invoice_number' => $invoiceNumber,
            'plan' => $plan,
            'period_months' => $periodMonths,
            'amount_kopecks' => $amountKopecks,
            'erip_account_number' => $eripAccount,
            'status' => InvoiceStatus::PENDING,
            'due_date' => $now->addDays(3),
            'payment_method' => 'erip',
            'payload' => [
                'tutor_name' => $subscription->tutor->name ?? '',
                'generated_at' => $now->toIso8601String(),
            ],
        ]);
    }

    /**
     * Record payment for an invoice and extend subscription.
     */
    public function recordPayment(
        SubscriptionInvoice|string $invoice,
        string $paymentMethod = 'erip',
        ?array $payload = null
    ): SubscriptionInvoice {
        return DB::transaction(function () use ($invoice, $paymentMethod, $payload) {
            $inv = is_string($invoice)
                ? SubscriptionInvoice::where('invoice_number', $invoice)->firstOrFail()
                : $invoice;

            if ($inv->status === InvoiceStatus::PAID) {
                return $inv;
            }

            $now = CarbonImmutable::now();
            $inv->update([
                'status' => InvoiceStatus::PAID,
                'paid_at' => $now,
                'payment_method' => $paymentMethod,
                'payload' => array_merge($inv->payload ?? [], $payload ?? []),
            ]);

            $sub = $inv->subscription;
            $currentEnd = $sub->current_period_ends_at && $sub->current_period_ends_at->isFuture()
                ? CarbonImmutable::parse($sub->current_period_ends_at)
                : $now;

            $newEnd = $currentEnd->addMonths($inv->period_months);

            $sub->update([
                'plan' => $inv->plan,
                'status' => SubscriptionStatus::ACTIVE,
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $newEnd,
                'grace_period_ends_at' => null,
                'responses_used_this_month' => 0,
            ]);

            return $inv;
        });
    }

    /**
     * Daily billing cycle maintenance:
     * 1. Auto-charge or move expiring subscriptions to grace period.
     * 2. Deactivate subscriptions whose grace period has expired.
     */
    public function processDailyBillingCycle(?AlfaBankPaymentGateway $gateway = null): array
    {
        $gateway = $gateway ?? app(AlfaBankPaymentGateway::class);
        $now = CarbonImmutable::now();
        $results = [
            'charged' => 0,
            'invoices_created' => 0,
            'moved_to_grace' => 0,
            'expired' => 0,
        ];

        // 1. Expiring / expired subscriptions
        $expiringSubs = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::TRIAL, SubscriptionStatus::ACTIVE])
            ->where('current_period_ends_at', '<=', $now)
            ->get();

        foreach ($expiringSubs as $sub) {
            if ($sub->canceled_at !== null) {
                $sub->update(['status' => SubscriptionStatus::CANCELED]);
                continue;
            }

            if (! empty($sub->payment_token)) {
                $chargeResult = $gateway->chargeRecurring(
                    $sub->payment_token,
                    $sub->plan->monthlyPriceKopecks(),
                    [
                        'subscription_id' => $sub->id,
                        'tutor_id' => $sub->tutor_id,
                    ]
                );

                if (! empty($chargeResult['success'])) {
                    $this->subscribe($sub->tutor, $sub->plan, 1, $sub->payment_token);
                    $results['charged']++;
                    $results['invoices_created']++;
                    continue;
                }
            }

            $this->handlePaymentFailure($sub);
            $sub->tutor?->notify(new SubscriptionGracePeriodNotification($sub));
            $results['moved_to_grace']++;
        }

        // 2. Grace period ended without payment -> EXPIRED
        $expiredSubs = Subscription::query()
            ->where('status', SubscriptionStatus::PAST_DUE)
            ->where('grace_period_ends_at', '<=', $now)
            ->get();

        foreach ($expiredSubs as $sub) {
            $this->expireGracePeriod($sub);
            $results['expired']++;
        }

        return $results;
    }
}
