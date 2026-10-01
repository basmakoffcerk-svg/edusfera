<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Models\Lesson;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PromoCodeService
{
    /**
     * Поиск промокода по строке (case-insensitive, trimmed, zero-width stripped).
     */
    public function findByCode(?string $code): ?PromoCode
    {
        if (! $code) {
            return null;
        }

        // Strip invisible zero-width characters, control chars, and all Unicode whitespace
        $clean = preg_replace('/[\p{Z}\p{C}]+/u', '', (string) $code);
        $normalized = mb_strtoupper(mb_substr($clean, 0, 64));

        if ($normalized === '') {
            return null;
        }

        return PromoCode::query()
            ->where('code', $normalized)
            ->first();
    }

    /**
     * Проверка промокода на применимость к сумме и контексту (урок, репетитор, предмет, пользователь).
     *
     * @return array{valid: bool, error: ?string, promo_code: ?PromoCode, discount_amount: float, final_amount: float}
     */
    public function validate(string $code, float $amount, ?User $user = null, array $context = []): array
    {
        $promo = $this->findByCode($code);

        if (! $promo) {
            return [
                'valid' => false,
                'error' => 'Промокод не найден.',
                'promo_code' => null,
                'discount_amount' => 0.0,
                'final_amount' => $amount,
            ];
        }

        $result = $promo->validateFor($amount, $user, $context);

        return array_merge($result, [
            'promo_code' => $promo,
        ]);
    }

    /**
     * Зафиксировать использование промокода для урока с защитой от состояния гонки.
     */
    public function applyToLesson(
        PromoCode $promoCode,
        Lesson $lesson,
        User $user,
        Transaction $transaction,
        float $discountAmount,
        float $originalAmount
    ): PromoCodeUsage {
        return DB::transaction(function () use ($promoCode, $lesson, $user, $transaction, $discountAmount, $originalAmount) {
            /** @var PromoCode|null $locked */
            $locked = PromoCode::where('id', $promoCode->id)->lockForUpdate()->first();
            if ($locked) {
                if ($locked->max_uses !== null && $locked->used_count >= $locked->max_uses) {
                    throw new \RuntimeException('Лимит активаций этого промокода исчерпан.');
                }
                $locked->increment('used_count');
            }

            return PromoCodeUsage::create([
                'promo_code_id' => $promoCode->id,
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'transaction_id' => $transaction->id,
                'order_type' => 'lesson',
                'discount_amount' => $discountAmount,
                'original_amount' => $originalAmount,
                'final_amount' => max(0.0, round($originalAmount - $discountAmount, 2)),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Зафиксировать использование промокода для подписки репетитора с защитой от состояния гонки.
     */
    public function applyToSubscription(
        PromoCode $promoCode,
        User $user,
        SubscriptionInvoice $invoice,
        float $discountAmount,
        float $originalAmount
    ): PromoCodeUsage {
        return DB::transaction(function () use ($promoCode, $user, $discountAmount, $originalAmount) {
            /** @var PromoCode|null $locked */
            $locked = PromoCode::where('id', $promoCode->id)->lockForUpdate()->first();
            if ($locked) {
                if ($locked->max_uses !== null && $locked->used_count >= $locked->max_uses) {
                    throw new \RuntimeException('Лимит активаций этого промокода исчерпан.');
                }
                $locked->increment('used_count');
            }

            return PromoCodeUsage::create([
                'promo_code_id' => $promoCode->id,
                'user_id' => $user->id,
                'lesson_id' => null,
                'transaction_id' => null,
                'order_type' => 'subscription',
                'discount_amount' => $discountAmount,
                'original_amount' => $originalAmount,
                'final_amount' => max(0.0, round($originalAmount - $discountAmount, 2)),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Активировать бесплатную подписку репетитору по промокоду (1/3/6/12 месяцев или пожизненно).
     *
     * @return array{subscription: Subscription, invoice: SubscriptionInvoice, is_lifetime: bool, months: int, plan: SubscriptionPlan}
     */
    public function activateFreeSubscriptionViaPromo(
        PromoCode $promoCode,
        User $tutor,
        ?string $requestedPlan = null
    ): array {
        return DB::transaction(function () use ($promoCode, $tutor, $requestedPlan) {
            if (! $tutor->isTutor() && ! $tutor->isAdmin()) {
                throw new \InvalidArgumentException('Бесплатная подписка по промокоду доступна только для преподавателей.');
            }

            /** @var PromoCode|null $lockedPromo */
            $lockedPromo = PromoCode::where('id', $promoCode->id)->lockForUpdate()->first();
            if (! $lockedPromo || ! $lockedPromo->is_active) {
                throw new \InvalidArgumentException('Промокод отключен или не существует.');
            }

            $planValue = $lockedPromo->subscription_plan && $lockedPromo->subscription_plan !== 'any'
                ? $lockedPromo->subscription_plan
                : ($requestedPlan ?? 'pro');

            $plan = SubscriptionPlan::tryFrom($planValue)
                ?? SubscriptionPlan::PRO;

            // Strict re-validation under lock
            $validation = $lockedPromo->validateFor(0.0, $tutor, [
                'order_type' => 'subscription',
                'plan' => $plan->value,
            ]);

            if (! $validation['valid']) {
                throw new \InvalidArgumentException($validation['error'] ?? 'Промокод не может быть применён.');
            }

            $now = CarbonImmutable::now();
            $subscription = Subscription::firstOrCreate(
                ['tutor_id' => $tutor->id],
                [
                    'plan' => $plan,
                    'status' => SubscriptionStatus::ACTIVE,
                    'is_founder' => false,
                    'trial_ends_at' => null,
                    'current_period_starts_at' => $now,
                    'current_period_ends_at' => $now->addMonths(1),
                    'grace_period_ends_at' => null,
                    'is_onboarded' => true,
                    'responses_used_this_month' => 0,
                ]
            );

            $isLifetime = $promoCode->isLifetimeSubscription();
            $months = $promoCode->getSubscriptionMonths();

            if ($isLifetime) {
                // MySQL TIMESTAMP safe limit: 2038-01-19. Set to end of 2037 to avoid integer overflow
                $newEnd = CarbonImmutable::create(2037, 12, 31, 23, 59, 59);
            } else {
                $currentEnd = ($subscription->current_period_ends_at && $subscription->current_period_ends_at->isFuture())
                    ? CarbonImmutable::parse($subscription->current_period_ends_at)
                    : $now;
                $newEnd = $currentEnd->addMonths($months);
            }

            $subUpdates = [
                'plan' => $plan,
                'status' => SubscriptionStatus::ACTIVE,
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $newEnd,
                'grace_period_ends_at' => null,
                'canceled_at' => null,
            ];

            if (Schema::hasColumn('subscriptions', 'is_onboarded')) {
                $subUpdates['is_onboarded'] = true;
            }

            $subscription->update($subUpdates);

            $originalPriceByn = $isLifetime ? ($plan->yearlyPriceByn() * 5) : ($plan->monthlyPriceByn() * $months);

            $invoiceData = [
                'subscription_id' => $subscription->id,
                'tutor_id' => $tutor->id,
                'invoice_number' => 'INV-FREE-'.strtoupper(Str::random(8)),
                'plan' => $plan,
                'period_months' => $isLifetime ? 120 : $months,
                'amount_kopecks' => 0,
                'status' => InvoiceStatus::PAID,
                'due_date' => $now,
                'paid_at' => $now,
                'payment_method' => 'promo',
                'payload' => [
                    'promo_code' => $promoCode->code,
                    'is_lifetime' => $isLifetime,
                    'period_months' => $months,
                ],
            ];

            if (Schema::hasColumn('subscription_invoices', 'promo_code_id')) {
                $invoiceData['promo_code_id'] = $promoCode->id;
            }
            if (Schema::hasColumn('subscription_invoices', 'discount_kopecks')) {
                $invoiceData['discount_kopecks'] = (int) round($originalPriceByn * 100);
            }

            $invoice = SubscriptionInvoice::create($invoiceData);

            $this->applyToSubscription(
                $promoCode,
                $tutor,
                $invoice,
                $originalPriceByn,
                $originalPriceByn
            );

            return [
                'subscription' => $subscription->fresh(),
                'invoice' => $invoice,
                'is_lifetime' => $isLifetime,
                'months' => $months,
                'plan' => $plan,
            ];
        });
    }

    /**
     * Генерация уникального случайного промокода.
     */
    public function generateUniqueCode(string $prefix = 'EDU', int $length = 6): string
    {
        do {
            $random = strtoupper(Str::random($length));
            $code = $prefix ? "{$prefix}-{$random}" : $random;
        } while (PromoCode::where('code', $code)->exists());

        return $code;
    }
}
