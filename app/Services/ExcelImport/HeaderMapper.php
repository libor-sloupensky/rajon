<?php

namespace App\Services\ExcelImport;

use Illuminate\Support\Str;

/**
 * Mapuje původní názvy sloupců v Excelech na normalizované klíče.
 *
 * Cílové klíče:
 *   nazev, datum, misto, kraj, organizator, mail, mobil, web,
 *   najem, trzba, navstevnost, rocnik, cas_konani, poznamka,
 *   poznamky_minulych_let
 *
 * Zpětně: klíče se v Excelech objevují s českou diakritikou, trailing
 * spaces, malými/velkými písmeny, zkrácenou diakritikou. Normalizujeme
 * přes Str::ascii + lower + trim.
 */
class HeaderMapper
{
    private const ALIASES = [
        // nazev
        'nazev' => 'nazev',
        'akce' => 'nazev',
        'nazev akce' => 'nazev',

        // datum (používá se pro parsování ve všech variantách)
        'termin' => 'datum',
        'datum' => 'datum',
        'datum konani' => 'datum',
        'cas konani' => 'cas_konani',

        // misto
        'misto' => 'misto',
        'mesto' => 'misto',
        'mesto konani' => 'misto',
        'kde se kona' => 'misto',
        'misto konani' => 'misto',

        // kraj
        'kraj' => 'kraj',

        // organizator
        'organizator' => 'organizator',
        'poradatel' => 'organizator',
        'kontakt' => 'organizator',

        // kontakty
        'mail' => 'mail',
        'email' => 'mail',
        'e-mail' => 'mail',
        'mobil' => 'mobil',
        'telefon' => 'mobil',
        'web' => 'web',
        'web/' => 'web',
        'odkaz na akci' => 'web',
        'odkaz' => 'web',

        // finance — nájem
        'cena najmu' => 'najem',
        'najem' => 'najem',
        'najem na akci' => 'najem',
        'najem na miste' => 'najem',

        // finance — tržba
        'obrat' => 'trzba',
        'obrat 2024' => 'trzba',
        'obrat 2023' => 'trzba',
        'prodano' => 'trzba',
        'trzba' => 'trzba',
        'prodej' => 'trzba',
        'trzba cca' => 'trzba',
        'ucast' => 'navstevnost', // sheet Prosinec má "Účast" jako návštěvnost
        'najem/prodej' => 'najem_prodej_mix', // speciální, nerozparsované

        // velikost
        'navstevnost' => 'navstevnost',
        'ocekavana navstevnost' => 'navstevnost',
        'rocnik' => 'rocnik',

        // poznámka
        'poznamky' => 'poznamka',
        'poznamka' => 'poznamka',
        'poznamky z minulych let' => 'poznamky_minulych_let',
        'poznamky 2024' => 'poznamky_plan',
        'poznamky 2025' => 'poznamky_plan',

        // ignorované / bez vlivu
        'kdo pujde' => null,
        'kdo jde' => null,
        'kdo by jel' => null,
        'kdo' => null,
        'jmeno' => null,
        'brigadnik' => null,
        'pos' => null,
        'vyplata' => null,
        'mzda' => null,
        'cas na stanku' => null,
        'stav' => null,
        'zatim s' => null,
    ];

    /**
     * Vrací mapu: index sloupce → normalizovaný klíč (nebo null = ignorovat).
     *
     * @param array<int, string> $headerRow
     * @return array<int, ?string>
     */
    public static function mapuj(array $headerRow): array
    {
        $result = [];
        foreach ($headerRow as $i => $raw) {
            $result[$i] = is_scalar($raw) ? self::prelozSloupec((string) $raw) : null;
        }
        return $result;
    }

    public static function prelozSloupec(string $raw): ?string
    {
        $key = self::normKey($raw);
        if ($key === '') return null;

        // Exact match
        if (array_key_exists($key, self::ALIASES)) {
            return self::ALIASES[$key];
        }

        // Fuzzy partial — běžné varianty s příponou/předponou
        foreach (self::ALIASES as $alias => $target) {
            if ($target === null) continue;
            if (str_contains($key, $alias) && strlen($alias) >= 4) {
                return $target;
            }
        }

        return null;
    }

    private static function normKey(string $raw): string
    {
        $s = Str::ascii(trim($raw));
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9\/\s]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }
}
