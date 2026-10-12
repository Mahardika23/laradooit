<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Domain\Money\Money;
use App\Enums\BudgetPeriod;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $name
 * @property string|null $parent_id
 * @property Money|null $budget_amount
 * @property BudgetPeriod|null $budget_period
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Category|null $parent
 * @property Collection<int, Category> $children
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable. The Budget columns are not
     * among them: a Budget is an amount together with its period, written
     * as a pair through setBudget and removed through clearBudget, so a
     * half-set Budget can never exist.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'parent_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_amount' => MoneyCast::class,
            'budget_period' => BudgetPeriod::class,
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * The chain of Categories above this one, closest first. A cycle cannot
     * be written through the app, so the walk terminates; the visited guard
     * keeps even hand-edited data from looping forever.
     *
     * @return Collection<int, Category>
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection;
        $visited = [$this->id];
        $current = $this->parent;

        while ($current !== null && ! in_array($current->id, $visited, true)) {
            $ancestors->push($current);
            $visited[] = $current->id;
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Whether this Category is the given one or sits somewhere beneath it.
     * The move form uses this to refuse making a Category its own ancestor.
     */
    public function isSelfOrDescendantOf(self $possibleAncestor): bool
    {
        return $this->id === $possibleAncestor->id
            || $this->ancestors()->contains('id', $possibleAncestor->id);
    }

    public function setBudget(Money $amount, BudgetPeriod $period): void
    {
        $this->forceFill([
            'budget_amount' => $amount,
            'budget_period' => $period,
        ])->save();
    }

    public function clearBudget(): void
    {
        $this->forceFill([
            'budget_amount' => null,
            'budget_period' => null,
        ])->save();
    }
}
