<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akce', function (Blueprint $table) {
            // Admin komentář — AI sem NIKDY nezasahuje (plní se z XLS nebo ručně)
            $table->text('admin_komentar')->nullable()->after('poznamka');
        });

        Schema::table('zdroje', function (Blueprint $table) {
            // Flag: zdroj je web pořadatele (trust se pro jeho akce zvýší)
            $table->boolean('je_web_poradatele')->default(false)->after('vyzaduje_login');
        });

        Schema::table('akce_zdroje', function (Blueprint $table) {
            // Kontext: byl scraping z webu pořadatele akce?
            // Null = neurčeno, true/false dle detekce
            $table->boolean('je_od_poradatele')->nullable()->after('externi_id');
        });
    }

    public function down(): void
    {
        Schema::table('akce', function (Blueprint $table) {
            $table->dropColumn('admin_komentar');
        });
        Schema::table('zdroje', function (Blueprint $table) {
            $table->dropColumn('je_web_poradatele');
        });
        Schema::table('akce_zdroje', function (Blueprint $table) {
            $table->dropColumn('je_od_poradatele');
        });
    }
};
