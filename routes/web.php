<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\TransactionController;
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

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::patch('categories/{category}/budget', [CategoryController::class, 'setBudget'])->name('categories.budget.set');
    Route::delete('categories/{category}/budget', [CategoryController::class, 'clearBudget'])->name('categories.budget.clear');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
