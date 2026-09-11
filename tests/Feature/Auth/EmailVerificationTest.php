<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

it('renders the email verification screen', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/verify-email')
        ->assertOk();
});

it('verifies the email address', function (): void {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1((string) $user->email),
    ]);

    $this->actingAs($user)->get($url)
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

    Event::assertDispatched(Verified::class);
    expect($user->fresh()?->hasVerifiedEmail())->toBeTrue();
});

it('leaves the email unverified when the hash is wrong', function (): void {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('wrong-email'),
    ]);

    $this->actingAs($user)->get($url);

    expect($user->fresh()?->hasVerifiedEmail())->toBeFalse();
});
