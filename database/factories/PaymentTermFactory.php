<?php

namespace Database\Factories;

use App\Enums\PaymentTermStatus;
use App\Models\BudgetItem;
use App\Models\PaymentTerm;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentTerm> */
class PaymentTermFactory extends Factory
{
    protected $model = PaymentTerm::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'budget_item_id' => BudgetItem::factory(),
            'name' => 'Termin '.fake()->numberBetween(1, 10),
            'sort_order' => 0,
            'planned_amount' => fake()->numberBetween(500_000, 20_000_000),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'actual_paid_amount' => 0,
            'paid_at' => null,
            'status' => PaymentTermStatus::Planned,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function partiallyPaid(): static
    {
        return $this->state(function (array $attributes): array {
            $actual = (int) floor($attributes['planned_amount'] / 2);

            return ['actual_paid_amount' => $actual, 'paid_at' => now(), 'status' => PaymentTermStatus::PartiallyPaid];
        });
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'actual_paid_amount' => $attributes['planned_amount'],
            'paid_at' => now(),
            'status' => PaymentTermStatus::Paid,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => PaymentTermStatus::Cancelled]);
    }
}
