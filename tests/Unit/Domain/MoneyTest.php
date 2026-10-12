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

it('parses a decimal string into integer minor units without ever seeing a float', function (string $decimal, int $expectedMinorUnits): void {
    expect(Money::fromDecimal($decimal, 'USD'))->toEqual(Money::of($expectedMinorUnits, 'USD'));
})->with([
    'zero' => ['0', 0],
    'zero with minor digits' => ['0.00', 0],
    'whole major units' => ['12', 1200],
    'one decimal place pads to two' => ['12.3', 1230],
    'two decimal places' => ['12.34', 1234],
    'minor units only' => ['0.07', 7],
    'large amount' => ['1234567.89', 123456789],
    'negative' => ['-50.25', -5025],
]);

it('normalizes the currency code when parsing', function (): void {
    expect(Money::fromDecimal('1.00', 'usd')->currency)->toBe('USD');
});

it('refuses a decimal string it cannot parse exactly rather than rounding it', function (string $decimal): void {
    Money::fromDecimal($decimal, 'USD');
})->with([
    'empty' => [''],
    'letters' => ['lots'],
    'three decimal places' => ['12.345'],
    'trailing dot' => ['12.'],
    'leading dot' => ['.5'],
    'dot only' => ['.'],
    'sign only' => ['-'],
    'thousands separator' => ['1,234.56'],
    'scientific notation' => ['1e3'],
    'more major digits than an integer can hold' => ['1234567890123456'],
])->throws(InvalidArgumentException::class);

it('renders integer minor units as a decimal string with exactly two minor digits', function (int $minorUnits, string $expected): void {
    expect(Money::of($minorUnits, 'USD')->toDecimal())->toBe($expected);
})->with([
    'zero' => [0, '0.00'],
    'minor units only' => [7, '0.07'],
    'whole major units' => [1200, '12.00'],
    'two minor digits' => [1234, '12.34'],
    'negative' => [-5025, '-50.25'],
]);

it('round-trips a decimal string through parsing and rendering', function (string $decimal, string $rendered): void {
    expect(Money::fromDecimal($decimal, 'USD')->toDecimal())->toBe($rendered);
})->with([
    'whole' => ['12', '12.00'],
    'one decimal place' => ['12.3', '12.30'],
    'two decimal places' => ['12.34', '12.34'],
    'negative' => ['-50.25', '-50.25'],
]);
