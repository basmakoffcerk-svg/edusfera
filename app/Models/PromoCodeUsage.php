<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCodeUsage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'promo_code_id',
        'user_id',
        'lesson_id',
        'transaction_id',
        'order_type',
        'discount_amount',
        'original_amount',
        'final_amount',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'float',
            'original_amount' => 'float',
            'final_amount' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
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
