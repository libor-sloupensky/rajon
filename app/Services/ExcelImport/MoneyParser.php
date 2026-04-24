<?php

namespace App\Services\ExcelImport;

/**
 * Vytáhne částku v CZK z volného textu.
 *   "3000bez dph"          → 3000
 *   "tržba 2024 cca 40 000" → 40000
 *   "1500,- kč"            → 1500
 *   "2 000 Kč / den"       → 2000
 *   "tisíce"               → null
 *   "NFC 20%"              → null (procento, nejspíš sazba)
 */
class MoneyParser
{
    public static function parse(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_float($value) && $value == (int) $value)) {
            $n = (int) $value;
            return $n >= 0 && $n < 10_000_000 ? $n : null;
        }

        $text = (string) $value;

        // Extrakce největšího čísla v textu (s oddělovači mezer/teček)
        $candidates = [];
        if (preg_match_all('/(\d{1,3}(?:[ \.\xc2\xa0]\d{3})+|\d+)/u', $text, $mm)) {
            foreach ($mm[1] as $raw) {
                $n = (int) preg_replace('/[^\d]/', '', $raw);
                // Ignorovat roky
                if ($n >= 1990 && $n <= 2030) continue;
                // Ignorovat %
                if (preg_match('/\b' . preg_quote($raw, '/') . '\s*%/u', $text)) continue;
                // Ignorovat telefonní čísla (obvykle 9 cifer) a PSČ
                if ($n > 1_000_000) continue;
                $candidates[] = $n;
            }
        }

        if (empty($candidates)) return null;
        return max($candidates);
    }
}
