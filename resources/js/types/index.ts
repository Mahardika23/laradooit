import { LucideIcon } from 'lucide-react';

export interface Account {
    id: string;
    name: string;
    type: string;
    typeLabel: string;
    institution: string;
}

export interface AccountTypeOption {
    value: string;
    label: string;
}

export interface CategoryBudget {
    amount: string;
    amountDecimal: string;
    period: string;
    periodLabel: string;
}

export interface CategoryNode {
    id: string;
    name: string;
    parentId: string | null;
    budget: CategoryBudget | null;
    children: CategoryNode[];
}

export interface CategoryParentOption {
    id: string;
    name: string;
    depth: number;
}

export interface BudgetPeriodOption {
    value: string;
    label: string;
}

export interface TransactionRow {
    id: string;
    occurredOn: string;
    merchant: string;
    amount: string;
    direction: string;
    kind: string;
    kindLabel: string;
    category: string | null;
    account: string;
    internal: boolean;
}

export interface TransactionCycle {
    label: string;
    param: string;
    previous: string;
    next: string;
}

export interface TransactionTotals {
    spent: string;
    received: string;
}

export interface TransactionAccountOption {
    id: string;
    name: string;
}

export interface TransactionCategoryOption {
    id: string;
    name: string;
    depth: number;
}

export interface TransactionDirectionOption {
    value: string;
    label: string;
}

export interface TransactionKindOption {
    value: string;
    label: string;
}

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
