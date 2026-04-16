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
        //
    }
}
