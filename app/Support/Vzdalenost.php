<?php

namespace App\Support;

/**
 * Výpočet vzdálenosti a směru mezi GPS body (haversine + bearing).
 */
class Vzdalenost
{
    /** Vzdálenost dvou GPS bodů v km (haversine). */
    public static function km(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * asin(sqrt($a));
    }

    /** Bearing (azimut) z bodu 1 do bodu 2 ve stupních od severu (0–360). */
    public static function smerStupne(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dLng = deg2rad($lng2 - $lng1);
        $y = sin($dLng) * cos($phi2);
        $x = cos($phi1) * sin($phi2) - sin($phi1) * cos($phi2) * cos($dLng);
        $deg = (rad2deg(atan2($y, $x)) + 360);
        return ((int) round($deg)) % 360;
    }

    /**
     * Šipka kompasu pro daný bearing (0=N, 90=E, 180=S, 270=W).
     * Vrací 8-směrnou šipku: ↑ ↗ → ↘ ↓ ↙ ← ↖
     */
    public static function smerSipka(int $bearing): string
    {
        $arrows = ['↑', '↗', '→', '↘', '↓', '↙', '←', '↖'];
        $idx = ((int) round($bearing / 45)) % 8;
        return $arrows[$idx] ?? '·';
    }

    /** Hezky formátovaná vzdálenost (např. "12 km", "850 m"). */
    public static function formatuj(float $km): string
    {
        if ($km < 1) {
            return round($km * 1000) . ' m';
        }
        if ($km < 10) {
            return round($km, 1) . ' km';
        }
        return (int) round($km) . ' km';
    }
}
