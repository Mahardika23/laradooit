<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates the single user from options', function (): void {
    $this->artisan('laradooit:install', [
        '--name' => 'Owner',
        '--email' => 'owner@example.com',
        '--password' => 'correct-horse',
    ])->assertSuccessful();

    $user = User::query()->sole();

    expect($user->name)->toBe('Owner')
        ->and($user->email)->toBe('owner@example.com')
        ->and(Hash::check('correct-horse', $user->password))->toBeTrue();
});

it('prompts for anything not passed as an option', function (): void {
    $this->artisan('laradooit:install')
        ->expectsQuestion('What name should the account use?', 'Owner')
        ->expectsQuestion('What email address will you log in with?', 'owner@example.com')
        ->expectsQuestion('Choose a password, at least 8 characters', 'correct-horse')
        ->assertSuccessful();

    expect(User::query()->sole()->email)->toBe('owner@example.com');
});

it('refuses to create a second user', function (): void {
    User::factory()->create();

    $this->artisan('laradooit:install', [
        '--name' => 'Intruder',
        '--email' => 'intruder@example.com',
        '--password' => 'correct-horse',
    ])->assertFailed();

    expect(User::query()->count())->toBe(1);
});

it('rejects a password shorter than eight characters', function (): void {
    $this->artisan('laradooit:install', [
        '--name' => 'Owner',
        '--email' => 'owner@example.com',
        '--password' => 'short',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('rejects an invalid email address', function (): void {
    $this->artisan('laradooit:install', [
        '--name' => 'Owner',
        '--email' => 'not-an-email',
        '--password' => 'correct-horse',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('creates a user who can then log in', function (): void {
    $this->artisan('laradooit:install', [
        '--name' => 'Owner',
        '--email' => 'owner@example.com',
        '--password' => 'correct-horse',
    ])->assertSuccessful();

    $this->post('/login', [
        'email' => 'owner@example.com',
        'password' => 'correct-horse',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
