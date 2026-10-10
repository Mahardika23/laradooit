<?php

use App\Casts\MoneyCast;
use App\Domain\Money\Money;
use Illuminate\Database\Eloquent\Model;

/**
 * A bare Eloquent model carrying the cast, so the cast can be driven through
 * real attribute reads and writes without a money column of its own yet.
 */
function moneyCastingModel(): Model
{
    return new class extends Model
    {
        protected function casts(): array
        {
            return ['amount' => MoneyCast::class];
        }
    };
}

it('reads a bigint column as Money with integer minor units, never as a float', function (): void {
    $model = moneyCastingModel();

    // PostgreSQL delivers bigint values to PHP as strings.
    $model->setRawAttributes(['amount' => '123456']);

    expect($model->amount)->toBeInstanceOf(Money::class)
        ->and($model->amount->minorUnits)->toBe(123456);
});

it('writes Money back as integer minor units', function (): void {
    $model = moneyCastingModel();

    $model->amount = Money::of(500, 'USD');

    expect($model->getAttributes()['amount'])->toBe(500);
});

it('reads a null column as null', function (): void {
    $model = moneyCastingModel();
    $model->setRawAttributes(['amount' => null]);

    expect($model->amount)->toBeNull();
});

it('takes the currency from the instance base currency in config', function (): void {
    config()->set('finance.currency', 'MYR');

    $model = moneyCastingModel();
    $model->setRawAttributes(['amount' => '2500']);

    expect($model->amount->currency)->toBe('MYR');
});

it('defaults the instance base currency to USD', function (): void {
    expect(config('finance.currency'))->toBe('USD');
});

it('refuses anything that is not Money when writing', function (): void {
    $model = moneyCastingModel();

    $model->amount = 12.5;
})->throws(InvalidArgumentException::class);
