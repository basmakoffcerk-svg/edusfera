<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('promo_codes')) {
            try {
                DB::statement("ALTER TABLE promo_codes MODIFY discount_type VARCHAR(32) NOT NULL DEFAULT 'percent'");
                DB::statement('ALTER TABLE promo_codes MODIFY discount_value DECIMAL(10, 2) NOT NULL DEFAULT 0.00');
            } catch (Throwable $e) {
                // SQLite or other non-MySQL drivers ignore MODIFY statement
            }

            Schema::table('promo_codes', function (Blueprint $table) {
                if (! Schema::hasColumn('promo_codes', 'subscription_period')) {
                    $table->string('subscription_period', 32)->nullable()->after('scope');
                }
                if (! Schema::hasColumn('promo_codes', 'subscription_plan')) {
                    $table->string('subscription_plan', 32)->nullable()->after('subscription_period');
                }
            });
        }

        if (Schema::hasTable('subscription_invoices')) {
            Schema::table('subscription_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('subscription_invoices', 'promo_code_id')) {
                    $table->foreignId('promo_code_id')->nullable()->after('amount_kopecks')->constrained('promo_codes')->nullOnDelete();
                }
                if (! Schema::hasColumn('subscription_invoices', 'discount_kopecks')) {
                    $table->integer('discount_kopecks')->default(0)->after('amount_kopecks');
                }
            });
        }

        if (Schema::hasTable('subscriptions')) {
            if (! Schema::hasColumn('subscriptions', 'is_onboarded')) {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->boolean('is_onboarded')->default(true)->after('is_founder');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('promo_codes')) {
            Schema::table('promo_codes', function (Blueprint $table) {
                if (Schema::hasColumn('promo_codes', 'subscription_plan')) {
                    $table->dropColumn('subscription_plan');
                }
                if (Schema::hasColumn('promo_codes', 'subscription_period')) {
                    $table->dropColumn('subscription_period');
                }
            });
        }
    }
};
