<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_id',
        'plan',
        'status',
        'is_founder',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'grace_period_ends_at',
        'canceled_at',
        'responses_used_this_month',
        'payment_token',
    ];

    protected function casts(): array
    {
        return [
            'plan' => SubscriptionPlan::class,
            'status' => SubscriptionStatus::class,
            'is_founder' => 'boolean',
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'responses_used_this_month' => 'integer',
        ];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        if ($this->isInTrial()) {
            return true;
        }

        if ($this->status === SubscriptionStatus::ACTIVE) {
            return $this->current_period_ends_at === null || $this->current_period_ends_at->isFuture();
        }

        if ($this->isInGracePeriod()) {
            return true;
        }

        return false;
    }

    public function isInTrial(): bool
    {
        return $this->status === SubscriptionStatus::TRIAL &&
            ($this->trial_ends_at === null || $this->trial_ends_at->isFuture());
    }

    public function isInGracePeriod(): bool
    {
        return $this->status === SubscriptionStatus::PAST_DUE &&
            $this->grace_period_ends_at !== null &&
            $this->grace_period_ends_at->isFuture();
    }

    public function daysRemaining(): int
    {
        $now = CarbonImmutable::now();
        $target = match ($this->status) {
            SubscriptionStatus::TRIAL => $this->trial_ends_at,
            SubscriptionStatus::ACTIVE => $this->current_period_ends_at,
            SubscriptionStatus::PAST_DUE => $this->grace_period_ends_at,
            default => null,
        };

        if (! $target) {
            return 0;
        }

        return (int) max(0, $now->diffInDays($target, false));
    }

    public function isPro(): bool
    {
        return $this->plan === SubscriptionPlan::PRO;
    }

    public function isOperational(): bool
    {
        return $this->isActive();
    }

    public function graceDaysRemaining(): int
    {
        if (! $this->grace_period_ends_at) {
            return 0;
        }

        $now = CarbonImmutable::now();
        return (int) max(0, ceil($now->diffInHours($this->grace_period_ends_at, false) / 24));
    }

    public function isExpiringSoon(): bool
    {
        return $this->isActive() && $this->daysRemaining() <= 3 && $this->daysRemaining() > 0;
    }
}

