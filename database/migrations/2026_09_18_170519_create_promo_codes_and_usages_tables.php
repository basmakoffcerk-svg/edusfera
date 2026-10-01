<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('description')->nullable();
            $table->string('discount_type', 32)->default('percent');
            $table->decimal('discount_value', 10, 2)->default(0.00);
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->enum('scope', ['all', 'lessons', 'subscriptions'])->default('all');
            $table->string('subscription_period', 32)->nullable(); // 1_month, 3_months, 6_months, 12_months, lifetime
            $table->string('subscription_plan', 32)->nullable(); // pro, premium, basic, any
            $table->json('package_codes')->nullable(); // ['single', 'pack_4', 'pack_8']
            $table->json('tutor_ids')->nullable();
            $table->json('subjects')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_user')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('first_order_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'is_active']);
            $table->index(['starts_at', 'expires_at']);
        });

        Schema::create('promo_code_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('order_type', 32)->default('lesson'); // lesson, subscription
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('original_amount', 10, 2);
            $table->decimal('final_amount', 10, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['promo_code_id', 'user_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('amount')->constrained('promo_codes')->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->default(0.00)->after('promo_code_id');
        });

        if (Schema::hasTable('subscription_invoices')) {
            Schema::table('subscription_invoices', function (Blueprint $table) {
                $table->foreignId('promo_code_id')->nullable()->after('amount_kopecks')->constrained('promo_codes')->nullOnDelete();
                $table->integer('discount_kopecks')->default(0)->after('promo_code_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscription_invoices')) {
            Schema::table('subscription_invoices', function (Blueprint $table) {
                $table->dropForeign(['promo_code_id']);
                $table->dropColumn(['promo_code_id', 'discount_kopecks']);
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'discount_amount']);
        });

        Schema::dropIfExists('promo_code_usages');
        Schema::dropIfExists('promo_codes');
    }
};
