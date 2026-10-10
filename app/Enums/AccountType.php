<?php

namespace App\Enums;

/**
 * The fixed vocabulary of what an Account is, mirrored by the native
 * Postgres enum type created in the accounts migration. Adding a case means
 * adding a value to that type with a migration.
 */
enum AccountType: string
{
    case Bank = 'bank';
    case EWallet = 'e_wallet';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Bank account',
            self::EWallet => 'E-wallet',
            self::Cash => 'Cash',
        };
    }
}
