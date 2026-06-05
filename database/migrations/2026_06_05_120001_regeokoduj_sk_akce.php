<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Přepočítá GPS u existujících slovenských akcí.
 *
 * Akce naskrapované před opravou geokodéru mají špatné souřadnice (slovenské
 * obce navázané na stejnojmenné české → body na české straně mapy). Tady je
 * znovu geokódujeme s VYNUCENÝM Slovenskem. Běží na serveru přes deploy-hook
 * migrate (server má MAPYCZ_API_KEY i dosah na Mapy.cz).
 *
 * Cílí akce z folklorfestu + akce s vyřešeným slovenským krajem.
 */
return new class extends Migration
{
    public function up(): void
    {
        $geokoder = app(\App\Services\Geokoder::class);

        $folklorfestId = DB::table('zdroje')->where('url', 'https://www.folklorfest.sk')->value('id');
        $skKrajeIds = DB::table('kraje')->where('zeme', 'SK')->pluck('id')->all();

        if (! $folklorfestId && empty($skKrajeIds)) {
            return;
        }

        $akce = DB::table('akce')
            ->where(function ($q) use ($folklorfestId, $skKrajeIds) {
                if ($folklorfestId) {
                    $q->where('zdroj_id', $folklorfestId);
                }
                if (! empty($skKrajeIds)) {
                    $q->orWhereIn('kraj_id', $skKrajeIds);
                }
            })
            ->limit(200)   // pojistka proti timeoutu deploy-hooku (300 s); zbytek dožene cron
            ->get(['id', 'adresa', 'misto', 'mesto', 'okres', 'kraj']);

        $opraveno = 0;
        $bezVysledku = 0;
        foreach ($akce as $a) {
            try {
                $gps = $geokoder->geokoduj($a->adresa, $a->misto, $a->mesto, $a->okres, $a->kraj, 'Slovensko');
                if ($gps) {
                    DB::table('akce')->where('id', $a->id)->update([
                        'gps_lat' => $gps['gps_lat'],
                        'gps_lng' => $gps['gps_lng'],
                    ]);
                    $opraveno++;
                } else {
                    $bezVysledku++;
                }
            } catch (\Throwable) {
                $bezVysledku++;
            }
        }

        Log::info("Re-geokódování SK akcí: celkem={$akce->count()}, opraveno={$opraveno}, bez_výsledku={$bezVysledku}");
    }

    public function down(): void
    {
        // Jednorázová oprava dat — bez reverzace.
    }
};
