<?php

namespace App\Domain\Cycles;

use Carbon\CarbonImmutable;

/**
 * One Cycle as a half-open stretch of time: it begins at start (inclusive)
 * and ends at end (exclusive), both midnights in the instance timezone. A
 * Transaction belongs to the Cycle when its occurred-on date falls on or
 * after start and before end, so comparing against the date parts alone is
 * always correct and no boundary instant is ever shared by two Cycles.
 */
final readonly class CyclePeriod
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }
}
