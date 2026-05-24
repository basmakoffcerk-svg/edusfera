<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentBalance extends Model
{
    protected $fillable = [
        'user_id',
        'available_amount',
        'locked_amount',
        'total_topped_up',
        'total_spent',
        'total_refunded',
    ];

    protected function casts(): array
    {
        return [
            'available_amount' => 'decimal:2',
            'locked_amount' => 'decimal:2',
            'total_topped_up' => 'decimal:2',
            'total_spent' => 'decimal:2',
            'total_refunded' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(StudentBalanceLedgerEntry::class);
    }
}
