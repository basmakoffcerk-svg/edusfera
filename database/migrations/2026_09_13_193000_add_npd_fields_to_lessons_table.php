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
            $table->string('npd_receipt_number', 64)->nullable()->after('notes');
            $table->dateTime('npd_receipt_issued_at')->nullable()->after('npd_receipt_number');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['npd_receipt_number', 'npd_receipt_issued_at']);
        });
    }
};
