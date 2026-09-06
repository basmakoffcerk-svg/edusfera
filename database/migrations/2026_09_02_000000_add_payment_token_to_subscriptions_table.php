<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscriptions', 'payment_token')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->string('payment_token')->nullable()->after('responses_used_this_month');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'payment_token')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('payment_token');
            });
        }
    }
};
