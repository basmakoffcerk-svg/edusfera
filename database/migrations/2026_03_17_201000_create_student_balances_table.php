<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('available_amount', 12, 2)->default(0);
            $table->decimal('locked_amount', 12, 2)->default(0);
            $table->decimal('total_topped_up', 12, 2)->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->decimal('total_refunded', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_balances');
    }
};
