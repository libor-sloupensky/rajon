<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Přidá zemi ke krajům — základ pro multi-country (CZ/SK, výhledově PL/HU).
 *
 * Default 'CZ'; slovenské kraje (naseedované migrací 100001) označíme 'SK'.
 * Slouží k dvouúrovňovému filtru Země → Kraj ve veřejném katalogu.
 */
return new class extends Migration
{
    /** Slovenské kraje — pro backfill zeme='SK'. */
    private const SK_KRAJE = [
        'Bratislavský kraj', 'Trnavský kraj', 'Trenčiansky kraj', 'Nitriansky kraj',
        'Žilinský kraj', 'Banskobystrický kraj', 'Prešovský kraj', 'Košický kraj',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('kraje', 'zeme')) {
            Schema::table('kraje', function (Blueprint $table) {
                $table->string('zeme', 2)->default('CZ')->after('slug')->index();
            });
        }

        DB::table('kraje')->whereIn('nazev', self::SK_KRAJE)->update(['zeme' => 'SK']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('kraje', 'zeme')) {
            Schema::table('kraje', function (Blueprint $table) {
                $table->dropColumn('zeme');
            });
        }
    }
};
