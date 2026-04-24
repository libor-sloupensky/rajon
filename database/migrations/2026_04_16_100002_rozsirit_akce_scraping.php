<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akce', function (Blueprint $table) {
            $table->string('externi_hash', 64)->nullable()->after('zdroj_id')->comment('Hash obsahu pro detekci změn');
            $table->foreignId('propojena_s_akci_id')->nullable()->after('externi_hash')
                ->constrained('akce')->nullOnDelete()
                ->comment('Propojení ročníků/duplicit');

            // Velikostní klasifikace
            $table->unsignedTinyInteger('velikost_skore')->default(0)->after('propojena_s_akci_id')->comment('0-100 AI scoring');
            $table->enum('velikost_stav', ['ano', 'ne', 'nejasna', 'neurceno'])->default('neurceno')->after('velikost_skore');
            $table->text('velikost_info')->nullable()->after('velikost_stav')->comment('AI volný text o velikosti');
            $table->json('velikost_signaly')->nullable()->after('velikost_info')->comment('{navstevnost, pocet_stankaru, rocnik, plocha_m2}');

            // Vstupné pro návštěvníka (najdeno na webtrziste)
            $table->string('vstupne', 100)->nullable()->after('obrat')->comment('Zdarma / částka / text');

            $table->index(['nazev', 'datum_od'], 'idx_dedup');
        });
    }

    public function down(): void
    {
        Schema::table('akce', function (Blueprint $table) {
            $table->dropForeign(['propojena_s_akci_id']);
            $table->dropIndex('idx_dedup');
            $table->dropColumn([
                'externi_hash', 'propojena_s_akci_id',
                'velikost_skore', 'velikost_stav', 'velikost_info', 'velikost_signaly',
                'vstupne',
            ]);
        });
    }
};
