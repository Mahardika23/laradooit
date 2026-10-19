<?php

use App\Domain\Transactions\Merchant;

/*
 * Normalization is the rule that lets "the same merchant" mean one thing
 * however the name arrived: lowercase, every run of non-letter non-digit
 * characters collapsed to one space, ends trimmed.
 */

it('lowercases the merchant', function (): void {
    expect(Merchant::normalize('STARBUCKS'))->toBe('starbucks');
});

it('trims the ends', function (): void {
    expect(Merchant::normalize('  starbucks  '))->toBe('starbucks');
});

it('collapses runs of spaces to one', function (): void {
    expect(Merchant::normalize('star   bucks'))->toBe('star bucks');
});

it('turns every run of punctuation into a single space', function (): void {
    expect(Merchant::normalize("McDonald's #42"))->toBe('mcdonald s 42');
});

it('collapses tabs and newlines the same as spaces', function (): void {
    expect(Merchant::normalize("Kopi\tKenangan\nSenja"))->toBe('kopi kenangan senja');
});

it('keeps letters and digits of every alphabet', function (): void {
    expect(Merchant::normalize('Café Müller 7'))->toBe('café müller 7');
});

it('normalizes punctuation-only input to the empty string', function (): void {
    expect(Merchant::normalize(' #! '))->toBe('');
});

it('leaves an already normalized merchant untouched', function (): void {
    expect(Merchant::normalize('starbucks 42'))->toBe('starbucks 42');
});

it('recognises the same merchant across statement renderings', function (string $raw): void {
    expect(Merchant::normalize($raw))->toBe('starbucks 42');
})->with([
    'STARBUCKS #42',
    'Starbucks #42',
    'starbucks 42',
    '  STARBUCKS   #42  ',
]);
