<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bepaid_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event')->nullable();
            $table->string('transaction_id')->nullable();
            $table->json('payload');
            $table->string('ip')->nullable();
            $table->integer('status_code')->default(200);
            $table->text('error_reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bepaid_webhook_logs');
    }
};
