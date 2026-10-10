<?php

use App\Models\Account;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the login page', function (): void {
    $this->get('/accounts')->assertRedirect('/login');
});

it('lists active accounts and keeps archived ones in a separate section', function (): void {
    $active = Account::factory()->create(['name' => 'Everyday']);
    $archived = Account::factory()->archived()->create(['name' => 'Old wallet']);

    $this->actingAs(User::factory()->create())
        ->get('/accounts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('accounts/index')
            ->has('activeAccounts', 1)
            ->where('activeAccounts.0.id', $active->id)
            ->has('archivedAccounts', 1)
            ->where('archivedAccounts.0.id', $archived->id)
        );
});

it('offers every account type for the create form', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/accounts')
        ->assertInertia(fn (Assert $page) => $page
            ->component('accounts/index')
            ->where('accountTypes', [
                ['value' => 'bank', 'label' => 'Bank account'],
                ['value' => 'e_wallet', 'label' => 'E-wallet'],
                ['value' => 'cash', 'label' => 'Cash'],
            ])
        );
});

it('creates an account with a name, type, and institution', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/accounts', [
            'name' => 'Everyday',
            'type' => 'bank',
            'institution' => 'Example Bank',
        ])
        ->assertRedirect(route('accounts.index'));

    $this->assertDatabaseHas('accounts', [
        'name' => 'Everyday',
        'type' => 'bank',
        'institution' => 'Example Bank',
        'archived_at' => null,
    ]);
});

it('rejects an account with a missing name, type, or institution', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/accounts', [])
        ->assertSessionHasErrors(['name', 'type', 'institution']);

    expect(Account::count())->toBe(0);
});

it('rejects an account type outside the enum', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/accounts', [
            'name' => 'Everyday',
            'type' => 'underwater',
            'institution' => 'Example Bank',
        ])
        ->assertSessionHasErrors(['type']);

    expect(Account::count())->toBe(0);
});

it('renames an account', function (): void {
    $account = Account::factory()->create(['name' => 'Everyday']);

    $this->actingAs(User::factory()->create())
        ->patch("/accounts/{$account->id}", ['name' => 'Daily'])
        ->assertRedirect(route('accounts.index'));

    expect($account->refresh()->name)->toBe('Daily');
});

it('refuses to rename an account to nothing', function (): void {
    $account = Account::factory()->create(['name' => 'Everyday']);

    $this->actingAs(User::factory()->create())
        ->patch("/accounts/{$account->id}", ['name' => ''])
        ->assertSessionHasErrors(['name']);

    expect($account->refresh()->name)->toBe('Everyday');
});

it('archives an account, which keeps its history but drops it from the active list any picker draws from', function (): void {
    $account = Account::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch("/accounts/{$account->id}/archive")
        ->assertRedirect(route('accounts.index'));

    // The Account is kept, history and all; it only gains an archived_at.
    $this->assertDatabaseHas('accounts', ['id' => $account->id, 'name' => $account->name]);
    expect($account->refresh()->archived_at)->not->toBeNull();

    $this->actingAs($user)
        ->get('/accounts')
        ->assertInertia(fn (Assert $page) => $page
            ->has('activeAccounts', 0)
            ->has('archivedAccounts', 1)
            ->where('archivedAccounts.0.id', $account->id)
        );
});

it('unarchives an account back onto the active list', function (): void {
    $account = Account::factory()->archived()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch("/accounts/{$account->id}/unarchive")
        ->assertRedirect(route('accounts.index'));

    expect($account->refresh()->archived_at)->toBeNull();

    $this->actingAs($user)
        ->get('/accounts')
        ->assertInertia(fn (Assert $page) => $page
            ->has('activeAccounts', 1)
            ->where('activeAccounts.0.id', $account->id)
            ->has('archivedAccounts', 0)
        );
});
