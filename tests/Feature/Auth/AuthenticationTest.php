<?php

use App\Models\User;

it('renders the login screen', function (): void {
    $this->get('/login')->assertOk();
});

it('authenticates a user from the login screen', function (): void {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid password', function (): void {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

it('logs a user out', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
