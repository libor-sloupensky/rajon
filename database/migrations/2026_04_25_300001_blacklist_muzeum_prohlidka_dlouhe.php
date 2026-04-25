<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rozšíření blacklistu:
 *   1. Místo obsahuje muzeum/expozice/galerie → BL (i pro sport. akce)
 *   2. Název obsahuje "prohlídk" (prohlídka, prohlídky) → BL
 *   3. Trvání > 14 dní → BL (dlouhodobá výstava/expozice)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET SESSION sql_mode=''");

        // 1) Místo: muzeum/expozice/galerie — strict (bez ohledu na typ)
        DB::table('akce')
            ->where('stav', '!=', 'zrusena')
            ->where(function ($q) {
                foreach (['%muzeum%', '%muzea%', '%muzeu%', '%muzejní%', '%expozic%',
                          '%galerie %', '%galerii%', '%galerie,%'] as $pat) {
                    $q->orWhere('misto', 'like', $pat);
                }
            })
            ->update(['stav' => 'zrusena']);

        // 2) Název obsahuje "prohlídk" → BL
        DB::table('akce')
            ->where('stav', '!=', 'zrusena')
            ->where(function ($q) {
                $q->where('nazev', 'like', '%prohlídk%')
                  ->orWhere('nazev', 'like', '%prohlidk%');
            })
            ->update(['stav' => 'zrusena']);

        // 3) Akce trvající > 14 dní = dlouhodobá akce (typicky výstava) → BL
        $maxDny = (int) config('scraping.max_trvani_dny', 14);
        DB::table('akce')
            ->where('stav', '!=', 'zrusena')
            ->whereNotNull('datum_od')
            ->whereNotNull('datum_do')
            ->whereRaw('DATEDIFF(datum_do, datum_od) > ?', [$maxDny])
            ->update(['stav' => 'zrusena']);
    }

    public function down(): void
    {
        // Down: nevracíme — manuální revert
    }
};
