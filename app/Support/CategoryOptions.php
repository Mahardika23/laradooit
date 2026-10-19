<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * The flat, tree-ordered Category list every Category picker draws from,
 * each option carrying its depth so the picker can indent. One fetch,
 * walked iteratively rather than queried per level.
 */
class CategoryOptions
{
    /**
     * @return list<array{id: string, name: string, depth: int}>
     */
    public static function inTreeOrder(): array
    {
        return self::flatten(Category::orderBy('name')->get());
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  list<string>  $path
     * @return list<array{id: string, name: string, depth: int}>
     */
    private static function flatten(Collection $categories, ?string $parentId = null, int $depth = 0, array $path = []): array
    {
        // A cycle cannot be written through the app; the path guard keeps
        // even hand-edited data from recursing forever, the same scenario
        // Category::ancestors guards.
        return $categories->where('parent_id', $parentId)
            ->reject(fn (Category $category): bool => in_array($category->id, $path, true))
            ->flatMap(fn (Category $category): array => array_merge(
                [['id' => $category->id, 'name' => $category->name, 'depth' => $depth]],
                self::flatten($categories, $category->id, $depth + 1, [...$path, $category->id]),
            ))
            ->values()
            ->all();
    }
}
