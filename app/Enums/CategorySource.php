<?php

namespace App\Enums;

/**
 * How a Transaction got its Category: filed by a Merchant Rule, suggested
 * by the model, or chosen by hand. Mirrored by the native Postgres enum
 * type created in the transactions migration.
 */
enum CategorySource: string
{
    case Rule = 'rule';
    case Llm = 'llm';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Rule => 'Rule',
            self::Llm => 'Model',
            self::Manual => 'Manual',
        };
    }
}
