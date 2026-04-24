<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akce', function (Blueprint $table) {
            // Pole zamčená adminem — scraping NIKDY nepřepíše
            // Formát: {'kontakt_email': '2026-04-24T10:30:00', 'popis': '...'}
            $table->json('pole_manualni')->nullable()->after('velikost_signaly');

            // Z jakého zdroje jsme vzali hodnotu konkrétního pole
            // Formát: {'gps_lat': 'kudyznudy', 'popis': 'stankar'}
            $table->json('pole_zdroje')->nullable()->after('pole_manualni');

            // Konflikty mezi zdroji — admin řeší manuálně
            // Formát: [{'pole': 'datum_od', 'hodnoty': [{'zdroj': 'kudyznudy', 'value': '...'}, ...]}]
            $table->json('konflikty')->nullable()->after('pole_zdroje');

            // Audit log merge rozhodnutí (posledních 20 operací)
            $table->json('merge_log')->nullable()->after('konflikty');

            // Kandidáti na ročníkové propojení (AI navrhl, admin potvrdí)
            // Pole akce_id (odkaz na akce.id)
            $table->json('navrh_propojeni')->nullable()->after('merge_log');
        });
    }

    public function down(): void
    {
        Schema::table('akce', function (Blueprint $table) {
            $table->dropColumn([
                'pole_manualni', 'pole_zdroje', 'konflikty',
                'merge_log', 'navrh_propojeni',
            ]);
        });
    }
};
