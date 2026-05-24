<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    protected $fillable = [
        'lesson_id',
        'user_id',
        'amount',
        'platform_commission',
        'acquiring_fee',
        'net_amount',
        'currency',
        'status',
        'payment_method',
        'gateway_transaction_id',
        'gateway_response',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_commission' => 'decimal:2',
            'acquiring_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
