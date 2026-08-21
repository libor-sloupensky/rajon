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
        // Na Českém hostingu je rozložení standardní — public/ je podadresář
        // kořene aplikace (DocumentRoot subdomény je posunutý na /public),
        // takže se výchozí cesta Laravelu přepisovat nemusí. Dřív to bylo
        // potřeba kvůli Webglobe, kde public leželo v /tuptudu.cz/_sub/rajon/.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hosting blokuje odchozí SMTP a zakazuje proc_open, takže jediná cesta
        // ven je PHP mail() — viz App\Mail\Transport\PhpMailTransport
        \Illuminate\Support\Facades\Mail::extend(
            'php_mail',
            fn (array $config) => new \App\Mail\Transport\PhpMailTransport()
        );

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
