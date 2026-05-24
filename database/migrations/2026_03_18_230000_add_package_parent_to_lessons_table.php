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
            $table->foreignId('package_parent_lesson_id')
                ->nullable()
                ->after('package_lessons_remaining')
                ->constrained('lessons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('package_parent_lesson_id');
        });
    }
};
