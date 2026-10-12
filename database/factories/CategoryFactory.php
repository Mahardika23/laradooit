<?php

namespace Database\Factories;

use App\Domain\Money\Money;
use App\Enums\BudgetPeriod;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state. Every fixture is synthetic.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'parent_id' => null,
            'budget_amount' => null,
            'budget_period' => null,
        ];
    }

    /**
     * A Category with a Budget of a round amount for the given period, or a
     * random one.
     */
    public function budgeted(?BudgetPeriod $period = null): static
    {
        return $this->state(fn (): array => [
            'budget_amount' => Money::of(fake()->numberBetween(1, 5_000) * 100, (string) config('finance.currency')),
            'budget_period' => $period ?? fake()->randomElement(BudgetPeriod::cases()),
        ]);
    }
}
