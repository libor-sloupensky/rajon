<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rozšíření blacklistu o koncert/vystava/prednaska + reklasifikace existing akcí.
 *
 * 1. ENUM: přidat 'prednaska'
 * 2. Heuristická reklasifikace podle názvu / popisu (kurz → workshop,
 *    výstava → vystava, přednáška → prednaska)
 * 3. Existing akce s typem v blacklistu → stav='zrusena'
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET SESSION sql_mode=''");

        // 1) ENUM rozšířit o 'prednaska'
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout',
            'food_festival',
            'slavnosti',
            'obrani',
            'trhy_jarmarky',
            'festival',
            'sportovni_akce',
            'koncert',
            'divadlo',
            'vystava',
            'prednaska',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");

        // 2) Heuristická reklasifikace existing akcí podle NÁZVU
        //    (override aktuálního typu — typ='slavnosti' atd. mohlo být chybně přiřazené AI)
        $vzory = [
            'workshop' => ['Kurz ', 'kurz ', ' kurz ', 'Workshop', 'workshop', 'Lekce', 'lekce', 'Školení', 'skoleni', 'Dílna ', 'dilna '],
            'prednaska' => ['Přednáška', 'přednáška', 'Beseda', 'beseda'],
            'vystava' => ['Výstava', 'výstava', 'výstavu '],
            'koncert' => [' koncert', 'Koncert', 'Trio', 'Kvartet', 'Quartet', 'Quintet'],
        ];

        foreach ($vzory as $typ => $vz) {
            foreach ($vz as $v) {
                DB::table('akce')
                    ->where('nazev', 'like', '%' . $v . '%')
                    ->whereNotIn('typ', ['pout', 'food_festival', 'slavnosti', 'obrani',
                                          'trhy_jarmarky', 'festival', 'sportovni_akce'])
                    ->orWhere(function ($q) use ($v, $typ) {
                        // Pro koncert chceme override i ze "slavnosti" pokud nazev má " Trio "/koncert
                        if ($typ === 'koncert' && in_array($v, ['Trio', 'Kvartet', 'Quartet'])) {
                            $q->where('nazev', 'like', '%' . $v . '%')
                              ->where('typ', 'slavnosti')
                              ->where(function ($qq) {
                                  // Jen pokud popis NEnaznačuje pouť/slavnosti
                                  $qq->where('popis', 'not like', '%pouť%')
                                     ->where('popis', 'not like', '%hody%')
                                     ->where('popis', 'not like', '%slavnost%');
                              });
                        }
                    })
                    ->update(['typ' => $typ]);
            }
        }

        // Specifický fix: výstavy chybně klasifikované jako 'slavnosti'
        DB::table('akce')
            ->where('typ', 'slavnosti')
            ->where('nazev', 'like', '%výstav%')
            ->update(['typ' => 'vystava']);

        // Specifický fix: kurz / lekce v názvu — overriduje 'slavnosti'
        DB::table('akce')
            ->where('typ', 'slavnosti')
            ->where(function ($q) {
                $q->where('nazev', 'like', 'Kurz %')
                  ->orWhere('nazev', 'like', '%Workshop%')
                  ->orWhere('nazev', 'like', '%Lekce %');
            })
            ->update(['typ' => 'workshop']);

        // 3) Heuristika z POPISU pro malé akce s rezervací (často jsou to přednášky/kurzy)
        DB::table('akce')
            ->where('typ', 'slavnosti')
            ->where(function ($q) {
                $q->where('popis', 'like', '%přednáška%')
                  ->orWhere('popis', 'like', '%prednaska%')
                  ->orWhere('popis', 'like', '%beseda%');
            })
            ->update(['typ' => 'prednaska']);

        // 4) Akce s typy v blacklistu → stav='zrusena' (nezobrazují se v katalogu)
        $blacklist = (array) config('scraping.ignorovane_typy', [
            'divadlo', 'workshop', 'koncert', 'vystava', 'prednaska',
        ]);
        DB::table('akce')
            ->whereIn('typ', $blacklist)
            ->where('stav', '!=', 'zrusena')
            ->update(['stav' => 'zrusena']);
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode=''");
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout',
            'food_festival',
            'slavnosti',
            'obrani',
            'trhy_jarmarky',
            'festival',
            'sportovni_akce',
            'koncert',
            'divadlo',
            'vystava',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");
    }
};
