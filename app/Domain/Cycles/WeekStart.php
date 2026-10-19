<?php

namespace App\Domain\Cycles;

use Carbon\CarbonImmutable;

/**
 * The day the calendar week begins on this instance, read from the
 * APP_WEEK_START environment value. Only Monday and Sunday exist: they are
 * the starts a weekly Budget is ever measured against, and keeping the
 * vocabulary to two makes every Cycle boundary testable by hand.
 */
enum WeekStart: string
{
    case Monday = 'monday';
    case Sunday = 'sunday';

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Monday',
            self::Sunday => 'Sunday',
        };
    }

    /**
     * The Carbon day-of-week constant the Cycle arithmetic works in.
     */
    public function carbonDay(): int
    {
        return match ($this) {
            self::Monday => CarbonImmutable::MONDAY,
            self::Sunday => CarbonImmutable::SUNDAY,
        };
    }
}
