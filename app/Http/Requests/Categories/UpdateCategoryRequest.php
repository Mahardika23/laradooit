<?php

namespace App\Http\Requests\Categories;

use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                    $parent = Category::find($value);

                    // A Category cannot be its own ancestor: the new parent
                    // may not be the Category itself nor sit beneath it.
                    if ($parent !== null && $parent->isSelfOrDescendantOf($category)) {
                        $fail('A Category cannot be its own ancestor.');
                    }
                },
            ],
        ];
    }
}
