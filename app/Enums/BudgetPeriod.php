<?php

namespace App\Enums;

/**
 * The fixed vocabulary of how often a Budget repeats, mirrored by the
 * native Postgres enum type created in the categories migration. Adding a
 * case means adding a value to that type with a migration.
 */
enum BudgetPeriod: string
{
    case Monthly = 'monthly';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Weekly => 'Weekly',
        };
    }
}
