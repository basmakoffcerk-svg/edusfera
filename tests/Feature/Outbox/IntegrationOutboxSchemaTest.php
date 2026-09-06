<?php

declare(strict_types=1);

namespace Tests\Feature\Outbox;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IntegrationOutboxSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_outbox_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('integration_outbox'),
            'The integration_outbox table should exist after migration.'
        );
    }

    public function test_integration_outbox_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('integration_outbox', [
            'id',
            'event_type',
            'aggregate_type',
            'aggregate_id',
            'payload',
            'occurred_at',
            'available_at',
            'published_at',
            'attempts',
            'last_error',
            'created_at',
            'updated_at',
        ]), 'The integration_outbox table is missing one or more expected columns.');
    }
}
