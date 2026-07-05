<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'model',
        'prompt',
        'response',
        'tokens_used',
        'cost',
        'is_error',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_error' => 'boolean',
            'cost' => 'decimal:4',
            'tokens_used' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
