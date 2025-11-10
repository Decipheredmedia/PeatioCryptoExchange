<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth:web'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::get('/markets', function () {
        return view('markets');
    })->name('markets');
    
    Route::get('/wallets', function () {
        return view('wallets');
    })->name('wallets');
    
    Route::get('/history', function () {
        return view('history');
    })->name('history');
});

// Admin routes
Route::prefix('admin')->middleware(['auth:web', 'role:admin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

require __DIR__.'/auth.php';
