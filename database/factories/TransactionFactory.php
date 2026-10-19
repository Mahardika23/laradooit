<?php

namespace Database\Factories;

use App\Domain\Money\Money;
use App\Enums\CategorySource;
use App\Enums\TransactionDirection;
use App\Enums\TransactionKind;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state: a Confirmed Manual Entry, the way
     * most rows in a used instance are born. Every fixture is synthetic.
     * The normalized merchant and the dedup hash are derived by the model
     * on save, so they are never stated here.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'occurred_on' => now()->toDateString(),
            'amount' => Money::of(fake()->numberBetween(1, 2_000) * 100, (string) config('finance.currency')),
            'direction' => TransactionDirection::Out,
            'kind' => TransactionKind::Expense,
            'status' => TransactionStatus::Confirmed,
            'category_source' => CategorySource::Manual,
            'merchant_raw' => fake()->company(),
            'paired_transfer_id' => null,
            'upload_id' => null,
        ];
    }

    /**
     * An Ingested Transaction still waiting in the Inbox: pending review,
     * Category suggested by the model, and born of an Upload.
     */
    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => TransactionStatus::PendingReview,
            'category_source' => CategorySource::Llm,
            'upload_id' => (string) Str::uuid(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => ['status' => TransactionStatus::Rejected]);
    }
}
