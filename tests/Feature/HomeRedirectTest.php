<?php

use App\Models\User;

it('sends a guest at the root to the login page', function (): void {
    $this->get('/')->assertRedirect(route('login'));
});

it('sends an authenticated user at the root to the dashboard', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
