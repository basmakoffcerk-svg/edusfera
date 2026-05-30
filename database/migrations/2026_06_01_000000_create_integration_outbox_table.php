<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the integration_outbox table used by the outbox pattern to
     * persist integration events in the same transaction as the domain
     * change, guaranteeing at-least-once delivery to external consumers.
     */
    public function up(): void
    {
        Schema::create('integration_outbox', function (Blueprint $table): void {
            // Primary key is the event id (uuid), shared with the envelope.
            $table->uuid('id')->primary();

            $table->string('event_type', 100);
            $table->string('aggregate_type', 50);
            $table->string('aggregate_id', 100);

            // Full serialized envelope. json works on sqlite, mysql and pgsql.
            $table->json('payload');

            $table->timestamp('occurred_at');
            $table->timestamp('available_at');
            $table->timestamp('published_at')->nullable();

            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamps();

            // Publisher scans unpublished rows ready to be sent.
            $table->index(['published_at', 'available_at']);
            // Dedup / partitioning by aggregate.
            $table->index(['aggregate_type', 'aggregate_id']);
            // Filtering / cleanup by event type.
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_outbox');
    }
};
