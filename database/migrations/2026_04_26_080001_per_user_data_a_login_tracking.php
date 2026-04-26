<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user data k akcím (poznámka + palec hodnocení) + login tracking u uživatelů.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sloupec posledni_prihlaseni v uzivatele
        if (!Schema::hasColumn('uzivatele', 'posledni_prihlaseni')) {
            Schema::table('uzivatele', function (Blueprint $t) {
                $t->timestamp('posledni_prihlaseni')->nullable()->after('email_overen_v');
            });
        }

        // Per-user data k akci — osobní poznámka + palec
        if (!Schema::hasTable('akce_uzivatel')) {
            Schema::create('akce_uzivatel', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('akce_id');
                $t->unsignedBigInteger('uzivatel_id');
                $t->enum('palec', ['nahoru', 'stred', 'dolu'])->nullable();
                $t->text('osobni_poznamka')->nullable();
                $t->timestamp('vytvoreno')->useCurrent();
                $t->timestamp('upraveno')->useCurrent()->useCurrentOnUpdate();

                $t->unique(['akce_id', 'uzivatel_id'], 'akce_uzivatel_unique');
                $t->index('uzivatel_id');
                $t->index('akce_id');
                // FK constraints — pokud akce/uzivatel smažu, smažou se i tyto záznamy
                $t->foreign('akce_id')->references('id')->on('akce')->onDelete('cascade');
                $t->foreign('uzivatel_id')->references('id')->on('uzivatele')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('akce_uzivatel');
        if (Schema::hasColumn('uzivatele', 'posledni_prihlaseni')) {
            Schema::table('uzivatele', function (Blueprint $t) {
                $t->dropColumn('posledni_prihlaseni');
            });
        }
    }
};
