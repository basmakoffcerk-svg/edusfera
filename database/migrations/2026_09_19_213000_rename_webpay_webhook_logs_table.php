<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webpay_webhook_logs') && ! Schema::hasTable('payment_webhook_logs')) {
            Schema::rename('webpay_webhook_logs', 'payment_webhook_logs');
        } elseif (! Schema::hasTable('payment_webhook_logs')) {
            Schema::create('payment_webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event')->nullable();
                $table->string('transaction_id')->nullable();
                $table->json('payload');
                $table->string('ip')->nullable();
                $table->integer('status_code')->default(200);
                $table->text('error_reason')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payment_webhook_logs') && ! Schema::hasTable('webpay_webhook_logs')) {
            Schema::rename('payment_webhook_logs', 'webpay_webhook_logs');
        }
    }
};
