<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JeAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->jeAdmin()) {
            abort(403, 'Přístup pouze pro administrátory.');
        }

        return $next($request);
    }
}
