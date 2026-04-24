<?php

namespace App\Services\ExcelImport;

use Carbon\Carbon;
use DateTimeInterface;

/**
 * Parser termínů z Excelových buněk.
 *
 * Excel má obě varianty:
 *   - DateTime objekt:   2023-06-15 00:00:00
 *   - Textový rozsah:    "9-10.6.", "5.-9.7.", "24.-26.3 - prodej jen 25.3."
 *
 * Vrací tuple ['datum_od' => Y-m-d|null, 'datum_do' => Y-m-d|null]
 * Rok se dopočítá z $defaultRok kontextu souboru/sheetu, když ho hodnota nenese.
 */
class DateParser
{
    /**
     * @return array{datum_od: ?string, datum_do: ?string}
     */
    public static function parse(mixed $value, int $defaultRok): array
    {
        if ($value === null || $value === '') {
            return ['datum_od' => null, 'datum_do' => null];
        }

        // Excel DateTime
        if ($value instanceof DateTimeInterface) {
            $carbon = Carbon::instance($value);
            // Excel občas vrátí rok jiný než kontextový (např. 2022 v souboru 2023)
            // Upravit pouze pokud rok z buňky je staršího než default
            if ($carbon->year < $defaultRok) {
                $carbon->year($defaultRok);
            }
            $d = $carbon->format('Y-m-d');
            return ['datum_od' => $d, 'datum_do' => $d];
        }

        // Numerický Excel datetime (serial)
        if (is_numeric($value) && $value > 30000 && $value < 60000) {
            try {
                $carbon = Carbon::createFromTimestamp(($value - 25569) * 86400);
                if ($carbon->year < $defaultRok) {
                    $carbon->year($defaultRok);
                }
                $d = $carbon->format('Y-m-d');
                return ['datum_od' => $d, 'datum_do' => $d];
            } catch (\Throwable) {
                // pokračovat jako text
            }
        }

        $text = (string) $value;
        return self::parseText($text, $defaultRok);
    }

    /**
     * Parsuje textové termíny:
     *   "9-10.6."              → 2023-06-09 … 2023-06-10
     *   "5.-9.7."              → 2023-07-05 … 2023-07-09
     *   "26.11.-23.12"         → 2023-11-26 … 2023-12-23
     *   "1.-2.7."              → 2023-07-01 … 2023-07-02
     *   "10.-12.3.2024"        → 2024-03-10 … 2024-03-12
     *   "04.-06.10.2025"       → 2025-10-04 … 2025-10-06
     *   "1.5.10-18h"           → 2023-05-01 (první datum)
     *
     * @return array{datum_od: ?string, datum_do: ?string}
     */
    public static function parseText(string $text, int $defaultRok): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['datum_od' => null, 'datum_do' => null];
        }

        // Vzorec A: "DD.-DD.MM.YYYY"    nebo  "DD.-DD.MM."
        //           "DD-DD.MM.YYYY"
        // např: "24.-26.3.2024", "17.-19. 5. 2024"
        if (preg_match('/^\s*(\d{1,2})\.?\s*[-–]\s*(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})?/u', $text, $m)) {
            $rok = isset($m[4]) && $m[4] !== '' ? (int) $m[4] : $defaultRok;
            return self::sestav($rok, (int) $m[3], (int) $m[1], (int) $m[3], (int) $m[2]);
        }

        // Vzorec B: "DD.MM.-DD.MM.YYYY"  nebo  "DD.MM.-DD.MM."
        // např: "26.11.-23.12.2023", "30.11-3.12"
        if (preg_match('/^\s*(\d{1,2})\.\s*(\d{1,2})\.?\s*[-–]\s*(\d{1,2})\.\s*(\d{1,2})\.?\s*(\d{4})?/u', $text, $m)) {
            $rok = isset($m[5]) && $m[5] !== '' ? (int) $m[5] : $defaultRok;
            return self::sestav($rok, (int) $m[2], (int) $m[1], (int) $m[4], (int) $m[3]);
        }

        // Vzorec C: "DD.MM.YYYY" — jedno datum v textu
        if (preg_match('/^\s*(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})?/u', $text, $m)) {
            $rok = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : $defaultRok;
            return self::sestav($rok, (int) $m[2], (int) $m[1], (int) $m[2], (int) $m[1]);
        }

        // Vzorec D: "YYYY-MM-DD HH:MM:SS" (textová datetime)
        if (preg_match('/^\s*(\d{4})-(\d{1,2})-(\d{1,2})/', $text, $m)) {
            $rok = (int) $m[1];
            if ($rok < $defaultRok) $rok = $defaultRok;
            return self::sestav($rok, (int) $m[2], (int) $m[3], (int) $m[2], (int) $m[3]);
        }

        return ['datum_od' => null, 'datum_do' => null];
    }

    /**
     * @return array{datum_od: ?string, datum_do: ?string}
     */
    private static function sestav(int $rok, int $mOd, int $dOd, int $mDo, int $dDo): array
    {
        try {
            $od = Carbon::create($rok, $mOd, $dOd);
            $do = Carbon::create($rok, $mDo, $dDo);
            if ($do->lt($od)) {
                // rozsah přelézá rok (např. "26.11.-23.12")
                $do->year($rok);
            }
            return [
                'datum_od' => $od->format('Y-m-d'),
                'datum_do' => $do->format('Y-m-d'),
            ];
        } catch (\Throwable) {
            return ['datum_od' => null, 'datum_do' => null];
        }
    }
}
