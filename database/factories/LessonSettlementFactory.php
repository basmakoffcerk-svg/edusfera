<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LessonSettlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonSettlement>
 *
 * Lesson and Transaction are created explicitly via ::create() in this
 * codebase (no model factories), so callers must supply lesson_id and
 * transaction_id, e.g. LessonSettlement::factory()->create([
 *   'lesson_id' => $lesson->id,
 *   'transaction_id' => $transaction->id,
 * ]).
 */
class LessonSettlementFactory extends Factory
{
    protected $model = LessonSettlement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => null,
            'transaction_id' => null,
            'net_share' => $this->faker->randomFloat(2, 0, 200),
            'gross_share' => $this->faker->randomFloat(2, 0, 250),
            'settled_at' => now('UTC'),
            'refunded_at' => null,
            'meta' => [],
        ];
    }

    /**
     * Indicate that the settlement represents a refund.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'net_share' => 0,
            'gross_share' => 0,
            'settled_at' => null,
            'refunded_at' => now('UTC'),
        ]);
    }
}
