<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Oprava folklorfest.sk po opravě AI promptu (CZ+SK lokalizace).
 *
 * Existující akce byly extrahovány promptem, který znal jen ČR → mají kraj=null
 * a nejsou pod filtrem Slovensko. Vynutíme jejich re-extrakci opraveným promptem:
 *  - vynulujeme html_hash + posledni_kontrola/extrakce (jinak by je scraper přeskočil),
 *  - vynulujeme posledni_scraping zdroje (ať ho cron vezme hned, ne až za 156 h),
 *  - zrychlíme frekvenci na 24 h (440 akcí × limit 50/běh — at se dobackfilluje).
 */
return new class extends Migration
{
    public function up(): void
    {
        $zdrojId = DB::table('zdroje')->where('url', 'https://www.folklorfest.sk')->value('id');
        if (! $zdrojId) {
            return;
        }

        DB::table('akce_zdroje')->where('zdroj_id', $zdrojId)->update([
            'html_hash' => null,
            'posledni_kontrola' => null,
            'posledni_extrakce' => null,
        ]);

        DB::table('zdroje')->where('id', $zdrojId)->update([
            'posledni_scraping' => null,
            'frekvence_hodin' => 24,
        ]);
    }

    public function down(): void
    {
        // Jednorázová oprava dat — bez reverzace.
    }
};
