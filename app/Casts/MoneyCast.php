<?php

namespace App\Casts;

use App\Domain\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Maps a bigint minor-units column to the Money value object and back. The
 * currency is always the instance base currency from configuration, and the
 * column is only ever read as integer minor units, never as a float.
 *
 * @implements CastsAttributes<Money|null, mixed>
 */
class MoneyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::of((int) $value, (string) config('finance.currency'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Money) {
            throw new InvalidArgumentException(
                'A money column is only ever written as a Money value object, never as a raw number.'
            );
        }

        return $value->minorUnits;
    }
}
