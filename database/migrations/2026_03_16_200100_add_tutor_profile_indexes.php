<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->index('is_verified');
            $table->index('rating_avg');
            $table->index('price_per_hour');
            $table->index('legal_status');
            $table->index(['is_verified', 'rating_avg']);
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);
            $table->dropIndex(['rating_avg']);
            $table->dropIndex(['price_per_hour']);
            $table->dropIndex(['legal_status']);
            $table->dropIndex(['is_verified', 'rating_avg']);
        });
    }
};
