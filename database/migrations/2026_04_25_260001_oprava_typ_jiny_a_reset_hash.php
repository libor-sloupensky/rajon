<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Jednorázová oprava existujících akcí:
 *   1. Akce s typ='jiny', které mají v názvu jarmark/pouť/slavnost atd.
 *      → reklasifikovat heuristicky podle názvu.
 *   2. Reset html_hash u akcí ze zdrojů 2 a 3 (Stánkař, Webtržiště) — aby se
 *      při dalším scrapingu znovu zavolala AI a získaly se chybějící informace.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET SESSION sql_mode=''");

        // 1. Heuristická reklasifikace podle názvu
        $pravidla = [
            'obrani' => ['%vinobran%', '%dynobr%', '%dýňobr%', '%jablkobran%',
                         '%bramborobran%', '%braní%', '%brani%'],
            'trhy_jarmarky' => ['%jarmark%', '%farmářské trhy%', '%farmarske trhy%',
                                '%vánoční trh%', '%vanocni trh%', '%velikonoční trh%',
                                '%velikonocni trh%', '%adventní trh%', '%adventni trh%',
                                '%řemeslný trh%', '%remeslny trh%'],
            'pout' => ['%pouť%'],
            'food_festival' => ['%food festival%', '%gastrofestival%', '%gulášfest%',
                                '%gulasfest%', '%pivní fest%', '%pivni fest%'],
            'slavnosti' => ['%slavnosti%', '%hody%', '%posvícení%', '%posviceni%',
                            '%dny města%', '%dny mesta%', '%historick%', '%rytířs%',
                            '%středověk%', '%folklor%', '%národopisn%'],
            'festival' => ['%festival%'],
            'koncert' => ['%koncert%'],
            'vystava' => ['%výstav%', '%vystav%'],
            'sportovni_akce' => ['%závod%', '%zavod%', '%turnaj%', '%běh %', '%beh %'],
        ];

        foreach ($pravidla as $typ => $vzory) {
            foreach ($vzory as $vzor) {
                DB::table('akce')
                    ->where('typ', 'jiny')
                    ->where('nazev', 'like', $vzor)
                    ->update(['typ' => $typ]);
            }
        }

        // 2. Reset html_hash + posledni_extrakce u akcí ze Stánkař + Webtržiště
        //    Důvod: scraping je extrahoval pouze přes JSON-LD, chybí 90% polí.
        //    Nový kód mergeuje JSON-LD + AI, takže reprocesování dotáhne kontakty.
        DB::table('akce_zdroje')
            ->whereIn('zdroj_id', [2, 3])
            ->update([
                'html_hash' => null,
                'posledni_extrakce' => null,
            ]);
    }

    public function down(): void
    {
        // Down: nemažeme nic — reklasifikace nemá inverzní operaci a reset hash
        // se "znovu vyplní" při dalším scrapingu.
    }
};
