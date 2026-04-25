<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Blacklist akcí podle INDOOR místa (kino, sál, kostel, galerie, muzeum,
 * knihovna, studio, kulturní dům, restaurace, škola, …).
 *
 * Výjimky (zůstanou v katalogu i v indoor místě):
 *   - typ='trhy_jarmarky' (vánoční trhy bývají v sále/hale)
 *   - typ='sportovni_akce' (sportovní haly, aerokluby)
 *   - místo obsahuje outdoor signál (náměstí, park, areál, ulice)
 *
 * AI mnoho indoor akcí chybně přiřadí jako 'slavnosti'/'jiny' — toto je
 * druhý filtr z místa, ne z typu.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET SESSION sql_mode=''");

        // Indoor místa — LIKE patterny
        $indoorVzory = [
            '%kino %', '%kino,%', '%městské kino%',
            '%ní sál%', '%ý sál%', '%á sála%', '%kulturní sál%', '%koncertní sál%',
            '%kostel %', '%kostele%', '%kostela%', '%kapli%', '%kaple %',
            '%klášter%', '%synagog%',
            '%galerie %', '%galerii%', '%galerii%',
            '%muzeum%', '%muzea %', '%muzeu%',
            '%expozic%',
            '%knihovn%',
            '%studio %', '%studia %', '%studiu %',
            '%restaurac%', '%kavárn%', '%vinárn%', '%pivnic%',
            '%kulturní dům%', '%kulturního domu%', '%lidový dům%',
            '%kulturní centrum%',
            '%dům dětí%', '%dům umění%',
            '%základní umělecká škola%', '%základní škola%', '%střední škola%',
            '%gymnázium%', '%gymnáziu%',
            ' zuš ', '%zuš %',
        ];

        // Outdoor signály — pokud místo OBSAHUJE některý z těchto, NEBLACKLISTOVAT
        // (např. "areál Kulturního domu" → outdoor; "Náměstí u Restaurace" → outdoor)
        // Použijeme to v PHP po načtení matchujících akcí.
        $outdoorSignaly = ['náměstí', 'park ', ' park,', ' parku', 'areál', 'areálu',
                            ' ulice', ' ulici', 'nábřeží', 'pole', 'louka', 'zahrada',
                            'venkovní', 'open air'];

        $celkemBL = 0;

        foreach ($indoorVzory as $vzor) {
            $kandidati = DB::table('akce')
                ->where('stav', '!=', 'zrusena')
                ->whereNotIn('typ', ['trhy_jarmarky', 'sportovni_akce'])  // výjimky
                ->where('misto', 'like', $vzor)
                ->get(['id', 'misto']);

            foreach ($kandidati as $k) {
                $mistoLow = mb_strtolower($k->misto ?? '');
                // Outdoor signál v místě — výjimka
                $jeOutdoor = false;
                foreach ($outdoorSignaly as $os) {
                    if (str_contains($mistoLow, $os)) { $jeOutdoor = true; break; }
                }
                if ($jeOutdoor) continue;

                DB::table('akce')->where('id', $k->id)->update(['stav' => 'zrusena']);
                $celkemBL++;
            }
        }

        // Logging do migrations_log není standardní — přes Laravel log
        \Illuminate\Support\Facades\Log::info("Migration 290001: blacklisted {$celkemBL} akcí podle indoor místa");
    }

    public function down(): void
    {
        // Down: nevracíme — manuálně by admin musel nastavit stav zpět
    }
};
