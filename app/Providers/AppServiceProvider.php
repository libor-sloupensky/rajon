<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Na serveru: public je v /tuptudu.cz/_sub/rajon/, app je v /tuptudu.cz/rajon/
        $serverPublicPath = dirname(base_path()) . '/_sub/rajon';
        if (is_dir($serverPublicPath)) {
            $this->app->usePublicPath($serverPublicPath);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Login tracking — zaznamenat čas posledního přihlášení uživatele
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function ($event) {
                if ($event->user && method_exists($event->user, 'forceFill')) {
                    $event->user->forceFill(['posledni_prihlaseni' => now()])->saveQuietly();
                }
            }
        );
    }
}
