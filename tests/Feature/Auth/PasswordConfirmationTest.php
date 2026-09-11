<?php

use App\Models\User;

it('renders the confirm password screen', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/confirm-password')
        ->assertOk();
});

it('confirms the password', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/confirm-password', ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();
});

it('refuses an invalid password', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/confirm-password', ['password' => 'wrong-password'])
        ->assertSessionHasErrors();
});
