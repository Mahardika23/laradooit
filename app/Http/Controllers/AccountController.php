<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('accounts/index', [
            'activeAccounts' => $this->presentList(Account::active()->orderBy('name')),
            'archivedAccounts' => $this->presentList(Account::archived()->orderBy('name')),
            'accountTypes' => array_map(
                fn (AccountType $type): array => ['value' => $type->value, 'label' => $type->label()],
                AccountType::cases(),
            ),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        Account::create($request->validated());

        return to_route('accounts.index');
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $account->update($request->validated());

        return to_route('accounts.index');
    }

    public function archive(Account $account): RedirectResponse
    {
        $account->archive();

        return to_route('accounts.index');
    }

    public function unarchive(Account $account): RedirectResponse
    {
        $account->unarchive();

        return to_route('accounts.index');
    }

    /**
     * @param  Builder<Account>  $query
     * @return list<array{id: string, name: string, type: string, typeLabel: string, institution: string}>
     */
    private function presentList(Builder $query): array
    {
        return $query->get()->map(fn (Account $account): array => $this->present($account))->all();
    }

    /**
     * @return array{id: string, name: string, type: string, typeLabel: string, institution: string}
     */
    private function present(Account $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->type->value,
            'typeLabel' => $account->type->label(),
            'institution' => $account->institution,
        ];
    }
}
