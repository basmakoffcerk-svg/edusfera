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
        if ($this->status === SubscriptionStatus::TRIAL) {
            return $this->trial_ends_at === null || $this->trial_ends_at->isFuture();
        }

        if ($this->status === SubscriptionStatus::ACTIVE) {
            return $this->current_period_ends_at === null || $this->current_period_ends_at->isFuture();
        }

        if ($this->status === SubscriptionStatus::PAST_DUE) {
            return $this->grace_period_ends_at !== null && $this->grace_period_ends_at->isFuture();
        }

        return false;
    }

    public function daysRemaining(): int
    {
        $now = CarbonImmutable::now();
        $target = $this->status === SubscriptionStatus::TRIAL ? $this->trial_ends_at : $this->current_period_ends_at;

        if (! $target) {
            return 0;
        }

        return (int) max(0, $now->diffInDays($target, false));
    }
}
