<?php

use App\Http\Controllers\AkceController;
use App\Http\Controllers\Admin\AkceController as AdminAkceController;
use App\Http\Controllers\Admin\UzivateleController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Veřejné routy
Route::get('/', fn () => redirect('/akce'));
Route::get('/akce', [AkceController::class, 'index'])->name('akce.index');
Route::get('/akce/{akce:slug}', [AkceController::class, 'show'])->name('akce.show');
Route::get('/mapa', [AkceController::class, 'mapa'])->name('akce.mapa');

// API pro mapu
Route::get('/api/akce-mapa', [AkceController::class, 'mapaJson'])->name('api.akce.mapa');

// Google OAuth
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

// Přihlášený uživatel
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/akce/{akce}/rezervovat', [AkceController::class, 'rezervovat'])->name('akce.rezervovat');
});

// Admin (je_admin middleware)
Route::middleware(['auth', 'verified', \App\Http\Middleware\JeAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/akce', [AdminAkceController::class, 'index'])->name('akce.index');
    Route::get('/akce/create', [AdminAkceController::class, 'create'])->name('akce.create');
    Route::post('/akce', [AdminAkceController::class, 'store'])->name('akce.store');
    Route::get('/akce/{akce}/edit', [AdminAkceController::class, 'edit'])->name('akce.edit');
    Route::put('/akce/{akce}', [AdminAkceController::class, 'update'])->name('akce.update');
    Route::delete('/akce/{akce}', [AdminAkceController::class, 'destroy'])->name('akce.destroy');
    Route::get('/zdroje', [AdminAkceController::class, 'zdroje'])->name('zdroje');
    Route::get('/uzivatele', [UzivateleController::class, 'index'])->name('uzivatele');
});
