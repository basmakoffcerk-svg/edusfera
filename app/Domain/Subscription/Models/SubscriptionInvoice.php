<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'tutor_id',
        'invoice_number',
        'plan',
        'period_months',
        'amount_kopecks',
        'erip_account_number',
        'status',
        'due_date',
        'paid_at',
        'payment_method',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'plan' => SubscriptionPlan::class,
            'status' => InvoiceStatus::class,
            'period_months' => 'integer',
            'amount_kopecks' => 'integer',
            'due_date' => 'datetime',
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function amountByn(): float
    {
        return $this->amount_kopecks / 100;
    }
}
