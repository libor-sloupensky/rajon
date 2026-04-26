<?php

namespace App\Http\Middleware;

use App\Models\Navsteva;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sledování návštěv uživatele. Návštěva = série requestů s mezerou < 2h.
 *
 * - Najde poslední záznam pro uživatele
 * - Pokud konec > now() - 2h: prodlouží návštěvu (update konec na now())
 * - Jinak: vytvoří novou návštěvu
 *
 * Optimalizace: konec se aktualizuje jen 1× za 5 minut (méně DB writes).
 */
class TrackNavsteva
{
    /** Mezera, po níž se request počítá jako nová návštěva. */
    public const HRANICE_NOVE_NAVSTEVY_HOD = 2;

    /** Throttle update — min interval mezi updaty 'konec'. */
    public const THROTTLE_MINUT = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Track až po response (neblokuje renderování)
        $u = Auth::user();
        if ($u && !$request->ajax()) {
            try {
                $this->zaznamenej($u->id);
            } catch (\Throwable $e) {
                // Nesmí blokovat aplikaci kvůli tracking chybě
                \Illuminate\Support\Facades\Log::warning("TrackNavsteva error: {$e->getMessage()}");
            }
        }

        return $response;
    }

    protected function zaznamenej(int $uzivatelId): void
    {
        $now = now();
        $hraniceNavstevy = $now->copy()->subHours(self::HRANICE_NOVE_NAVSTEVY_HOD);

        $posledni = Navsteva::where('uzivatel_id', $uzivatelId)
            ->orderBy('zacatek', 'desc')
            ->first();

        if (!$posledni || $posledni->konec < $hraniceNavstevy) {
            Navsteva::create([
                'uzivatel_id' => $uzivatelId,
                'zacatek' => $now,
                'konec' => $now,
            ]);
            return;
        }

        // Throttle update — pokud konec byl před < THROTTLE_MINUT, neaktualizujeme
        $hraniceUpdate = $now->copy()->subMinutes(self::THROTTLE_MINUT);
        if ($posledni->konec < $hraniceUpdate) {
            $posledni->update(['konec' => $now]);
        }
    }
}
