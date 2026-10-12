<?php

namespace App\Http\Controllers;

use App\Domain\Money\Money;
use App\Enums\BudgetPeriod;
use App\Http\Requests\Categories\SetBudgetRequest;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::orderBy('name')->get();

        return Inertia::render('categories/index', [
            'categories' => $this->presentTree($categories),
            'parentOptions' => $this->presentParentOptions($categories),
            'budgetPeriods' => array_map(
                fn (BudgetPeriod $period): array => ['value' => $period->value, 'label' => $period->label()],
                BudgetPeriod::cases(),
            ),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return to_route('categories.index');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('categories.index');
    }

    public function setBudget(SetBudgetRequest $request, Category $category): RedirectResponse
    {
        $category->setBudget(
            Money::fromDecimal($request->validated('amount'), (string) config('finance.currency')),
            BudgetPeriod::from($request->validated('period')),
        );

        return to_route('categories.index');
    }

    public function clearBudget(Category $category): RedirectResponse
    {
        $category->clearBudget();

        return to_route('categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return to_route('categories.index')->withErrors([
                'delete' => "\"{$category->name}\" still has Categories beneath it; move or delete them first.",
            ]);
        }

        $category->delete();

        return to_route('categories.index');
    }

    /**
     * The tree as nested nodes, parents and children alike, each carrying
     * its Budget if it has one. Built from one flat fetch rather than
     * recursive queries.
     *
     * @param  Collection<int, Category>  $categories
     * @return list<array<string, mixed>>
     */
    private function presentTree(Collection $categories, ?string $parentId = null): array
    {
        return $categories->where('parent_id', $parentId)
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'parentId' => $category->parent_id,
                'budget' => $this->presentBudget($category),
                'children' => $this->presentTree($categories, $category->id),
            ])
            ->values()
            ->all();
    }

    /**
     * Every Category as a flat parent-picker list in tree order, each with
     * its depth so the picker can indent.
     *
     * @param  Collection<int, Category>  $categories
     * @return list<array{id: string, name: string, depth: int}>
     */
    private function presentParentOptions(Collection $categories, ?string $parentId = null, int $depth = 0): array
    {
        return $categories->where('parent_id', $parentId)
            ->flatMap(fn (Category $category): array => array_merge(
                [['id' => $category->id, 'name' => $category->name, 'depth' => $depth]],
                $this->presentParentOptions($categories, $category->id, $depth + 1),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array{amount: string, amountDecimal: string, period: string, periodLabel: string}|null
     */
    private function presentBudget(Category $category): ?array
    {
        if ($category->budget_amount === null || $category->budget_period === null) {
            return null;
        }

        return [
            'amount' => $category->budget_amount->format(),
            'amountDecimal' => $category->budget_amount->toDecimal(),
            'period' => $category->budget_period->value,
            'periodLabel' => $category->budget_period->label(),
        ];
    }
}
