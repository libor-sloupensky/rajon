<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vypnout STRICT mode — DB obsahuje řádky s neplatnými enum hodnotami
        // (z dřívějších scrapingů, kdy AI vrátila něco mimo enum). Bez tohoto
        // by ALTER TABLE selhal s "Data truncated for column typ".
        DB::statement("SET SESSION sql_mode=''");

        // Přidat nové typy + ponechat ostatní (vinobrani/dynobrani zůstávají v enum
        // pro zpětnou kompatibilitu, ale UPDATE je migruje na 'obrani').
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout',
            'food_festival',
            'slavnosti',
            'mestske_slavnosti',
            'obrani',
            'vinobrani',
            'dynobrani',
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

        // Sjednocení '*braní' do jediného typu 'obrani'
        DB::table('akce')->whereIn('typ', ['vinobrani', 'dynobrani'])->update(['typ' => 'obrani']);

        // Heuristicky najít další '*braní' podle názvu (např. bramborobraní, jablkobraní)
        DB::table('akce')
            ->where('typ', 'jiny')
            ->where(function ($q) {
                $q->where('nazev', 'like', '%braní%')
                  ->orWhere('nazev', 'like', '%brani%');
            })
            ->update(['typ' => 'obrani']);

        // Městské slavnosti — heuristic
        $patterny = [
            ['městsk', 'slavnost'],
            ['mestsk', 'slavnost'],
            ['dny města', null],
            ['dny mesta', null],
        ];
        foreach ($patterny as [$a, $b]) {
            $q = DB::table('akce')->where('typ', 'jiny')->orWhere('typ', 'slavnosti');
            $q->where('nazev', 'like', '%' . $a . '%');
            if ($b) $q->where('nazev', 'like', '%' . $b . '%');
            $q->update(['typ' => 'mestske_slavnosti']);
        }
    }

    public function down(): void
    {
        DB::table('akce')->where('typ', 'obrani')->update(['typ' => 'vinobrani']);
        DB::table('akce')->where('typ', 'mestske_slavnosti')->update(['typ' => 'slavnosti']);

        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout', 'food_festival', 'slavnosti', 'vinobrani', 'dynobrani',
            'farmarske_trhy', 'vanocni_trhy', 'velikonocni_trhy', 'jarmark',
            'festival', 'sportovni_akce',
            'koncert', 'divadlo', 'vystava', 'workshop',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");
    }
};
