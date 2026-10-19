<?php

namespace App\Domain\Transactions;

use App\Domain\Money\Money;

/**
 * The Duplicate hash: one value standing for "this exact money movement" —
 * Account, occurred-on date, amount, and normalized merchant — so that
 * re-ingesting the same line is recognisable without comparing four fields
 * by hand. An ingested row whose hash already exists on its Account, in any
 * status, is silently dropped; a Rejected row keeps its hash precisely so
 * that memory outlives the dismissal.
 */
final class DuplicateHash
{
    /**
     * @param  string  $accountId  The Account the movement happened on.
     * @param  string  $occurredOn  The date as Y-m-d.
     * @param  Money  $amount  Always positive; the direction is no part of a Duplicate.
     * @param  string  $normalizedMerchant  Already through Merchant::normalize.
     */
    public static function for(string $accountId, string $occurredOn, Money $amount, string $normalizedMerchant): string
    {
        return hash('sha256', implode('|', [
            $accountId,
            $occurredOn,
            (string) $amount->minorUnits,
            $normalizedMerchant,
        ]));
    }
}
