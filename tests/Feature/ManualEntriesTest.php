<?php

use App\Enums\TransactionDirection;
use App\Enums\TransactionKind;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

function validEntry(array $overrides = []): array
{
    return array_merge([
        'occurred_on' => '2026-10-13',
        'account_id' => Account::factory()->create()->id,
        'amount' => '45.50',
        'direction' => 'out',
        'kind' => 'expense',
        'merchant' => 'Corner Bakery',
        'category_id' => Category::factory()->create()->id,
    ], $overrides);
}

it('redirects guests to the login page', function (): void {
    $this->get('/transactions')->assertRedirect('/login');
    $this->post('/transactions', [])->assertRedirect('/login');
});

it('creates a manual entry born confirmed with a hand-set category', function (): void {
    $entry = validEntry();

    $this->actingAs(User::factory()->create())
        ->post('/transactions', $entry)
        ->assertRedirect(route('transactions.index'));

    $this->assertDatabaseHas('transactions', [
        'account_id' => $entry['account_id'],
        'category_id' => $entry['category_id'],
        'occurred_on' => '2026-10-13',
        'amount' => 4550,
        'direction' => 'out',
        'kind' => 'expense',
        'status' => 'confirmed',
        'category_source' => 'manual',
        'merchant_raw' => 'Corner Bakery',
        'merchant_normalized' => 'corner bakery',
        'paired_transfer_id' => null,
        'upload_id' => null,
    ]);
});

it('gives two identical manual entries the same dedup hash and records both, because two identical real purchases happen', function (): void {
    $entry = validEntry();
    $user = User::factory()->create();

    $this->actingAs($user)->post('/transactions', $entry);
    $this->actingAs($user)->post('/transactions', $entry);

    $rows = Transaction::where('account_id', $entry['account_id'])->get();

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('dedup_hash')->unique())->toHaveCount(1);
});

it('gives entries that differ in account, date, amount, or merchant different dedup hashes', function (): void {
    $user = User::factory()->create();
    $entry = validEntry();

    $this->actingAs($user)->post('/transactions', $entry);
    $this->actingAs($user)->post('/transactions', array_merge($entry, ['merchant' => 'Corner Bakery 2']));

    $hashes = Transaction::where('account_id', $entry['account_id'])->pluck('dedup_hash');

    expect($hashes->unique())->toHaveCount(2);
});

it('requires a category, because a manual entry is filed by hand', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry(['category_id' => null]))
        ->assertSessionHasErrors(['category_id']);

    expect(Transaction::count())->toBe(0);
});

it('refuses a category that does not exist', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry(['category_id' => (string) Str::uuid()]))
        ->assertSessionHasErrors(['category_id']);

    expect(Transaction::count())->toBe(0);
});

it('refuses an account that does not exist', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry(['account_id' => (string) Str::uuid()]))
        ->assertSessionHasErrors(['account_id']);

    expect(Transaction::count())->toBe(0);
});

it('requires a merchant', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry(['merchant' => '']))
        ->assertSessionHasErrors(['merchant']);

    expect(Transaction::count())->toBe(0);
});

it('requires a real calendar date', function (string $occurredOn): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry(['occurred_on' => $occurredOn]))
        ->assertSessionHasErrors(['occurred_on']);

    expect(Transaction::count())->toBe(0);
})->with([
    'not a date' => ['yesterday'],
    'wrong shape' => ['13/10/2026'],
    'impossible date' => ['2026-02-30'],
]);

it('refuses an amount that is not a plain positive decimal with at most two minor digits', function (string $amount): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry(['amount' => $amount]))
        ->assertSessionHasErrors(['amount']);

    expect(Transaction::count())->toBe(0);
})->with([
    'three decimal places' => ['12.345'],
    'letters' => ['lots'],
    'negative' => ['-12.00'],
    'zero' => ['0'],
    'zero point zero zero' => ['0.00'],
]);

it('refuses a direction or kind outside the enum', function (string $field, string $value): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry([$field => $value]))
        ->assertSessionHasErrors([$field]);

    expect(Transaction::count())->toBe(0);
})->with([
    'sideways direction' => ['direction', 'sideways'],
    'kind of magic' => ['kind', 'magic'],
]);

it('records every direction and kind the enum knows', function (string $direction, string $kind): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry(['direction' => $direction, 'kind' => $kind]))
        ->assertRedirect(route('transactions.index'));

    $this->assertDatabaseHas('transactions', [
        'direction' => $direction,
        'kind' => $kind,
        'status' => 'confirmed',
    ]);
})->with([
    'income in' => ['in', 'income'],
    'transfer out' => ['out', 'transfer'],
    'transfer in' => ['in', 'transfer'],
    'fee out' => ['out', 'fee'],
    'refund in' => ['in', 'refund'],
]);

it('normalizes the merchant before storing it', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry(['merchant' => 'STARBUCKS   #42']))
        ->assertRedirect(route('transactions.index'));

    $this->assertDatabaseHas('transactions', [
        'merchant_raw' => 'STARBUCKS   #42',
        'merchant_normalized' => 'starbucks 42',
    ]);
});

it('keeps every amount positive whatever direction is typed', function (TransactionDirection $direction, TransactionKind $kind): void {
    $this->actingAs(User::factory()->create())
        ->post('/transactions', validEntry(['direction' => $direction->value, 'kind' => $kind->value]));

    expect(Transaction::where('amount', '<=', 0)->count())->toBe(0);
})->with([
    [TransactionDirection::In, TransactionKind::Income],
    [TransactionDirection::Out, TransactionKind::Expense],
]);
