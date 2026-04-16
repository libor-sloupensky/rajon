<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutoLoginLocal
{
    public function handle(Request $request, Closure $next)
    {
        if (config('app.env') !== 'local') {
            return $next($request);
        }

        if (!Auth::check()) {
            $user = \App\Models\Uzivatel::first();
            if ($user) {
                Auth::login($user);
            }
        }

        return $next($request);
    }
}
