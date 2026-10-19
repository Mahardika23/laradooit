<?php

use App\Domain\Cycles\Cycle;
use App\Domain\Cycles\WeekStart;
use Carbon\CarbonImmutable;

/*
 * The Cycle rules are combinatorial: which month or week a moment belongs
 * to depends on the timezone it is read in and the day the week starts on.
 * These cases pin the boundaries across offsets from UTC-10 to UTC+14 and
 * across both week starts, at the dates where a wrong rule would show.
 */

it('bounds the calendar month containing a moment', function (): void {
    $period = Cycle::monthContaining(CarbonImmutable::parse('2026-10-13 15:04:00', 'UTC'), 'UTC');

    expect($period->start->toDateTimeString())->toBe('2026-10-01 00:00:00')
        ->and($period->end->toDateTimeString())->toBe('2026-11-01 00:00:00')
        ->and($period->startDate())->toBe('2026-10-01')
        ->and($period->endDate())->toBe('2026-11-01');
});

it('places a moment just after midnight UTC in different months at different offsets', function (string $timezone, string $expectedStart, string $expectedEnd): void {
    // The same instant is already 1 October ahead of the line and still
    // 30 September behind it; the Cycle must follow the local calendar.
    $period = Cycle::monthContaining(CarbonImmutable::parse('2026-10-01 00:30:00', 'UTC'), $timezone);

    expect($period->startDate())->toBe($expectedStart)
        ->and($period->endDate())->toBe($expectedEnd);
})->with([
    'UTC' => ['UTC', '2026-10-01', '2026-11-01'],
    'Kiritimati, UTC+14' => ['Pacific/Kiritimati', '2026-10-01', '2026-11-01'],
    'Tokyo, UTC+9' => ['Asia/Tokyo', '2026-10-01', '2026-11-01'],
    'New York, UTC-4' => ['America/New_York', '2026-09-01', '2026-10-01'],
    'Los Angeles, UTC-7' => ['America/Los_Angeles', '2026-09-01', '2026-10-01'],
    'Honolulu, UTC-10' => ['Pacific/Honolulu', '2026-09-01', '2026-10-01'],
]);

it('bounds the calendar week containing a midweek moment under both week starts', function (WeekStart $weekStart, string $expectedStart, string $expectedEnd): void {
    $period = Cycle::weekContaining(CarbonImmutable::parse('2026-10-14 12:00:00', 'UTC'), 'UTC', $weekStart);

    expect($period->start->toDateTimeString())->toBe($expectedStart.' 00:00:00')
        ->and($period->end->toDateTimeString())->toBe($expectedEnd.' 00:00:00');
})->with([
    'Monday start' => [WeekStart::Monday, '2026-10-12', '2026-10-19'],
    'Sunday start' => [WeekStart::Sunday, '2026-10-11', '2026-10-18'],
]);

it('puts a Sunday at the head of its own week only when the week starts on Sunday', function (WeekStart $weekStart, string $expectedStart, string $expectedEnd): void {
    $period = Cycle::weekContaining(CarbonImmutable::parse('2026-10-18 09:00:00', 'UTC'), 'UTC', $weekStart);

    expect($period->startDate())->toBe($expectedStart)
        ->and($period->endDate())->toBe($expectedEnd);
})->with([
    'Monday start: the Sunday closes the week that began Monday' => [WeekStart::Monday, '2026-10-12', '2026-10-19'],
    'Sunday start: the Sunday opens a new week' => [WeekStart::Sunday, '2026-10-18', '2026-10-25'],
]);

it('puts a Monday at the head of its own week only when the week starts on Monday', function (WeekStart $weekStart, string $expectedStart, string $expectedEnd): void {
    $period = Cycle::weekContaining(CarbonImmutable::parse('2026-10-12 09:00:00', 'UTC'), 'UTC', $weekStart);

    expect($period->startDate())->toBe($expectedStart)
        ->and($period->endDate())->toBe($expectedEnd);
})->with([
    'Monday start: the Monday opens a new week' => [WeekStart::Monday, '2026-10-12', '2026-10-19'],
    'Sunday start: the Monday belongs to the week that began Sunday' => [WeekStart::Sunday, '2026-10-11', '2026-10-18'],
]);

it('reads the week start from the local calendar, not the UTC one, across offsets', function (string $timezone, WeekStart $weekStart, string $expectedStart, string $expectedEnd): void {
    // Half past midnight UTC on Monday 12 October: ahead of the line it is
    // already Monday, behind it still Sunday. Which week the moment belongs
    // to follows the local day.
    $period = Cycle::weekContaining(CarbonImmutable::parse('2026-10-12 00:30:00', 'UTC'), $timezone, $weekStart);

    expect($period->startDate())->toBe($expectedStart)
        ->and($period->endDate())->toBe($expectedEnd);
})->with([
    'Kiritimati UTC+14, Monday start: already Monday, week opens that day' => ['Pacific/Kiritimati', WeekStart::Monday, '2026-10-12', '2026-10-19'],
    'Kiritimati UTC+14, Sunday start: already Monday, week opened Sunday' => ['Pacific/Kiritimati', WeekStart::Sunday, '2026-10-11', '2026-10-18'],
    'Honolulu UTC-10, Monday start: still Sunday, week opened Monday 5th' => ['Pacific/Honolulu', WeekStart::Monday, '2026-10-05', '2026-10-12'],
    'Honolulu UTC-10, Sunday start: still Sunday, week opens that day' => ['Pacific/Honolulu', WeekStart::Sunday, '2026-10-11', '2026-10-18'],
]);

it('never lets two neighbouring cycles share a boundary instant', function (): void {
    $october = Cycle::monthContaining(CarbonImmutable::parse('2026-10-13 12:00:00', 'UTC'), 'UTC');
    $november = Cycle::monthContaining(CarbonImmutable::parse('2026-11-13 12:00:00', 'UTC'), 'UTC');

    expect($october->end->toDateTimeString())->toBe($november->start->toDateTimeString())
        ->and($october->endDate())->toBe('2026-11-01');
});
