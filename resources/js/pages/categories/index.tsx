import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type BudgetPeriodOption, type CategoryNode, type CategoryParentOption } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '/categories',
    },
];

// Radix Select items cannot carry an empty value, so the root of the tree
// (a null parent) rides through the pickers under this sentinel.
const ROOT = 'root';

function descendantIds(node: CategoryNode): string[] {
    return node.children.flatMap((child) => [child.id, ...descendantIds(child)]);
}

function ParentSelect({
    id,
    value,
    options,
    onChange,
}: {
    id: string;
    value: string | null;
    options: CategoryParentOption[];
    onChange: (parentId: string | null) => void;
}) {
    return (
        <Select value={value ?? ROOT} onValueChange={(selected) => onChange(selected === ROOT ? null : selected)}>
            <SelectTrigger id={id}>
                <SelectValue placeholder="Choose a parent" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ROOT}>At the root</SelectItem>
                {options.map((option) => (
                    <SelectItem key={option.id} value={option.id}>
                        {'— '.repeat(option.depth)}
                        {option.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function CategoryItem({
    node,
    depth,
    parentOptions,
    budgetPeriods,
}: {
    node: CategoryNode;
    depth: number;
    parentOptions: CategoryParentOption[];
    budgetPeriods: BudgetPeriodOption[];
}) {
    const editForm = useForm({ name: node.name, parent_id: node.parentId as string | null });
    const budgetForm = useForm({ amount: node.budget?.amountDecimal ?? '', period: node.budget?.period ?? '' });
    const deleteForm = useForm<Record<string, never>>({});
    const [editing, setEditing] = useState(false);
    const [budgeting, setBudgeting] = useState(false);

    // A Category cannot be its own ancestor, so the move picker offers
    // neither the Category itself nor anything beneath it.
    const excludedIds = [node.id, ...descendantIds(node)];
    const moveOptions = parentOptions.filter((option) => !excludedIds.includes(option.id));

    const startEditing = () => {
        editForm.setData({ name: node.name, parent_id: node.parentId });
        editForm.clearErrors();
        setBudgeting(false);
        setEditing(true);
    };

    const submitEdit: FormEventHandler = (e) => {
        e.preventDefault();

        editForm.patch(route('categories.update', node.id), {
            onSuccess: () => setEditing(false),
            preserveScroll: true,
        });
    };

    const startBudgeting = () => {
        budgetForm.setData({ amount: node.budget?.amountDecimal ?? '', period: node.budget?.period ?? '' });
        budgetForm.clearErrors();
        setEditing(false);
        setBudgeting(true);
    };

    const submitBudget: FormEventHandler = (e) => {
        e.preventDefault();

        budgetForm.patch(route('categories.budget.set', node.id), {
            onSuccess: () => setBudgeting(false),
            preserveScroll: true,
        });
    };

    const clearBudget = () => {
        router.delete(route('categories.budget.clear', node.id), { preserveScroll: true });
    };

    const destroy = () => {
        deleteForm.delete(route('categories.destroy', node.id), { preserveScroll: true });
    };

    return (
        <li>
            {editing ? (
                <form onSubmit={submitEdit} className="space-y-3 p-4" style={{ paddingLeft: `${1 + depth * 1.5}rem` }}>
                    <div className="grid gap-2">
                        <Input
                            value={editForm.data.name}
                            onChange={(e) => editForm.setData('name', e.target.value)}
                            aria-label="Category name"
                            autoFocus
                        />
                        <InputError message={editForm.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <ParentSelect
                            id={`move-${node.id}`}
                            value={editForm.data.parent_id}
                            options={moveOptions}
                            onChange={(parentId) => editForm.setData('parent_id', parentId)}
                        />
                        <InputError message={editForm.errors.parent_id} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Button size="sm" disabled={editForm.processing}>
                            Save
                        </Button>
                        <Button size="sm" variant="ghost" type="button" onClick={() => setEditing(false)}>
                            Cancel
                        </Button>
                    </div>
                </form>
            ) : budgeting ? (
                <form onSubmit={submitBudget} className="space-y-3 p-4" style={{ paddingLeft: `${1 + depth * 1.5}rem` }}>
                    <div className="grid gap-2">
                        <Label htmlFor={`amount-${node.id}`}>Budget amount</Label>
                        <Input
                            id={`amount-${node.id}`}
                            value={budgetForm.data.amount}
                            onChange={(e) => budgetForm.setData('amount', e.target.value)}
                            placeholder="500.00"
                            inputMode="decimal"
                            autoFocus
                        />
                        <InputError message={budgetForm.errors.amount} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`period-${node.id}`}>Budget period</Label>
                        <Select value={budgetForm.data.period} onValueChange={(value) => budgetForm.setData('period', value)}>
                            <SelectTrigger id={`period-${node.id}`}>
                                <SelectValue placeholder="Choose a period" />
                            </SelectTrigger>
                            <SelectContent>
                                {budgetPeriods.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={budgetForm.errors.period} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Button size="sm" disabled={budgetForm.processing}>
                            Save budget
                        </Button>
                        {node.budget !== null && (
                            <Button size="sm" variant="ghost" type="button" onClick={clearBudget}>
                                Clear budget
                            </Button>
                        )}
                        <Button size="sm" variant="ghost" type="button" onClick={() => setBudgeting(false)}>
                            Cancel
                        </Button>
                    </div>
                </form>
            ) : (
                <div className="flex items-center justify-between gap-4 p-4" style={{ paddingLeft: `${1 + depth * 1.5}rem` }}>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-medium">{node.name}</p>
                        <InputError message={deleteForm.errors.delete} />
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {node.budget !== null && (
                            <Badge variant="secondary">
                                {node.budget.amount} · {node.budget.periodLabel}
                            </Badge>
                        )}
                        <Button size="sm" variant="ghost" type="button" onClick={startEditing}>
                            Edit
                        </Button>
                        <Button size="sm" variant="ghost" type="button" onClick={startBudgeting}>
                            Budget
                        </Button>
                        <Button
                            size="sm"
                            variant="ghost"
                            type="button"
                            onClick={destroy}
                            disabled={node.children.length > 0}
                            title={node.children.length > 0 ? 'Move or delete the Categories beneath this one first.' : undefined}
                        >
                            Delete
                        </Button>
                    </div>
                </div>
            )}

            {node.children.length > 0 && (
                <ul className="divide-sidebar-border/70 border-sidebar-border/70 divide-y border-t">
                    {node.children.map((child) => (
                        <CategoryItem key={child.id} node={child} depth={depth + 1} parentOptions={parentOptions} budgetPeriods={budgetPeriods} />
                    ))}
                </ul>
            )}
        </li>
    );
}

export default function Categories({
    categories,
    parentOptions,
    budgetPeriods,
}: {
    categories: CategoryNode[];
    parentOptions: CategoryParentOption[];
    budgetPeriods: BudgetPeriodOption[];
}) {
    const createForm = useForm({ name: '', parent_id: null as string | null });

    const submitCreate: FormEventHandler = (e) => {
        e.preventDefault();

        createForm.post(route('categories.store'), {
            onSuccess: () => createForm.reset(),
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categories" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-xl p-4">
                <section className="max-w-xl space-y-6">
                    <HeadingSmall
                        title="Add a category"
                        description="A bucket money is filed under, optionally nested beneath a parent so spending reads at more than one level."
                    />

                    <form onSubmit={submitCreate} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>

                            <Input
                                id="name"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData('name', e.target.value)}
                                placeholder="Groceries"
                            />

                            <InputError message={createForm.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="parent">Parent</Label>

                            <ParentSelect
                                id="parent"
                                value={createForm.data.parent_id}
                                options={parentOptions}
                                onChange={(parentId) => createForm.setData('parent_id', parentId)}
                            />

                            <InputError message={createForm.errors.parent_id} />
                        </div>

                        <Button disabled={createForm.processing}>Add category</Button>
                    </form>
                </section>

                <section className="max-w-xl space-y-4">
                    <HeadingSmall
                        title="Your categories"
                        description="Rename a category, move it, or set the Budget it is measured against for each Cycle."
                    />

                    <ul className="divide-sidebar-border/70 border-sidebar-border/70 divide-y rounded-xl border">
                        {categories.length === 0 && <li className="text-muted-foreground p-4 text-sm">No categories yet.</li>}

                        {categories.map((node) => (
                            <CategoryItem key={node.id} node={node} depth={0} parentOptions={parentOptions} budgetPeriods={budgetPeriods} />
                        ))}
                    </ul>
                </section>
            </div>
        </AppLayout>
    );
}
