<?php

namespace App\Enums;

/**
 * Which way the money moved on a Transaction. The amount is always stored
 * positive (see ADR 0003); the direction is what makes it spending or
 * receipt. Mirrored by the native Postgres enum type created in the
 * transactions migration.
 */
enum TransactionDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In => 'In',
            self::Out => 'Out',
        };
    }
}
