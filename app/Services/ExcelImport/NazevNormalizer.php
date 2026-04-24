<?php

namespace App\Services\ExcelImport;

use Illuminate\Support\Str;

/**
 * Normalizuje název akce pro fuzzy matching mezi Excely a DB.
 * Odstraňuje rok, ročník, interpunkci — vrací slug.
 */
class NazevNormalizer
{
    public static function normalizuj(string $nazev): string
    {
        // Odstranit samostatný rok 2022–2025 (nechceme ať rozlišuje ročníky)
        $nazev = preg_replace('/\b(19|20)\d{2}\b/', ' ', $nazev);

        // Odstranit "XX. ročník" / "XXX ročníku"
        $nazev = preg_replace('/\b\d+\.?\s*(ročník|ročníku)\b/iu', ' ', $nazev);

        // Odstranit ordinaly typu "15. ", "20tý"
        $nazev = preg_replace('/\b\d+\.\s/u', ' ', $nazev);
        $nazev = preg_replace('/\b\d+[tn]ý\b/u', ' ', $nazev);

        return trim(Str::slug($nazev), '-');
    }
}
