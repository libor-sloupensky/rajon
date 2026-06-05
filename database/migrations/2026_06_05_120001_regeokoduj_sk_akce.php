<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Připraví existující slovenské akce k přegeokódování.
 *
 * Akce naskrapované před opravou mají špatné GPS (slovenské obce navázané na
 * stejnojmenné české → body na české straně mapy). Vlastní geokódování ale
 * NEDĚLÁME tady — stovky HTTP volání na Mapy.cz v synchronní deploy migraci
 * shazovaly deploy-hook (HTTP 500 / timeout). Místo toho jen:
 *   - vynulujeme špatné GPS (merger je při dalším scrapu doplní novou),
 *   - vynutíme re-extrakci (html_hash/kontrola) + přeplánujeme zdroj hned.
 * Geokódování pak proběhne v pipeline (má vynucenou zemi zdroje + opravený
 * geokodér) při nejbližším cron běhu nebo ručním "Spustit".
 */
return new class extends Migration
{
    public function up(): void
    {
        $fid = DB::table('zdroje')->where('url', 'https://www.folklorfest.sk')->value('id');
        $skKrajeIds = DB::table('kraje')->where('zeme', 'SK')->pluck('id')->all();

        if (! $fid && empty($skKrajeIds)) {
            return;
        }

        DB::table('akce')
            ->where(function ($q) use ($fid, $skKrajeIds) {
                if ($fid) {
                    $q->where('zdroj_id', $fid);
                }
                if (! empty($skKrajeIds)) {
                    $q->orWhereIn('kraj_id', $skKrajeIds);
                }
            })
            ->update(['gps_lat' => null, 'gps_lng' => null]);

        if ($fid) {
            DB::table('akce_zdroje')->where('zdroj_id', $fid)
                ->update(['html_hash' => null, 'posledni_kontrola' => null]);
            DB::table('zdroje')->where('id', $fid)->update(['posledni_scraping' => null]);
        }
    }

    public function down(): void
    {
        // Jednorázová příprava dat — bez reverzace.
    }
};
