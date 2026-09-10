<?php

use App\Models\User;

it('displays the profile page', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/settings/profile')
        ->assertOk();
});

it('updates the profile information', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => 'Owner',
            'email' => 'owner@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/profile');

    $user->refresh();

    expect($user->name)->toBe('Owner')
        ->and($user->email)->toBe('owner@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

it('keeps the verification status when the email is unchanged', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => 'Owner',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/profile');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('deletes the account', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

it('requires the correct password to delete the account', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/profile')
        ->delete('/settings/profile', ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password')
        ->assertRedirect('/settings/profile');

    expect($user->fresh())->not->toBeNull();
});
