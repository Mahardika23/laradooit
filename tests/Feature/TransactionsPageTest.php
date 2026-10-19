<?php

use App\Domain\Money\Money;
use App\Enums\TransactionDirection;
use App\Enums\TransactionKind;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    // Pin "now" so the current Cycle is a fixed month whatever day the
    // suite runs.
    CarbonImmutable::setTestNow('2026-10-15 12:00:00');
});

it('shows only confirmed transactions of the current monthly cycle, newest first', function (): void {
    $account = Account::factory()->create(['name' => 'Cash Wallet']);
    $category = Category::factory()->create(['name' => 'Groceries']);

    $first = Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $category->id,
        'occurred_on' => '2026-10-03',
        'merchant_raw' => 'Alpha Market',
        'amount' => Money::of(1099, 'USD'),
    ]);
    $second = Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $category->id,
        'occurred_on' => '2026-10-10',
        'merchant_raw' => 'Beta Store',
        'amount' => Money::of(2500, 'USD'),
    ]);
    // A boundary day either side of the month, a pending row, and a
    // rejected row — none of them belong on the page.
    Transaction::factory()->create(['occurred_on' => '2026-09-30', 'merchant_raw' => 'Last Month']);
    Transaction::factory()->create(['occurred_on' => '2026-11-01', 'merchant_raw' => 'Next Month']);
    Transaction::factory()->pending()->create(['occurred_on' => '2026-10-10', 'merchant_raw' => 'Pending One']);
    Transaction::factory()->rejected()->create(['occurred_on' => '2026-10-10', 'merchant_raw' => 'Rejected One']);

    $this->actingAs(User::factory()->create())
        ->get('/transactions')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/index')
            ->has('transactions', 2)
            ->where('transactions.0.id', $second->id)
            ->where('transactions.0.occurredOn', '2026-10-10')
            ->where('transactions.0.merchant', 'Beta Store')
            ->where('transactions.0.amount', 'USD 25.00')
            ->where('transactions.0.direction', 'out')
            ->where('transactions.0.kind', 'expense')
            ->where('transactions.0.kindLabel', 'Expense')
            ->where('transactions.0.category', 'Groceries')
            ->where('transactions.0.account', 'Cash Wallet')
            ->where('transactions.0.internal', false)
            ->where('transactions.1.id', $first->id)
        );
});

it('labels the current cycle from the instance timezone, not from UTC', function (): void {
    config(['app.timezone' => 'America/New_York']);

    // Half past midnight UTC on 1 November: New York is still in October,
    // so October is the current Cycle there.
    CarbonImmutable::setTestNow('2026-11-01 00:30:00');

    $this->actingAs(User::factory()->create())
        ->get('/transactions')
        ->assertInertia(fn (Assert $page) => $page
            ->where('cycle.label', 'October 2026')
            ->where('cycle.param', '2026-10')
            ->where('cycle.previous', '2026-09')
            ->where('cycle.next', '2026-11')
        );
});

it('moves to previous and next cycles through the cycle parameter', function (): void {
    Transaction::factory()->create(['occurred_on' => '2026-09-14', 'merchant_raw' => 'September One']);
    Transaction::factory()->create(['occurred_on' => '2026-10-14', 'merchant_raw' => 'October One']);

    $this->actingAs(User::factory()->create())
        ->get('/transactions?cycle=2026-09')
        ->assertInertia(fn (Assert $page) => $page
            ->where('cycle.label', 'September 2026')
            ->where('cycle.param', '2026-09')
            ->where('cycle.previous', '2026-08')
            ->where('cycle.next', '2026-10')
            ->has('transactions', 1)
            ->where('transactions.0.merchant', 'September One')
        );
});

it('falls back to the current cycle when the parameter is not a month', function (string $cycle): void {
    $this->actingAs(User::factory()->create())
        ->get("/transactions?cycle={$cycle}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('cycle.param', '2026-10')
        );
})->with([
    'gibberish' => ['gibberish'],
    'not a month' => ['2026-13'],
    'a full date' => ['2026-10-13'],
]);

it('totals spent and received over the rows shown, never netting one against the other', function (): void {
    Transaction::factory()->create(['occurred_on' => '2026-10-05', 'amount' => Money::of(3000, 'USD'), 'direction' => TransactionDirection::Out]);
    Transaction::factory()->create(['occurred_on' => '2026-10-06', 'amount' => Money::of(1250, 'USD'), 'direction' => TransactionDirection::Out]);
    Transaction::factory()->create(['occurred_on' => '2026-10-07', 'amount' => Money::of(50000, 'USD'), 'direction' => TransactionDirection::In, 'kind' => TransactionKind::Income]);
    // Outside the cycle, so outside the totals.
    Transaction::factory()->create(['occurred_on' => '2026-09-28', 'amount' => Money::of(99900, 'USD')]);

    $this->actingAs(User::factory()->create())
        ->get('/transactions')
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.spent', 'USD 42.50')
            ->where('totals.received', 'USD 500.00')
        );
});

it('counts external transfers in the totals but excludes paired transfer legs, which are money that never left', function (): void {
    $cash = Account::factory()->create();
    $bank = Account::factory()->create();

    // An outgoing External Transfer: real spending, it counts.
    Transaction::factory()->create([
        'account_id' => $cash->id,
        'occurred_on' => '2026-10-05',
        'amount' => Money::of(2000, 'USD'),
        'direction' => TransactionDirection::Out,
        'kind' => TransactionKind::Transfer,
    ]);

    // Two legs of one internal hop: neither counts.
    $outLeg = Transaction::factory()->create([
        'account_id' => $cash->id,
        'occurred_on' => '2026-10-06',
        'amount' => Money::of(7000, 'USD'),
        'direction' => TransactionDirection::Out,
        'kind' => TransactionKind::Transfer,
    ]);
    $inLeg = Transaction::factory()->create([
        'account_id' => $bank->id,
        'occurred_on' => '2026-10-06',
        'amount' => Money::of(7000, 'USD'),
        'direction' => TransactionDirection::In,
        'kind' => TransactionKind::Transfer,
    ]);
    $outLeg->update(['paired_transfer_id' => $inLeg->id]);
    $inLeg->update(['paired_transfer_id' => $outLeg->id]);

    $this->actingAs(User::factory()->create())
        ->get('/transactions')
        ->assertInertia(fn (Assert $page) => $page
            ->has('transactions', 3)
            ->where('transactions.0.internal', true)
            ->where('transactions.1.internal', true)
            ->where('transactions.2.internal', false)
            ->where('totals.spent', 'USD 20.00')
            ->where('totals.received', 'USD 0.00')
        );
});

it('offers the form every active account, the category tree, both enums, and today', function (): void {
    $cash = Account::factory()->create(['name' => 'Cash Wallet']);
    Account::factory()->archived()->create(['name' => 'Old Bank']);
    $food = Category::factory()->create(['name' => 'Food']);
    $groceries = Category::factory()->create(['name' => 'Groceries', 'parent_id' => $food->id]);

    $this->actingAs(User::factory()->create())
        ->get('/transactions')
        ->assertInertia(fn (Assert $page) => $page
            ->where('accounts', [['id' => $cash->id, 'name' => 'Cash Wallet']])
            ->where('categories', [
                ['id' => $food->id, 'name' => 'Food', 'depth' => 0],
                ['id' => $groceries->id, 'name' => 'Groceries', 'depth' => 1],
            ])
            ->where('directions', [
                ['value' => 'in', 'label' => 'In'],
                ['value' => 'out', 'label' => 'Out'],
            ])
            ->where('kinds', [
                ['value' => 'expense', 'label' => 'Expense'],
                ['value' => 'income', 'label' => 'Income'],
                ['value' => 'transfer', 'label' => 'Transfer'],
                ['value' => 'fee', 'label' => 'Fee'],
                ['value' => 'refund', 'label' => 'Refund'],
            ])
            ->where('today', '2026-10-15')
        );
});
