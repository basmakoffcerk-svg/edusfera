<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorBalance extends Model
{
    protected $fillable = [
        'user_id',
        'available_amount',
        'pending_amount',
        'total_earned',
        'total_withdrawn',
        'last_payout_at',
    ];

    protected function casts(): array
    {
        return [
            'available_amount' => 'decimal:2',
            'pending_amount' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
            'last_payout_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
