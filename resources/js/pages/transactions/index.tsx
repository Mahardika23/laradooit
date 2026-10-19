import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import {
    type BreadcrumbItem,
    type TransactionAccountOption,
    type TransactionCategoryOption,
    type TransactionCycle,
    type TransactionDirectionOption,
    type TransactionKindOption,
    type TransactionRow,
    type TransactionTotals,
} from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transactions',
        href: '/transactions',
    },
];

export default function Transactions({
    transactions,
    cycle,
    totals,
    accounts,
    categories,
    directions,
    kinds,
    today,
}: {
    transactions: TransactionRow[];
    cycle: TransactionCycle;
    totals: TransactionTotals;
    accounts: TransactionAccountOption[];
    categories: TransactionCategoryOption[];
    directions: TransactionDirectionOption[];
    kinds: TransactionKindOption[];
    today: string;
}) {
    const form = useForm({
        occurred_on: today,
        account_id: '',
        amount: '',
        direction: 'out',
        kind: 'expense',
        merchant: '',
        category_id: '',
    });

    // Batch entry is keyboard-driven: after a save the date stays put, every
    // other field resets, and focus lands back on the date input so the next
    // entry starts with typing, not with the mouse.
    const dateInputRef = useRef<HTMLInputElement>(null);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        form.post(route('transactions.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('account_id', 'amount', 'direction', 'kind', 'merchant', 'category_id');
                dateInputRef.current?.focus();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transactions" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-xl p-4">
                <section className="max-w-xl space-y-6">
                    <HeadingSmall
                        title="Add a manual entry"
                        description="A transaction you type in, born confirmed — cash spending and anything else the accounts never see."
                    />

                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="occurred_on">Date</Label>

                            <Input
                                id="occurred_on"
                                ref={dateInputRef}
                                type="date"
                                value={form.data.occurred_on}
                                onChange={(e) => form.setData('occurred_on', e.target.value)}
                            />

                            <InputError message={form.errors.occurred_on} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="account_id">Account</Label>

                            <Select value={form.data.account_id} onValueChange={(value) => form.setData('account_id', value)}>
                                <SelectTrigger id="account_id">
                                    <SelectValue placeholder="Choose an account" />
                                </SelectTrigger>
                                <SelectContent>
                                    {accounts.map((account) => (
                                        <SelectItem key={account.id} value={account.id}>
                                            {account.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={form.errors.account_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="amount">Amount</Label>

                            <Input
                                id="amount"
                                value={form.data.amount}
                                onChange={(e) => form.setData('amount', e.target.value)}
                                placeholder="45.50"
                                inputMode="decimal"
                            />

                            <InputError message={form.errors.amount} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="direction">Direction</Label>

                            <Select value={form.data.direction} onValueChange={(value) => form.setData('direction', value)}>
                                <SelectTrigger id="direction">
                                    <SelectValue placeholder="Which way did the money move?" />
                                </SelectTrigger>
                                <SelectContent>
                                    {directions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={form.errors.direction} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="kind">Kind</Label>

                            <Select value={form.data.kind} onValueChange={(value) => form.setData('kind', value)}>
                                <SelectTrigger id="kind">
                                    <SelectValue placeholder="Choose a kind" />
                                </SelectTrigger>
                                <SelectContent>
                                    {kinds.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={form.errors.kind} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="merchant">Merchant</Label>

                            <Input
                                id="merchant"
                                value={form.data.merchant}
                                onChange={(e) => form.setData('merchant', e.target.value)}
                                placeholder="Corner bakery"
                            />

                            <InputError message={form.errors.merchant} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="category_id">Category</Label>

                            <Select value={form.data.category_id} onValueChange={(value) => form.setData('category_id', value)}>
                                <SelectTrigger id="category_id">
                                    <SelectValue placeholder="Choose a category" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={category.id}>
                                            {'— '.repeat(category.depth)}
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={form.errors.category_id} />
                        </div>

                        <Button disabled={form.processing}>Add entry</Button>
                    </form>
                </section>

                <section className="space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="icon" asChild>
                                <Link href={route('transactions.index', { cycle: cycle.previous })} aria-label="Previous cycle">
                                    <ChevronLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <h2 className="min-w-36 text-center text-sm font-medium">{cycle.label}</h2>
                            <Button variant="outline" size="icon" asChild>
                                <Link href={route('transactions.index', { cycle: cycle.next })} aria-label="Next cycle">
                                    <ChevronRight className="h-4 w-4" />
                                </Link>
                            </Button>
                        </div>

                        <dl className="flex items-center gap-6">
                            <div className="flex items-baseline gap-2">
                                <dt className="text-muted-foreground text-sm">Spent</dt>
                                <dd className="text-sm font-medium tabular-nums">{totals.spent}</dd>
                            </div>
                            <div className="flex items-baseline gap-2">
                                <dt className="text-muted-foreground text-sm">Received</dt>
                                <dd className="text-sm font-medium tabular-nums">{totals.received}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="border-sidebar-border/70 overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-sidebar-border/70 text-muted-foreground border-b text-left">
                                    <th className="p-3 font-medium">Date</th>
                                    <th className="p-3 font-medium">Merchant</th>
                                    <th className="p-3 font-medium">Category</th>
                                    <th className="p-3 font-medium">Account</th>
                                    <th className="p-3 font-medium">Kind</th>
                                    <th className="p-3 text-right font-medium">Amount</th>
                                </tr>
                            </thead>
                            <tbody className="divide-sidebar-border/70 divide-y">
                                {transactions.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="text-muted-foreground p-4">
                                            No confirmed transactions in this cycle.
                                        </td>
                                    </tr>
                                )}

                                {transactions.map((transaction) => (
                                    <tr key={transaction.id}>
                                        <td className="p-3 whitespace-nowrap tabular-nums">{transaction.occurredOn}</td>
                                        <td className="max-w-48 truncate p-3 font-medium">{transaction.merchant}</td>
                                        <td className="max-w-40 truncate p-3">{transaction.category}</td>
                                        <td className="max-w-40 truncate p-3">{transaction.account}</td>
                                        <td className="p-3">
                                            <Badge variant={transaction.internal ? 'outline' : 'secondary'}>
                                                {transaction.internal ? 'Internal' : transaction.kindLabel}
                                            </Badge>
                                        </td>
                                        <td
                                            className={cn(
                                                'p-3 text-right whitespace-nowrap tabular-nums',
                                                transaction.direction === 'in' && 'text-green-600 dark:text-green-400',
                                            )}
                                        >
                                            {transaction.direction === 'in' ? '+' : '−'}
                                            {transaction.amount}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
