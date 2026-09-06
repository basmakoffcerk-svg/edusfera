<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    public const FOUNDER_LIMIT = 50;
    public const TRIAL_DAYS = 30;
    public const GRACE_PERIOD_DAYS = 3;

    /**
     * Start a 30-day free trial for a tutor.
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
     * 1. Send T-3 invoices for expiring trials/active subscriptions.
     * 2. Put overdue into grace period.
     * 3. Deactivate expired subscriptions.
     */
    public function processDailyBillingCycle(): array
    {
        $now = CarbonImmutable::now();
        $results = [
            'invoices_created' => 0,
            'moved_to_grace' => 0,
            'expired' => 0,
        ];

        // 1. T-3 Invoices (expiring in <= 3 days and no pending invoice exists)
        $expiringSubs = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::TRIAL, SubscriptionStatus::ACTIVE])
            ->where('current_period_ends_at', '<=', $now->addDays(3))
            ->where('current_period_ends_at', '>', $now)
            ->get();

        foreach ($expiringSubs as $sub) {
            $hasPendingInvoice = SubscriptionInvoice::where('subscription_id', $sub->id)
                ->where('status', InvoiceStatus::PENDING)
                ->exists();

            if (! $hasPendingInvoice) {
                $this->createInvoice($sub, $sub->plan, 1);
                $results['invoices_created']++;
            }
        }

        // 2. Overdue -> Grace period
        $overdueSubs = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::TRIAL, SubscriptionStatus::ACTIVE])
            ->where('current_period_ends_at', '<=', $now)
            ->get();

        foreach ($overdueSubs as $sub) {
            $sub->update([
                'status' => SubscriptionStatus::PAST_DUE,
                'grace_period_ends_at' => $now->addDays(self::GRACE_PERIOD_DAYS),
            ]);
            $results['moved_to_grace']++;
        }

        // 3. Grace period ended without payment -> EXPIRED
        $expiredSubs = Subscription::query()
            ->where('status', SubscriptionStatus::PAST_DUE)
            ->where('grace_period_ends_at', '<=', $now)
            ->get();

        foreach ($expiredSubs as $sub) {
            $sub->update([
                'status' => SubscriptionStatus::EXPIRED,
            ]);
            $results['expired']++;
        }

        return $results;
    }
}
