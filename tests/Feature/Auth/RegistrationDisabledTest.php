<?php

use Illuminate\Support\Facades\Route;

it('has no registration routes', function (): void {
    $paths = collect(Route::getRoutes())->map(fn ($route): string => $route->uri());

    expect($paths)->not->toContain('register');
});

it('does not answer the registration urls', function (string $method): void {
    $this->call($method, '/register')->assertNotFound();
})->with(['GET', 'POST']);

it('ships no registration page', function (): void {
    expect(resource_path('js/pages/auth/register.tsx'))->not->toBeFile();
});
