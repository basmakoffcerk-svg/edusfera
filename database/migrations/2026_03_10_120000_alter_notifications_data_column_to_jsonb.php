<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications') || DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE notifications
            ALTER COLUMN data TYPE jsonb
            USING data::jsonb
        SQL);
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications') || DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE notifications
            ALTER COLUMN data TYPE text
            USING data::text
        SQL);
    }
};
