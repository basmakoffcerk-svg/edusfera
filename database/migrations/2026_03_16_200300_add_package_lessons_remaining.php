<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedInteger('package_lessons_remaining')->nullable()->after('package_lessons');
        });

        // Initialize remaining lessons for existing unpaid/pending package lessons
        \Illuminate\Support\Facades\DB::statement("
            UPDATE lessons
            SET package_lessons_remaining = package_lessons
            WHERE package_code IS NOT NULL AND package_code != 'single'
              AND package_lessons_remaining IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('package_lessons_remaining');
        });
    }
};
