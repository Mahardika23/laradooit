<?php

namespace App\Http\Requests\Transactions;

use App\Domain\Money\Money;
use App\Enums\TransactionDirection;
use App\Enums\TransactionKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class StoreManualEntryRequest extends FormRequest
{
    /**
     * The instance has one user and the route is behind auth; anyone reaching
     * this request is that user.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'occurred_on' => ['required', 'date_format:Y-m-d'],
            'account_id' => ['required', 'uuid', Rule::exists('accounts', 'id')],
            // The amount arrives as the decimal string the user typed —
            // never a float — is bounded so it always fits a bigint of minor
            // units, and is positive by definition: the direction says which
            // way the money moved, so a zero or a sign is a typing mistake.
            'amount' => [
                'required',
                'string',
                'regex:/^\d{1,13}(\.\d{1,2})?$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        if (Money::fromDecimal((string) $value, 'USD')->isZero()) {
                            $fail('An amount of zero records no movement of money.');
                        }
                    } catch (InvalidArgumentException) {
                        // The regex rule reports the malformed amount.
                    }
                },
            ],
            'direction' => ['required', Rule::enum(TransactionDirection::class)],
            'kind' => ['required', Rule::enum(TransactionKind::class)],
            'merchant' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'uuid', Rule::exists('categories', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.regex' => 'An amount is a decimal number with at most two minor digits, like 12 or 45.50.',
            'category_id.required' => 'A Manual Entry is filed under a Category; choose one.',
        ];
    }
}
