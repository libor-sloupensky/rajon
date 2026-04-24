<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\Pozvanka;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('auth.login'));

        // Registrace jen s platným tokenem pozvánky (i když feature je vypnutý,
        // view se používá pro náš vlastní /registrace endpoint)
        Fortify::registerView(function (Request $request) {
            $pozvanka = null;
            if ($request->filled('token')) {
                $pozvanka = Pozvanka::where('token', $request->token)
                    ->where('stav', 'cekajici')
                    ->first();
                if ($pozvanka && !$pozvanka->jePlatna()) {
                    $pozvanka = null;
                }
            }
            return view('auth.register', compact('pozvanka'));
        });

        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
