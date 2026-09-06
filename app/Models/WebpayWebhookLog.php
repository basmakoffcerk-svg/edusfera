<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebpayWebhookLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'webpay_webhook_logs';

    protected $fillable = [
        'event',
        'transaction_id',
        'payload',
        'ip',
        'status_code',
        'error_reason',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status_code' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
