<?php

use App\Domain\Money\Money;
use App\Domain\Transactions\DuplicateHash;
use App\Domain\Transactions\Merchant;

/*
 * The Duplicate hash stands for one exact money movement: Account, date,
 * amount, and normalized merchant. The same four inputs always produce the
 * same hash, and changing any one of them produces a different one — that
 * is the whole of the rule.
 */

it('is a sha256 hex string', function (): void {
    $hash = DuplicateHash::for('account-1', '2026-10-13', Money::of(1250, 'USD'), 'starbucks');

    expect($hash)->toMatch('/^[0-9a-f]{64}$/');
});

it('is deterministic for the same Account, date, amount, and normalized merchant', function (): void {
    $first = DuplicateHash::for('account-1', '2026-10-13', Money::of(1250, 'USD'), 'starbucks');
    $second = DuplicateHash::for('account-1', '2026-10-13', Money::of(1250, 'USD'), 'starbucks');

    expect($first)->toBe($second);
});

it('changes when any one of Account, date, amount, or merchant changes', function (string $accountId, string $occurredOn, Money $amount, string $merchant): void {
    $base = DuplicateHash::for('account-1', '2026-10-13', Money::of(1250, 'USD'), 'starbucks');

    expect(DuplicateHash::for($accountId, $occurredOn, $amount, $merchant))->not->toBe($base);
})->with([
    'another Account' => ['account-2', '2026-10-13', Money::of(1250, 'USD'), 'starbucks'],
    'another date' => ['account-1', '2026-10-14', Money::of(1250, 'USD'), 'starbucks'],
    'another amount' => ['account-1', '2026-10-13', Money::of(1251, 'USD'), 'starbucks'],
    'another merchant' => ['account-1', '2026-10-13', Money::of(1250, 'USD'), 'starbucks 42'],
]);

it('matches across raw merchant renderings once they are normalized', function (): void {
    $typed = DuplicateHash::for('account-1', '2026-10-13', Money::of(1250, 'USD'), Merchant::normalize('Starbucks'));
    $statement = DuplicateHash::for('account-1', '2026-10-13', Money::of(1250, 'USD'), Merchant::normalize('  STARBUCKS  '));

    expect($typed)->toBe($statement);
});
