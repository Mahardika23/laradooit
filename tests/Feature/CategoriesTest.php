<?php

use App\Domain\Money\Money;
use App\Enums\BudgetPeriod;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the login page', function (): void {
    $this->get('/categories')->assertRedirect('/login');
});

it('shows the category tree with each category and its budget, if any', function (): void {
    $food = Category::factory()->create([
        'name' => 'Food',
        'budget_amount' => Money::of(50000, 'USD'),
        'budget_period' => BudgetPeriod::Monthly,
    ]);
    $groceries = Category::factory()->create([
        'name' => 'Groceries',
        'parent_id' => $food->id,
        'budget_amount' => Money::of(20000, 'USD'),
        'budget_period' => BudgetPeriod::Weekly,
    ]);
    Category::factory()->create(['name' => 'Eating out', 'parent_id' => $food->id]);
    Category::factory()->create(['name' => 'Transport']);

    $this->actingAs(User::factory()->create())
        ->get('/categories')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->has('categories', 2)
            ->where('categories.0.name', 'Food')
            ->where('categories.0.parentId', null)
            ->where('categories.0.budget.amount', 'USD 500.00')
            ->where('categories.0.budget.amountDecimal', '500.00')
            ->where('categories.0.budget.period', 'monthly')
            ->where('categories.0.budget.periodLabel', 'Monthly')
            ->has('categories.0.children', 2)
            ->where('categories.0.children.0.name', 'Eating out')
            ->where('categories.0.children.0.parentId', $food->id)
            ->where('categories.0.children.0.budget', null)
            ->where('categories.0.children.1.id', $groceries->id)
            ->where('categories.0.children.1.parentId', $food->id)
            ->where('categories.0.children.1.budget.amount', 'USD 200.00')
            ->where('categories.0.children.1.budget.amountDecimal', '200.00')
            ->where('categories.0.children.1.budget.period', 'weekly')
            ->where('categories.0.children.1.budget.periodLabel', 'Weekly')
            ->has('categories.0.children.1.children', 0)
            ->where('categories.1.name', 'Transport')
            ->where('categories.1.budget', null)
            ->has('categories.1.children', 0)
        );
});

it('offers every category as a parent option in tree order and every budget period for the forms', function (): void {
    $food = Category::factory()->create(['name' => 'Food']);
    $groceries = Category::factory()->create(['name' => 'Groceries', 'parent_id' => $food->id]);
    $transport = Category::factory()->create(['name' => 'Transport']);

    $this->actingAs(User::factory()->create())
        ->get('/categories')
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->where('parentOptions', [
                ['id' => $food->id, 'name' => 'Food', 'depth' => 0],
                ['id' => $groceries->id, 'name' => 'Groceries', 'depth' => 1],
                ['id' => $transport->id, 'name' => 'Transport', 'depth' => 0],
            ])
            ->where('budgetPeriods', [
                ['value' => 'monthly', 'label' => 'Monthly'],
                ['value' => 'weekly', 'label' => 'Weekly'],
            ])
        );
});

it('creates a category at the root', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/categories', ['name' => 'Food'])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => 'Food',
        'parent_id' => null,
        'budget_amount' => null,
        'budget_period' => null,
    ]);
});

it('creates a category under a parent', function (): void {
    $food = Category::factory()->create(['name' => 'Food']);

    $this->actingAs(User::factory()->create())
        ->post('/categories', ['name' => 'Groceries', 'parent_id' => $food->id])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', ['name' => 'Groceries', 'parent_id' => $food->id]);
});

it('rejects a category without a name', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/categories', [])
        ->assertSessionHasErrors(['name']);

    expect(Category::count())->toBe(0);
});

it('rejects a category under a parent that does not exist', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/categories', ['name' => 'Groceries', 'parent_id' => (string) Str::uuid()])
        ->assertSessionHasErrors(['parent_id']);

    expect(Category::count())->toBe(0);
});

it('renames a category', function (): void {
    $category = Category::factory()->create(['name' => 'Food']);

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$category->id}", ['name' => 'Groceries', 'parent_id' => null])
        ->assertRedirect(route('categories.index'));

    expect($category->refresh()->name)->toBe('Groceries');
});

it('moves a category under a different parent', function (): void {
    $food = Category::factory()->create();
    $transport = Category::factory()->create();
    $category = Category::factory()->create(['parent_id' => $food->id]);

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$category->id}", ['name' => $category->name, 'parent_id' => $transport->id])
        ->assertRedirect(route('categories.index'));

    expect($category->refresh()->parent_id)->toBe($transport->id);
});

it('moves a category to the root', function (): void {
    $food = Category::factory()->create();
    $category = Category::factory()->create(['parent_id' => $food->id]);

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$category->id}", ['name' => $category->name, 'parent_id' => null])
        ->assertRedirect(route('categories.index'));

    expect($category->refresh()->parent_id)->toBeNull();
});

it('refuses to move a category under itself', function (): void {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$category->id}", ['name' => $category->name, 'parent_id' => $category->id])
        ->assertSessionHasErrors(['parent_id']);

    expect($category->refresh()->parent_id)->toBeNull();
});

it('refuses to move a category under one of its own descendants, because a Category cannot be its own ancestor', function (): void {
    $food = Category::factory()->create();
    $groceries = Category::factory()->create(['parent_id' => $food->id]);
    $market = Category::factory()->create(['parent_id' => $groceries->id]);

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$food->id}", ['name' => $food->name, 'parent_id' => $market->id])
        ->assertSessionHasErrors(['parent_id']);

    expect($food->refresh()->parent_id)->toBeNull();
});

it('sets a budget on a category, storing the amount as integer minor units', function (): void {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$category->id}/budget", ['amount' => '120.50', 'period' => 'monthly'])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'budget_amount' => 12050,
        'budget_period' => 'monthly',
    ]);
});

it('changes a budget', function (): void {
    $category = Category::factory()->budgeted()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$category->id}/budget", ['amount' => '75', 'period' => 'weekly'])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'budget_amount' => 7500,
        'budget_period' => 'weekly',
    ]);
});

it('clears a budget, removing amount and period together', function (): void {
    $category = Category::factory()->budgeted()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/categories/{$category->id}/budget")
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'budget_amount' => null,
        'budget_period' => null,
    ]);
});

it('refuses a budget without both an amount and a period', function (array $payload, array $errors): void {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$category->id}/budget", $payload)
        ->assertSessionHasErrors($errors);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'budget_amount' => null,
        'budget_period' => null,
    ]);
})->with([
    'missing period' => [['amount' => '120.50'], ['period']],
    'missing amount' => [['period' => 'monthly'], ['amount']],
]);

it('refuses a budget amount that is not a plain decimal with at most two minor digits', function (string $amount): void {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$category->id}/budget", ['amount' => $amount, 'period' => 'monthly'])
        ->assertSessionHasErrors(['amount']);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'budget_amount' => null,
        'budget_period' => null,
    ]);
})->with([
    'three decimal places' => ['12.345'],
    'letters' => ['lots'],
    'negative' => ['-12.00'],
]);

it('refuses a budget period outside the enum', function (): void {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/categories/{$category->id}/budget", ['amount' => '120.50', 'period' => 'yearly'])
        ->assertSessionHasErrors(['period']);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'budget_amount' => null,
        'budget_period' => null,
    ]);
});

it('deletes a category without children', function (): void {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/categories/{$category->id}")
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('refuses to delete a category while it has children', function (): void {
    $food = Category::factory()->create();
    Category::factory()->create(['parent_id' => $food->id]);

    $this->actingAs(User::factory()->create())
        ->delete("/categories/{$food->id}")
        ->assertSessionHasErrors(['delete']);

    $this->assertDatabaseHas('categories', ['id' => $food->id]);
});
