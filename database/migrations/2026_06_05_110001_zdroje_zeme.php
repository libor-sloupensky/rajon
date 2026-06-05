<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Země zdroje (CZ/SK/PL/HU). Umožní vynutit správnou zemi při geokódování —
 * folklorfest je celý slovenský, takže jeho akce se hledají na Slovensku
 * i když AI netrefí kraj. Null = neurčeno (chová se jako dosud, default ČR).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('zdroje', 'zeme')) {
            Schema::table('zdroje', function (Blueprint $table) {
                $table->string('zeme', 2)->nullable()->after('typ');
            });
        }

        DB::table('zdroje')->where('url', 'https://www.folklorfest.sk')->update(['zeme' => 'SK']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('zdroje', 'zeme')) {
            Schema::table('zdroje', function (Blueprint $table) {
                $table->dropColumn('zeme');
            });
        }
    }
};
