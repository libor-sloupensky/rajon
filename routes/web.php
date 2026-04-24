<?php

use App\Http\Controllers\AkceController;
use App\Http\Controllers\Admin\AkceController as AdminAkceController;
use App\Http\Controllers\Admin\ScrapingController;
use App\Http\Controllers\Admin\UzivateleController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\RegistraceController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Kořen — redirect podle stavu přihlášení
Route::get('/', fn () => Auth::check() ? redirect('/dashboard') : redirect('/login'));

// Google OAuth — jediné veřejné auth endpointy (kromě login/registrace)
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

// Registrace pouze na pozvánku
Route::get('/registrace', [RegistraceController::class, 'zobrazit'])->name('registrace');
Route::post('/registrace', [RegistraceController::class, 'registrovat'])->name('registrace.store');

// Celý web pouze pro přihlášené s ověřeným e-mailem
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Katalog akcí
    Route::get('/akce', [AkceController::class, 'index'])->name('akce.index');
    Route::get('/akce/{akce:slug}', [AkceController::class, 'show'])->name('akce.show');
    Route::get('/mapa', [AkceController::class, 'mapa'])->name('akce.mapa');
    Route::post('/akce/{akce}/rezervovat', [AkceController::class, 'rezervovat'])->name('akce.rezervovat');

    // API pro mapu
    Route::get('/api/akce-mapa', [AkceController::class, 'mapaJson'])->name('api.akce.mapa');
});

// Admin (je_admin middleware)
Route::middleware(['auth', 'verified', \App\Http\Middleware\JeAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/akce', [AdminAkceController::class, 'index'])->name('akce.index');
    Route::get('/akce/create', [AdminAkceController::class, 'create'])->name('akce.create');
    Route::post('/akce', [AdminAkceController::class, 'store'])->name('akce.store');
    Route::get('/akce/{akce}/edit', [AdminAkceController::class, 'edit'])->name('akce.edit');
    Route::put('/akce/{akce}', [AdminAkceController::class, 'update'])->name('akce.update');
    Route::post('/akce/{akce}/odemknout-pole', [AdminAkceController::class, 'odemknoutPole'])->name('akce.odemknout-pole');
    Route::delete('/akce/{akce}', [AdminAkceController::class, 'destroy'])->name('akce.destroy');

    // Uživatelé + pozvánky
    Route::get('/uzivatele', [UzivateleController::class, 'index'])->name('uzivatele');
    Route::post('/uzivatele/pozvat', [UzivateleController::class, 'pozvat'])->name('uzivatele.pozvat');
    Route::post('/uzivatele/pozvanky/{pozvanka}/resend', [UzivateleController::class, 'resendPozvanku'])->name('uzivatele.pozvanka.resend');
    Route::delete('/uzivatele/pozvanky/{pozvanka}', [UzivateleController::class, 'zrusitPozvanku'])->name('uzivatele.pozvanka.zrusit');

    // Scraping — zdroje a běhy
    Route::get('/scraping', [ScrapingController::class, 'index'])->name('scraping.index');
    Route::get('/scraping/new', [ScrapingController::class, 'create'])->name('scraping.create');
    Route::post('/scraping/analyzovat', [ScrapingController::class, 'analyzovat'])->name('scraping.analyzovat');
    Route::post('/scraping', [ScrapingController::class, 'store'])->name('scraping.store');
    Route::get('/scraping/{zdroj}/edit', [ScrapingController::class, 'edit'])->name('scraping.edit');
    Route::put('/scraping/{zdroj}', [ScrapingController::class, 'update'])->name('scraping.update');
    Route::post('/scraping/{zdroj}/spustit', [ScrapingController::class, 'spustit'])->name('scraping.spustit');
    Route::get('/scraping/log/{log}', [ScrapingController::class, 'log'])->name('scraping.log');
});
