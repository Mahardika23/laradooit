<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('updates the password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/password');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

it('requires the current password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect('/settings/password');
});
