<?php

namespace App\Enums;

/**
 * What sort of movement a Transaction is. Mirrored by the native Postgres
 * enum type created in the transactions migration. Adding a case means
 * adding a value to that type with a migration.
 */
enum TransactionKind: string
{
    case Expense = 'expense';
    case Income = 'income';
    case Transfer = 'transfer';
    case Fee = 'fee';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Expense => 'Expense',
            self::Income => 'Income',
            self::Transfer => 'Transfer',
            self::Fee => 'Fee',
            self::Refund => 'Refund',
        };
    }
}
