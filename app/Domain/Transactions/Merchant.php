<?php

namespace App\Domain\Transactions;

/**
 * Merchant name normalization, the merchant half of what makes two rows the
 * same purchase. A merchant arrives as typed or as a statement renders it —
 * "STARBUCKS  #42", "Starbucks #42" — and normalizes to one shape so the
 * Duplicate hash does not care which channel or casing produced it.
 *
 * The rule, in full: lowercase, then every run of characters that are
 * neither letters nor digits collapses to a single space, and the ends are
 * trimmed. It is deliberately crude; a sharper rule would keep having to
 * know what each bank does to a name.
 */
final class Merchant
{
    public static function normalize(string $raw): string
    {
        $lowercased = mb_strtolower($raw);

        $collapsed = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $lowercased) ?? $lowercased;

        return trim($collapsed);
    }
}
