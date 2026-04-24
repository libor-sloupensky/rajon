<?php

namespace App\Services\ExcelImport;

/**
 * Parser textu v "Poznámky z minulých let" — obvykle ve tvaru:
 *   "2023: Prodej 55509 Kč, Nájem 20%"
 *   "2023: Prodej 6480 Kč, Nájem 1350 Kč"
 *   "tržba 2024 - cca 40 000,-"
 *   "prodej 2023: 31 000,-, prodej 2024: 40 000,-"
 *
 * Vrací seznam mikro-výkazů pro `akce_vykazy`.
 *
 * @phpstan-type MikroVykaz array{rok: int, trzba: ?int, najem: ?int}
 */
class PoznamkyParser
{
    /**
     * @return list<array{rok: int, trzba: ?int, najem: ?int}>
     */
    public static function vytahni(string $text): array
    {
        $result = [];
        $processed = [];

        // Vzor A: "YYYY: ..." — rozsekej text podle roku na úseky
        //   "2023: Prodej X, Nájem Y . 2024: Prodej Z"
        $parts = preg_split('/(?=\b(20\d{2})\s*:)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts) {
            foreach ($parts as $part) {
                if (!preg_match('/^(20\d{2})\s*:\s*(.+?)(?=\b20\d{2}\s*:|$)/us', $part, $m)) continue;
                $rok = (int) $m[1];
                if ($rok < 2020 || $rok > 2030) continue;
                if (in_array($rok, $processed, true)) continue;

                $fragment = $m[2];
                $trzba = self::vyhledej($fragment, ['Prodej', 'Tržba', 'tržba', 'prodej']);
                $najem = self::vyhledej($fragment, ['Nájem', 'nájem']);

                if ($trzba === null && $najem === null) continue;

                $result[] = ['rok' => $rok, 'trzba' => $trzba, 'najem' => $najem];
                $processed[] = $rok;
            }
        }

        // Vzor B: "tržba YYYY - cca X" / "tržba YYYY: X"
        if (preg_match_all('/tr[zž]ba\s*(\d{4})\s*[:\-–]\s*(?:cca\s*)?([^,\.\n]+)/iu', $text, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $rok = (int) $m[1];
                if ($rok < 2020 || $rok > 2030) continue;
                if (in_array($rok, $processed, true)) continue;
                $trzba = MoneyParser::parse($m[2]);
                if ($trzba === null) continue;
                $result[] = ['rok' => $rok, 'trzba' => $trzba, 'najem' => null];
                $processed[] = $rok;
            }
        }

        // Vzor C: "prodej YYYY: X" (když není chyceno vzor A — bez ":" po roku)
        if (preg_match_all('/prodej\s*(\d{4})\s*[:\-–]\s*([^,\.\n]+)/iu', $text, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $rok = (int) $m[1];
                if ($rok < 2020 || $rok > 2030) continue;
                if (in_array($rok, $processed, true)) continue;
                $trzba = MoneyParser::parse($m[2]);
                if ($trzba === null) continue;
                $result[] = ['rok' => $rok, 'trzba' => $trzba, 'najem' => null];
                $processed[] = $rok;
            }
        }

        return $result;
    }

    /**
     * V fragmentu hledej první klíčové slovo následované číslem.
     */
    private static function vyhledej(string $fragment, array $klicoveSlova): ?int
    {
        foreach ($klicoveSlova as $klic) {
            if (preg_match('/' . preg_quote($klic, '/') . '\s*([^,;]+?)(?:[,;\.]|\bKč\b|$)/iu', $fragment, $m)) {
                $n = MoneyParser::parse($m[1]);
                if ($n !== null) return $n;
            }
        }
        return null;
    }
}
