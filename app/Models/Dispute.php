<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED_REFUNDED = 'resolved_refunded';
    public const STATUS_RESOLVED_PAYOUT = 'resolved_payout';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'lesson_id',
        'initiator_id',
        'reason',
        'status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
