<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akce_zdroje', function (Blueprint $table) {
            // SHA-256 očištěného textového obsahu HTML — pro detekci změn
            $table->char('html_hash', 64)->nullable()->after('surova_data');

            // Sitemap.xml <lastmod> — kdy server řekl že byla URL naposledy upravena
            $table->timestamp('lastmod_sitemap')->nullable()->after('html_hash');

            // Kdy jsme HTML naposledy fetchli (i když AI nebylo voláno)
            $table->timestamp('posledni_kontrola')->nullable()->after('lastmod_sitemap');

            // Kdy jsme naposledy AI extrakci provedli (hash se změnil)
            $table->timestamp('posledni_extrakce')->nullable()->after('posledni_kontrola');

            // Statistika: kolikrát jsme AI volali pro tuto URL
            $table->unsignedInteger('pocet_extrakci')->default(0)->after('posledni_extrakce');

            $table->index('posledni_kontrola');
            $table->index('posledni_extrakce');
        });
    }

    public function down(): void
    {
        Schema::table('akce_zdroje', function (Blueprint $table) {
            $table->dropIndex(['posledni_kontrola']);
            $table->dropIndex(['posledni_extrakce']);
            $table->dropColumn(['html_hash', 'lastmod_sitemap', 'posledni_kontrola', 'posledni_extrakce', 'pocet_extrakci']);
        });
    }
};
