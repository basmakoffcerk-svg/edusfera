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
        if (! Schema::hasColumn('subscriptions', 'is_onboarded')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->boolean('is_onboarded')->default(true)->after('is_founder');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'is_onboarded')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('is_onboarded');
            });
        }
    }
};
