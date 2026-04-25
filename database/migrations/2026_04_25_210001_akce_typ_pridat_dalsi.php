<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rozšíření enum o nové typy z AI klasifikace
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout', 'food_festival', 'slavnosti', 'vinobrani', 'dynobrani',
            'farmarske_trhy', 'vanocni_trhy', 'velikonocni_trhy', 'jarmark',
            'festival', 'sportovni_akce',
            'koncert', 'divadlo', 'vystava', 'workshop',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");

        // Hromadná oprava existujících akcí podle názvu (heuristic)
        // Pouze typu 'jiny' přepiše na konkrétnější
        $pravidla = [
            // [pattern, novy_typ]
            ['vinobran', 'vinobrani'],
            ['dýňobr', 'dynobrani'],
            ['dynobr', 'dynobrani'],
            ['food festival', 'food_festival'],
            ['gastro', 'food_festival'],
            ['pivní', 'food_festival'],
            ['pivni', 'food_festival'],
            ['vánoční trh', 'vanocni_trhy'],
            ['vanocni trh', 'vanocni_trhy'],
            ['advent', 'vanocni_trhy'],
            ['velikonoční', 'velikonocni_trhy'],
            ['velikonocni', 'velikonocni_trhy'],
            ['farmářský', 'farmarske_trhy'],
            ['farmarsky', 'farmarske_trhy'],
            ['farmářské trhy', 'farmarske_trhy'],
            ['jarmark', 'jarmark'],
            ['posvícení', 'slavnosti'],
            ['posviceni', 'slavnosti'],
            ['hody', 'slavnosti'],
            ['národopisn', 'slavnosti'],
            ['narodopisn', 'slavnosti'],
            ['historick', 'slavnosti'],
            ['středověk', 'slavnosti'],
            ['rytířs', 'slavnosti'],
            ['rytirs', 'slavnosti'],
            ['slavnosti', 'slavnosti'],
            ['dny města', 'slavnosti'],
            ['dny mesta', 'slavnosti'],
            ['festival', 'festival'],
            ['výstav', 'vystava'],
            ['vystav', 'vystava'],
            ['koncert', 'koncert'],
            ['divadlo', 'divadlo'],
            ['workshop', 'workshop'],
            ['kurz ', 'workshop'],
            ['dílna', 'workshop'],
            ['dilna', 'workshop'],
            ['běh', 'sportovni_akce'],
            ['beh ', 'sportovni_akce'],
            ['závod', 'sportovni_akce'],
            ['zavod', 'sportovni_akce'],
            ['turnaj', 'sportovni_akce'],
            ['poutě', 'pout'],
            ['poute', 'pout'],
            ['pouť', 'pout'],
            ['pout ', 'pout'],
        ];

        foreach ($pravidla as [$pattern, $novyTyp]) {
            DB::table('akce')
                ->where('typ', 'jiny')
                ->where('nazev', 'like', '%' . $pattern . '%')
                ->update(['typ' => $novyTyp]);
        }
    }

    public function down(): void
    {
        // Vrátit enum zpět (bez nově přidaných typů)
        // Akce s novým typem se nejprve převedou na 'jiny'
        DB::table('akce')->whereIn('typ', ['velikonocni_trhy', 'koncert', 'divadlo', 'vystava', 'workshop'])
            ->update(['typ' => 'jiny']);

        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout', 'food_festival', 'slavnosti', 'vinobrani', 'dynobrani',
            'farmarske_trhy', 'vanocni_trhy', 'jarmark', 'festival',
            'sportovni_akce', 'jiny'
        ) NOT NULL DEFAULT 'jiny'");
    }
};
