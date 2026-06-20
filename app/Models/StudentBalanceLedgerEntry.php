<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBalanceLedgerEntry extends Model
{
    public const TYPE_TOPUP = 'topup';

    public const TYPE_HOLD = 'hold';

    public const TYPE_RELEASE = 'release';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_REFUND = 'refund';

    protected $fillable = [
        'student_balance_id',
        'user_id',
        'lesson_id',
        'transaction_id',
        'type',
        'amount',
        'currency',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function balance(): BelongsTo
    {
        return $this->belongsTo(StudentBalance::class, 'student_balance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
