<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zdroje', function (Blueprint $table) {
            $table->string('robots_url', 500)->nullable()->after('url');
            $table->string('sitemap_url', 500)->nullable()->after('robots_url');
            $table->string('cms_typ', 50)->nullable()->after('sitemap_url')->comment('wordpress_mec, joomla, custom, kudyznudy, webtrziste');
            $table->string('url_pattern_list', 200)->nullable()->after('cms_typ');
            $table->string('url_pattern_detail', 200)->nullable()->after('url_pattern_list');
            $table->json('struktura')->nullable()->after('url_pattern_detail')->comment('CSS selectors / XPath / AI mapping');
            $table->unsignedInteger('frekvence_hodin')->default(168)->after('struktura')->comment('Jak často scrapovat (hodiny)');
            $table->text('posledni_chyby')->nullable()->after('frekvence_hodin');
            $table->boolean('vyzaduje_login')->default(false)->after('posledni_chyby');
        });
    }

    public function down(): void
    {
        Schema::table('zdroje', function (Blueprint $table) {
            $table->dropColumn([
                'robots_url', 'sitemap_url', 'cms_typ',
                'url_pattern_list', 'url_pattern_detail', 'struktura',
                'frekvence_hodin', 'posledni_chyby', 'vyzaduje_login',
            ]);
        });
    }
};
