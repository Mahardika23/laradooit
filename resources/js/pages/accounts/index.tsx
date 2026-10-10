import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type Account, type AccountTypeOption, type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { FormEventHandler, ReactNode, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accounts',
        href: '/accounts',
    },
];

function AccountRow({ account, muted = false, children }: { account: Account; muted?: boolean; children: ReactNode }) {
    return (
        <li className={cn('flex items-center justify-between gap-4 p-4', muted && 'text-muted-foreground')}>
            <div className="min-w-0">
                <p className="truncate text-sm font-medium">{account.name}</p>
                <p className={cn('truncate text-sm', !muted && 'text-muted-foreground')}>{account.institution}</p>
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <Badge variant={muted ? 'outline' : 'secondary'}>{account.typeLabel}</Badge>
                {children}
            </div>
        </li>
    );
}

export default function Accounts({
    activeAccounts,
    archivedAccounts,
    accountTypes,
}: {
    activeAccounts: Account[];
    archivedAccounts: Account[];
    accountTypes: AccountTypeOption[];
}) {
    const createForm = useForm({ name: '', type: '', institution: '' });
    const renameForm = useForm({ name: '' });
    const [renamingId, setRenamingId] = useState<string | null>(null);

    const submitCreate: FormEventHandler = (e) => {
        e.preventDefault();

        createForm.post(route('accounts.store'), {
            onSuccess: () => createForm.reset(),
            preserveScroll: true,
        });
    };

    const startRename = (account: Account) => {
        setRenamingId(account.id);
        renameForm.setData('name', account.name);
        renameForm.clearErrors();
    };

    const submitRename: FormEventHandler = (e) => {
        e.preventDefault();

        if (renamingId === null) {
            return;
        }

        renameForm.patch(route('accounts.update', renamingId), {
            onSuccess: () => setRenamingId(null),
            preserveScroll: true,
        });
    };

    const archive = (account: Account) => {
        router.patch(route('accounts.archive', account.id), {}, { preserveScroll: true });
    };

    const unarchive = (account: Account) => {
        router.patch(route('accounts.unarchive', account.id), {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Accounts" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-xl p-4">
                <section className="max-w-xl space-y-6">
                    <HeadingSmall
                        title="Add an account"
                        description="A place money sits or moves through — a bank account, an e-wallet, or cash in hand."
                    />

                    <form onSubmit={submitCreate} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>

                            <Input
                                id="name"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData('name', e.target.value)}
                                placeholder="Everyday spending"
                            />

                            <InputError message={createForm.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="type">Type</Label>

                            <Select value={createForm.data.type} onValueChange={(value) => createForm.setData('type', value)}>
                                <SelectTrigger id="type">
                                    <SelectValue placeholder="Choose a type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {accountTypes.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={createForm.errors.type} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="institution">Institution</Label>

                            <Input
                                id="institution"
                                value={createForm.data.institution}
                                onChange={(e) => createForm.setData('institution', e.target.value)}
                                placeholder="Example Bank"
                            />

                            <InputError message={createForm.errors.institution} />
                        </div>

                        <Button disabled={createForm.processing}>Add account</Button>
                    </form>
                </section>

                <section className="max-w-xl space-y-4">
                    <HeadingSmall title="Your accounts" description="Rename an account, or archive one you no longer use." />

                    <ul className="divide-sidebar-border/70 border-sidebar-border/70 divide-y rounded-xl border">
                        {activeAccounts.length === 0 && <li className="text-muted-foreground p-4 text-sm">No accounts yet.</li>}

                        {activeAccounts.map((account) =>
                            renamingId === account.id ? (
                                <li key={account.id} className="p-4">
                                    <form onSubmit={submitRename} className="flex items-center gap-2">
                                        <div className="grid flex-1 gap-1">
                                            <Input
                                                value={renameForm.data.name}
                                                onChange={(e) => renameForm.setData('name', e.target.value)}
                                                aria-label="Account name"
                                                autoFocus
                                            />
                                            <InputError message={renameForm.errors.name} />
                                        </div>
                                        <Button size="sm" disabled={renameForm.processing}>
                                            Save
                                        </Button>
                                        <Button size="sm" variant="ghost" type="button" onClick={() => setRenamingId(null)}>
                                            Cancel
                                        </Button>
                                    </form>
                                </li>
                            ) : (
                                <AccountRow key={account.id} account={account}>
                                    <Button size="sm" variant="ghost" type="button" onClick={() => startRename(account)}>
                                        Rename
                                    </Button>
                                    <Button size="sm" variant="ghost" type="button" onClick={() => archive(account)}>
                                        Archive
                                    </Button>
                                </AccountRow>
                            ),
                        )}
                    </ul>

                    {archivedAccounts.length > 0 && (
                        <Collapsible>
                            <CollapsibleTrigger asChild>
                                <Button variant="ghost" size="sm" className="group gap-1 px-2">
                                    <ChevronRight className="h-4 w-4 transition-transform group-data-[state=open]:rotate-90" />
                                    Archived accounts ({archivedAccounts.length})
                                </Button>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <ul className="divide-sidebar-border/70 border-sidebar-border/70 mt-2 divide-y rounded-xl border">
                                    {archivedAccounts.map((account) => (
                                        <AccountRow key={account.id} account={account} muted>
                                            <Button size="sm" variant="ghost" type="button" onClick={() => unarchive(account)}>
                                                Unarchive
                                            </Button>
                                        </AccountRow>
                                    ))}
                                </ul>
                            </CollapsibleContent>
                        </Collapsible>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
