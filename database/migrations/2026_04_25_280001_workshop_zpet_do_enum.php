<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vrátí 'workshop' do enumu — předchozí migrace 270001 ho zkoušela přiřadit
 * akcím "Kurz X" ale workshop už nebyl v enumu od 240001 → updaty selhávaly
 * v non-strict modu a typ se uložil jako prázdný řetězec.
 *
 * Workshop je v ignorovane_typy (skrytý z katalogu), ale enum ho musí obsahovat.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET SESSION sql_mode=''");

        // 1) Rozšířit enum o 'workshop'
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
            'workshop',
            'prednaska',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");

        // 2) Opravit akce s prázdným typem (vznikly z minulé selhané reklasifikace)
        //    Heuristika: pokud nazev/popis ukazuje na workshop/kurz, dej workshop.
        $broken = DB::table('akce')->where('typ', '')->orWhereNull('typ')->get(['id', 'nazev', 'popis']);
        foreach ($broken as $a) {
            $kombi = mb_strtolower(($a->nazev ?? '') . ' ' . ($a->popis ?? ''));
            $typ = 'jiny';
            if (preg_match('/\bkurz\s|\bworkshop\b|\blekce\b|\bdílna\b|\bškolení\b/u', $kombi)) {
                $typ = 'workshop';
            } elseif (preg_match('/\bpřednáška\b|\bbeseda\b/u', $kombi)) {
                $typ = 'prednaska';
            } elseif (preg_match('/\bvýstava\b/u', $kombi)) {
                $typ = 'vystava';
            }
            DB::table('akce')->where('id', $a->id)->update(['typ' => $typ]);
        }

        // 3) Schovat všechny ignored typy (po opravě jich víc)
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
            'prednaska',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");
    }
};
