<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->string('package_code', 20)->default('single')->after('payment_status');
            $table->unsignedSmallInteger('package_lessons')->default(1)->after('package_code');
            $table->decimal('package_total', 10, 2)->nullable()->after('package_lessons');
            $table->decimal('package_discount', 10, 2)->default(0)->after('package_total');

            $table->index(['tutor_id', 'package_code']);
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropIndex(['tutor_id', 'package_code']);
            $table->dropColumn([
                'package_code',
                'package_lessons',
                'package_total',
                'package_discount',
            ]);
        });
    }
};

