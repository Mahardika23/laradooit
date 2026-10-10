<?php

declare(strict_types=1);

// This file declares strict types on purpose: the float-refusal cases below
// rely on PHP throwing a TypeError when a float is passed where minor units
// are declared as int. In non-strict callers the same mistake is caught by
// Larastan before it ever runs.

use App\Domain\Money\Money;

it('holds integer minor units and a currency code', function (): void {
    $money = Money::of(12345, 'USD');

    expect($money->minorUnits)->toBe(12345)
        ->and($money->currency)->toBe('USD');
});

it('normalizes the currency code to uppercase', function (): void {
    expect(Money::of(100, 'usd')->currency)->toBe('USD');
});

it('refuses floats for minor units', function (): void {
    /** @var mixed $floatMinorUnits */
    $floatMinorUnits = 12.5;

    Money::of($floatMinorUnits, 'USD');
})->throws(TypeError::class);

it('creates zero in a currency', function (): void {
    $zero = Money::zero('USD');

    expect($zero->minorUnits)->toBe(0)
        ->and($zero->currency)->toBe('USD')
        ->and($zero->isZero())->toBeTrue();
});

it('adds money of the same currency', function (): void {
    $sum = Money::of(1000, 'USD')->add(Money::of(250, 'USD'));

    expect($sum)->toEqual(Money::of(1250, 'USD'));
});

it('subtracts money of the same currency, allowing a negative result', function (): void {
    $difference = Money::of(250, 'USD')->subtract(Money::of(1000, 'USD'));

    expect($difference)->toEqual(Money::of(-750, 'USD'))
        ->and($difference->isNegative())->toBeTrue();
});

it('refuses arithmetic across currencies', function (string $method): void {
    Money::of(1000, 'USD')->{$method}(Money::of(1000, 'EUR'));
})->with(['add', 'subtract'])->throws(InvalidArgumentException::class);

it('compares money of the same currency', function (): void {
    $fiveDollars = Money::of(500, 'USD');

    expect($fiveDollars->compareTo(Money::of(499, 'USD')))->toBe(1)
        ->and($fiveDollars->compareTo(Money::of(500, 'USD')))->toBe(0)
        ->and($fiveDollars->compareTo(Money::of(501, 'USD')))->toBe(-1);
});

it('refuses comparison across currencies', function (): void {
    Money::of(500, 'USD')->compareTo(Money::of(500, 'EUR'));
})->throws(InvalidArgumentException::class);

it('compares currency as well as amount for equality', function (): void {
    expect(Money::of(500, 'USD')->equals(Money::of(500, 'USD')))->toBeTrue()
        ->and(Money::of(500, 'USD')->equals(Money::of(500, 'EUR')))->toBeFalse()
        ->and(Money::of(500, 'USD')->equals(Money::of(501, 'USD')))->toBeFalse();
});

it('formats for display with grouping and two minor digits', function (int $minorUnits, string $currency, string $expected): void {
    expect(Money::of($minorUnits, $currency)->format())->toBe($expected);
})->with([
    'zero' => [0, 'USD', 'USD 0.00'],
    'minor units only' => [7, 'USD', 'USD 0.07'],
    'one major unit' => [100, 'USD', 'USD 1.00'],
    'grouped thousands' => [123456789, 'USD', 'USD 1,234,567.89'],
    'negative' => [-5000, 'USD', '-USD 50.00'],
    'another currency' => [2500, 'myr', 'MYR 25.00'],
]);
