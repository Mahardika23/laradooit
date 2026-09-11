<?php

use App\Models\User;

it('redirects guests to the login page', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('shows the dashboard to an authenticated user', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk();
});
