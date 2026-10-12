<?php

namespace App\Http\Requests\Categories;

use App\Enums\BudgetPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetBudgetRequest extends FormRequest
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
            // A Budget is an amount together with its period: both or
            // neither. The amount arrives as the decimal string the user
            // typed — never a float — and is bounded so it always fits a
            // bigint of minor units.
            'amount' => ['required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'period' => ['required', Rule::enum(BudgetPeriod::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.regex' => 'A Budget amount is a decimal number with at most two minor digits, like 1200 or 45.50.',
        ];
    }
}
