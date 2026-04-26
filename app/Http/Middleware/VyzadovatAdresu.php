<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Existující uživatelé bez vyplněné adresy budou přesměrováni na /doplnit-adresu
 * (pro výpočet vzdálenosti k akcím).
 */
class VyzadovatAdresu
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = Auth::user();
        if ($u && !$u->maAdresu()) {
            // Whitelisted routes — bez nich by user uvízl
            $povolene = ['doplnit-adresu', 'logout', 'auth/google', 'auth/google/callback'];
            $aktualniPath = trim($request->path(), '/');
            foreach ($povolene as $p) {
                if ($aktualniPath === $p || str_starts_with($aktualniPath, $p . '/')) {
                    return $next($request);
                }
            }
            return redirect('/doplnit-adresu');
        }
        return $next($request);
    }
}
