<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LessonSettlementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonSettlement extends Model
{
    /** @use HasFactory<LessonSettlementFactory> */
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'transaction_id',
        'net_share',
        'gross_share',
        'settled_at',
        'refunded_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'net_share' => 'decimal:2',
            'gross_share' => 'decimal:2',
            'settled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }
}
