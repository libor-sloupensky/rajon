<?php

use App\Http\Controllers\AkceController;
use App\Http\Controllers\Admin\ErrorLogController;
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

    // Katalog + správa akcí (sjednoceno — každý přihlášený může editovat)
    Route::get('/akce', [AkceController::class, 'index'])->name('akce.index');
    Route::get('/akce/nova', [AkceController::class, 'create'])->name('akce.create');
    Route::post('/akce', [AkceController::class, 'store'])->name('akce.store');
    Route::get('/akce/{akce:slug}', [AkceController::class, 'show'])->name('akce.show');
    Route::get('/akce/{akce}/upravit', [AkceController::class, 'edit'])->name('akce.edit');
    Route::put('/akce/{akce}', [AkceController::class, 'update'])->name('akce.update');
    Route::post('/akce/{akce}/odemknout-pole', [AkceController::class, 'odemknoutPole'])->name('akce.odemknout-pole');
    Route::delete('/akce/{akce}', [AkceController::class, 'destroy'])->name('akce.destroy');
    Route::post('/akce/{akce}/rezervovat', [AkceController::class, 'rezervovat'])->name('akce.rezervovat');

    Route::get('/mapa', [AkceController::class, 'mapa'])->name('akce.mapa');

    // API pro mapu
    Route::get('/api/akce-mapa', [AkceController::class, 'mapaJson'])->name('api.akce.mapa');
});

// Admin (je_admin middleware)
Route::middleware(['auth', 'verified', \App\Http\Middleware\JeAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    // Uživatelé + pozvánky
    Route::get('/uzivatele', [UzivateleController::class, 'index'])->name('uzivatele');
    Route::post('/uzivatele/pozvat', [UzivateleController::class, 'pozvat'])->name('uzivatele.pozvat');
    Route::put('/uzivatele/{uzivatel}/role', [UzivateleController::class, 'zmenitRoli'])->name('uzivatele.role');
    Route::delete('/uzivatele/{uzivatel}', [UzivateleController::class, 'destroy'])->name('uzivatele.destroy');
    Route::post('/uzivatele/pozvanky/{pozvanka}/resend', [UzivateleController::class, 'resendPozvanku'])->name('uzivatele.pozvanka.resend');
    Route::delete('/uzivatele/pozvanky/{pozvanka}', [UzivateleController::class, 'zrusitPozvanku'])->name('uzivatele.pozvanka.zrusit');

    // Error logy
    Route::get('/error-logy', [ErrorLogController::class, 'index'])->name('error-logy.index');
    Route::get('/error-logy/{soubor}', [ErrorLogController::class, 'show'])->name('error-logy.show');
    Route::get('/error-logy/{soubor}/raw', [ErrorLogController::class, 'raw'])->name('error-logy.raw');
    Route::get('/error-logy/{soubor}/download', [ErrorLogController::class, 'download'])->name('error-logy.download');
    Route::delete('/error-logy/{soubor}', [ErrorLogController::class, 'destroy'])->name('error-logy.destroy');

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
