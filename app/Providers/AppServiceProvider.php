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
        // Na serveru: public je v /_sub/rajon/, app je v /rajon/
        // Detekce serverové struktury — symlink ../../_sub/rajon jako public path
        $serverPublicPath = dirname(base_path(), 2) . '/_sub/rajon';
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
