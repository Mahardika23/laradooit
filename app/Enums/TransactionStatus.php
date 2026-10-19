<?php

namespace App\Enums;

/**
 * Where a Transaction stands. A Manual Entry is born Confirmed; an Ingested
 * Transaction waits in the Inbox as pending review until it is Confirmed or
 * Rejected. Rejected rows are invisible everywhere but never deleted, so
 * their Duplicate memory survives (see ADR 0004). Mirrored by the native
 * Postgres enum type created in the transactions migration.
 */
enum TransactionStatus: string
{
    case PendingReview = 'pending_review';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending review',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
        };
    }
}
