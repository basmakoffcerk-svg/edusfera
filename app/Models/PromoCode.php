<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'scope',
        'subscription_period',
        'subscription_plan',
        'package_codes',
        'tutor_ids',
        'subjects',
        'max_uses',
        'max_uses_per_user',
        'used_count',
        'first_order_only',
        'is_active',
        'starts_at',
        'expires_at',
        'created_by',
    ];

    protected $attributes = [
        'max_uses_per_user' => 1,
        'used_count' => 0,
        'is_active' => true,
        'first_order_only' => false,
        'discount_type' => 'percent',
        'discount_value' => 0.00,
        'scope' => 'all',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'float',
            'max_discount_amount' => 'float',
            'min_order_amount' => 'float',
            'package_codes' => 'array',
            'tutor_ids' => 'array',
            'subjects' => 'array',
            'max_uses' => 'integer',
            'max_uses_per_user' => 'integer',
            'used_count' => 'integer',
            'first_order_only' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isFreeSubscription(): bool
    {
        return in_array($this->discount_type, ['free_period', 'lifetime'], true)
            || ($this->discount_type === 'percent' && (float) $this->discount_value >= 100.0);
    }

    public function isLifetimeSubscription(): bool
    {
        return $this->discount_type === 'lifetime' || $this->subscription_period === 'lifetime';
    }

    public function getSubscriptionMonths(): int
    {
        return match ($this->subscription_period) {
            '3_months' => 3,
            '6_months' => 6,
            '12_months' => 12,
            'lifetime' => 1200,
            default => 1,
        };
    }

    protected static function booted(): void
    {
        static::saving(function (PromoCode $promoCode): void {
            $promoCode->code = strtoupper(trim((string) $promoCode->code));

            if ($promoCode->discount_value === null || in_array($promoCode->discount_type, ['free_period', 'lifetime'], true)) {
                $promoCode->discount_value = 0.00;
            }

            if ($promoCode->discount_type === 'lifetime') {
                $promoCode->subscription_period = 'lifetime';
            }
        });
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * Рассчитать сумму скидки в BYN от переданной суммы заказа.
     */
    public function calculateDiscount(float $amount): float
    {
        if ($amount <= 0.0) {
            return 0.0;
        }

        if ($this->isFreeSubscription()) {
            return $amount;
        }

        $rawVal = max(0.0, (float) $this->discount_value);

        if ($this->discount_type === 'percent') {
            $percent = min(100.0, $rawVal);
            $discount = round(($amount * ($percent / 100)), 2);
            if ($this->max_discount_amount !== null && (float) $this->max_discount_amount > 0) {
                $discount = min($discount, (float) $this->max_discount_amount);
            }

            return min($discount, $amount);
        }

        // Фиксированная сумма (fixed)
        if ($this->max_discount_amount !== null && (float) $this->max_discount_amount > 0) {
            $rawVal = min($rawVal, (float) $this->max_discount_amount);
        }

        return min($rawVal, $amount);
    }

    /**
     * Проверка валидности промокода под конкретный заказ и пользователя.
     *
     * @return array{valid: bool, error: ?string, discount_amount: float, final_amount: float}
     */
    public function validateFor(float $amount, ?User $user = null, array $context = []): array
    {
        if (! $this->is_active) {
            return [
                'valid' => false,
                'error' => 'Промокод отключен или недействителен.',
                'discount_amount' => 0.0,
                'final_amount' => $amount,
            ];
        }

        // Если промокод запланирован на будущую дату
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return [
                'valid' => false,
                'error' => 'Срок действия промокода ещё не начался.',
                'discount_amount' => 0.0,
                'final_amount' => $amount,
            ];
        }

        if ($this->expires_at) {
            // Если указано время 00:00:00 (дата без точного времени), промокод действует до конца суток
            $isDateOnly = $this->expires_at->format('H:i:s') === '00:00:00';
            $expiryThreshold = $isDateOnly ? $this->expires_at->endOfDay() : $this->expires_at;

            if (now()->gt($expiryThreshold)) {
                return [
                    'valid' => false,
                    'error' => 'Срок действия промокода истёк.',
                    'discount_amount' => 0.0,
                    'final_amount' => $amount,
                ];
            }
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return [
                'valid' => false,
                'error' => 'Лимит активаций этого промокода исчерпан.',
                'discount_amount' => 0.0,
                'final_amount' => $amount,
            ];
        }

        if ($this->min_order_amount !== null && $amount < $this->min_order_amount) {
            return [
                'valid' => false,
                'error' => sprintf('Минимальная сумма для этого промокода — %.2f BYN.', $this->min_order_amount),
                'discount_amount' => 0.0,
                'final_amount' => $amount,
            ];
        }

        // Проверка области применения
        $orderType = $context['order_type'] ?? 'lesson';

        if ($this->isFreeSubscription() && $orderType !== 'subscription') {
            return [
                'valid' => false,
                'error' => 'Этот промокод предназначен только для подписок репетиторов.',
                'discount_amount' => 0.0,
                'final_amount' => $amount,
            ];
        }

        if (! empty($this->subscription_plan) && $this->subscription_plan !== 'any' && isset($context['plan'])) {
            $chosenPlan = strtolower((string) $context['plan']);
            $allowedPlan = strtolower((string) $this->subscription_plan);
            if ($chosenPlan !== $allowedPlan) {
                return [
                    'valid' => false,
                    'error' => "Этот промокод действует только для тарифа «{$this->subscription_plan}».",
                    'discount_amount' => 0.0,
                    'final_amount' => $amount,
                ];
            }
        }

        if ($this->scope === 'lessons' && $orderType !== 'lesson') {
            return [
                'valid' => false,
                'error' => 'Промокод применим только для оплаты занятий.',
                'discount_amount' => 0.0,
                'final_amount' => $amount,
            ];
        }

        if ($this->scope === 'subscriptions' && $orderType !== 'subscription') {
            return [
                'valid' => false,
                'error' => 'Промокод применим только для оплаты тарифов репетитора.',
                'discount_amount' => 0.0,
                'final_amount' => $amount,
            ];
        }

        // Ограничение по пакетам
        if (! empty($this->package_codes) && isset($context['package_code'])) {
            if (! in_array($context['package_code'], (array) $this->package_codes, true)) {
                return [
                    'valid' => false,
                    'error' => 'Промокод не действует для выбранного пакета занятий.',
                    'discount_amount' => 0.0,
                    'final_amount' => $amount,
                ];
            }
        }

        // Ограничение по репетиторам
        if (! empty($this->tutor_ids) && isset($context['tutor_id'])) {
            $allowedTutors = array_map('intval', (array) $this->tutor_ids);
            if (! in_array((int) $context['tutor_id'], $allowedTutors, true)) {
                return [
                    'valid' => false,
                    'error' => 'Промокод не действует для выбранного репетитора.',
                    'discount_amount' => 0.0,
                    'final_amount' => $amount,
                ];
            }
        }

        // Ограничение по предметам
        if (! empty($this->subjects) && isset($context['subject'])) {
            $allowedSubjects = array_map('mb_strtolower', (array) $this->subjects);
            if (! in_array(mb_strtolower((string) $context['subject']), $allowedSubjects, true)) {
                return [
                    'valid' => false,
                    'error' => 'Промокод не действует для этого предмета.',
                    'discount_amount' => 0.0,
                    'final_amount' => $amount,
                ];
            }
        }

        // Ограничения для пользователя
        if ($user) {
            $maxPerUser = $this->max_uses_per_user !== null ? (int) $this->max_uses_per_user : 1;
            if ($maxPerUser > 0) {
                $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
                if ($userUsageCount >= $maxPerUser) {
                    return [
                        'valid' => false,
                        'error' => 'Вы уже использовали этот промокод максимально допустимое количество раз.',
                        'discount_amount' => 0.0,
                        'final_amount' => $amount,
                    ];
                }
            }

            if ($this->first_order_only) {
                $hasPaidTransactions = Transaction::query()
                    ->where('user_id', $user->id)
                    ->whereIn('status', [Transaction::STATUS_SUCCESS, 'success', 'settled'])
                    ->exists();

                $hasPaidInvoices = SubscriptionInvoice::query()
                    ->where('tutor_id', $user->id)
                    ->where('status', InvoiceStatus::PAID)
                    ->exists();

                if ($hasPaidTransactions || $hasPaidInvoices) {
                    return [
                        'valid' => false,
                        'error' => 'Промокод действует только на первый заказ нового пользователя.',
                        'discount_amount' => 0.0,
                        'final_amount' => $amount,
                    ];
                }
            }
        }

        $discount = $this->calculateDiscount($amount);
        $finalAmount = max(0.0, round($amount - $discount, 2));

        return [
            'valid' => true,
            'error' => null,
            'discount_amount' => $discount,
            'final_amount' => $finalAmount,
        ];
    }
}
