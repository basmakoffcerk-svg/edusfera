<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->unique();
            $table->foreignId('user_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('platform_commission', 10, 2);
            $table->decimal('acquiring_fee', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->char('currency', 3)->default('BYN');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('lesson_id', 'transactions_lesson_id_foreign')->references('id')->on('lessons')->cascadeOnDelete();
            $table->foreign('user_id', 'transactions_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'status', 'paid_at']);
            $table->index(['lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
