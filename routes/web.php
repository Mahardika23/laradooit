<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
})->name('home');

Route::get('up', HealthController::class)->name('health');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::patch('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::patch('accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');
    Route::patch('accounts/{account}/unarchive', [AccountController::class, 'unarchive'])->name('accounts.unarchive');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
