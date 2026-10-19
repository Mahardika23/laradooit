<?php

namespace App\Domain\Cycles;

use Carbon\CarbonImmutable;

/**
 * Computes Cycle boundaries: the calendar month or the calendar week
 * containing a moment, in the instance timezone, with the configured week
 * start day for weeks. The timezone matters because a moment near midnight
 * UTC is already tomorrow in one part of the world and still yesterday in
 * another, and a purchase must land in the Cycle the user would point at.
 *
 * The service is framework-free on purpose: timezone and week start arrive
 * as parameters read from configuration by the caller, so the boundary
 * rules are exercised across offsets and starts without booting anything.
 */
final class Cycle
{
    /**
     * The calendar month containing the moment: from the first of the month
     * at midnight (inclusive) to the first of the next month at midnight
     * (exclusive), in the instance timezone.
     */
    public static function monthContaining(CarbonImmutable $moment, string $timezone): CyclePeriod
    {
        $start = $moment->setTimezone($timezone)->startOfMonth();

        return new CyclePeriod($start, $start->addMonth());
    }

    /**
     * The calendar week containing the moment: from the configured start
     * day at midnight (inclusive) to the next start day at midnight
     * (exclusive), in the instance timezone.
     */
    public static function weekContaining(CarbonImmutable $moment, string $timezone, WeekStart $weekStart): CyclePeriod
    {
        $start = $moment->setTimezone($timezone)->startOfDay()->startOfWeek($weekStart->carbonDay());

        return new CyclePeriod($start, $start->addWeek());
    }
}
