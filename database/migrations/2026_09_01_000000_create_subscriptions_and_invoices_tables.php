<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('plan')->default('pro'); // basic, pro, premium
            $table->string('status')->default('trial'); // trial, active, past_due, canceled, expired
            $table->boolean('is_founder')->default(false);
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('current_period_starts_at')->nullable();
            $table->dateTime('current_period_ends_at')->nullable();
            $table->dateTime('grace_period_ends_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->unsignedInteger('responses_used_this_month')->default(0);
            $table->timestamps();

            $table->index(['status', 'current_period_ends_at']);
            $table->index(['status', 'trial_ends_at']);
        });

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('plan'); // basic, pro, premium
            $table->unsignedSmallInteger('period_months')->default(1); // 1 or 12
            $table->unsignedInteger('amount_kopecks');
            $table->string('erip_account_number')->nullable()->index();
            $table->string('status')->default('pending'); // pending, paid, expired, canceled
            $table->dateTime('due_date');
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // erip, bank_transfer, card
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('subscriptions');
    }
};
