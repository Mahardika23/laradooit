<?php

namespace App\Domain\Money;

use InvalidArgumentException;

/**
 * Money is an integer count of minor units plus a currency code. Arithmetic
 * is integer-only; floats are refused by the int type on every entry point,
 * enforced at runtime for strict callers and by static analysis everywhere
 * else. Formatting is display-only and never touches a float either.
 */
final readonly class Money
{
    private function __construct(
        public int $minorUnits,
        public string $currency,
    ) {}

    public static function of(int $minorUnits, string $currency): self
    {
        return new self($minorUnits, strtoupper($currency));
    }

    public static function zero(string $currency): self
    {
        return new self(0, strtoupper($currency));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    /**
     * Returns -1, 0 or 1 as this amount is less than, equal to, or greater
     * than the other.
     */
    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits <=> $other->minorUnits;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency
            && $this->minorUnits === $other->minorUnits;
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    /**
     * Formats for display as "USD 1,234.56": the currency code, the major
     * units grouped by thousands, and exactly two minor digits. Built from
     * the integer units directly, so no float ever appears in a money path.
     */
    public function format(): string
    {
        $absolute = abs($this->minorUnits);

        $formatted = $this->currency.' '.self::groupThousands(intdiv($absolute, 100))
            .'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);

        return $this->isNegative() ? '-'.$formatted : $formatted;
    }

    private static function groupThousands(int $majorUnits): string
    {
        $digits = (string) $majorUnits;
        $groups = [];

        while (strlen($digits) > 3) {
            $groups[] = substr($digits, -3);
            $digits = substr($digits, 0, -3);
        }

        $groups[] = $digits;

        return implode(',', array_reverse($groups));
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on money of different currencies: {$this->currency} and {$other->currency}."
            );
        }
    }
}
