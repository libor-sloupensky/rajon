<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Konsolidace typů akcí:
 *   - 'trhy_jarmarky' — sloučení farmarske_trhy + vanocni_trhy + velikonocni_trhy + jarmark
 *   - 'workshop' vyřadit z enumu (převést na 'jiny')
 *   - Heuristika *braní napříč VŠEMI typy (chytí Bramborobraní s typem festival/jiny apod.)
 *   - Existing 'divadlo' → stav='zrusena' (jistota — pokud předchozí migrace neprošla)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Rozšířit enum o 'trhy_jarmarky' (legacy hodnoty zatím necháme, abychom mohli updatovat data)
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout',
            'food_festival',
            'slavnosti',
            'mestske_slavnosti',
            'obrani',
            'vinobrani',
            'dynobrani',
            'trhy_jarmarky',
            'farmarske_trhy',
            'vanocni_trhy',
            'velikonocni_trhy',
            'jarmark',
            'festival',
            'sportovni_akce',
            'koncert',
            'divadlo',
            'vystava',
            'workshop',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");

        // 2) Sloučit trhy + jarmarky → 'trhy_jarmarky'
        DB::table('akce')
            ->whereIn('typ', ['farmarske_trhy', 'vanocni_trhy', 'velikonocni_trhy', 'jarmark'])
            ->update(['typ' => 'trhy_jarmarky']);

        // Heuristika podle názvu pro akce s typ='jiny'
        DB::table('akce')
            ->where('typ', 'jiny')
            ->where(function ($q) {
                $q->where('nazev', 'like', '%trh%')
                  ->orWhere('nazev', 'like', '%jarmark%');
            })
            ->update(['typ' => 'trhy_jarmarky']);

        // 3) Workshop → jiny (vyřadit z nabídky)
        DB::table('akce')->where('typ', 'workshop')->update(['typ' => 'jiny']);

        // 4) Jistota — vinobrani/dynobrani → obrani (pro případ, že předchozí migrace neproběhla)
        DB::table('akce')
            ->whereIn('typ', ['vinobrani', 'dynobrani'])
            ->update(['typ' => 'obrani']);

        // 5) Heuristika *braní napříč VŠEMI typy (chytí Bramborobraní/Jablkobraní apod.,
        //    i když mají v DB typ 'festival', 'slavnosti' nebo cokoliv jiného)
        DB::table('akce')
            ->where(function ($q) {
                $q->where('nazev', 'like', '%braní%')
                  ->orWhere('nazev', 'like', '%brani%');
            })
            ->where('typ', '!=', 'obrani')
            ->update(['typ' => 'obrani']);

        // 6) Existing 'divadlo' → stav='zrusena' (jistota)
        DB::table('akce')
            ->where('typ', 'divadlo')
            ->where('stav', '!=', 'zrusena')
            ->update(['stav' => 'zrusena']);

        // 7) Existing 'workshop' akce → stav='zrusena' (uživatel nechce v katalogu)
        //    Pozn.: typ je už 'jiny' (krok 3), ale můžeme dohledat podle názvu
        DB::table('akce')
            ->where('typ', 'jiny')
            ->where('nazev', 'like', '%workshop%')
            ->where('stav', '!=', 'zrusena')
            ->update(['stav' => 'zrusena']);

        // 8) Finální enum bez legacy hodnot (workshop, vinobrani, dynobrani, farmarske_trhy,
        //    vanocni_trhy, velikonocni_trhy, jarmark). Divadlo zůstává — pro zrušené akce.
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout',
            'food_festival',
            'slavnosti',
            'mestske_slavnosti',
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

    public function down(): void
    {
        // Vrátit enum se všemi legacy hodnotami (data zůstávají sloučená — neumíme je rozdělit zpět)
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout',
            'food_festival',
            'slavnosti',
            'mestske_slavnosti',
            'obrani',
            'vinobrani',
            'dynobrani',
            'trhy_jarmarky',
            'farmarske_trhy',
            'vanocni_trhy',
            'velikonocni_trhy',
            'jarmark',
            'festival',
            'sportovni_akce',
            'koncert',
            'divadlo',
            'vystava',
            'workshop',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");
    }
};
