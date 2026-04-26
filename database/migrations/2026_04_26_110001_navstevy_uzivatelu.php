<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracking návštěv uživatelů — pro analytiku v adminu.
 * Návštěva = sekvence requestů. Pokud je další request > 2h od posledního,
 * vznikne nová návštěva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navstevy', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('uzivatel_id');
            $t->timestamp('zacatek');
            $t->timestamp('konec');

            $t->index(['uzivatel_id', 'zacatek'], 'navstevy_uzivatel_zacatek');
            $t->foreign('uzivatel_id')->references('id')->on('uzivatele')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navstevy');
    }
};
