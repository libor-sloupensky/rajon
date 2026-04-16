<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rezervace', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akce_id')->constrained('akce')->cascadeOnDelete();
            $table->foreignId('uzivatel_id')->constrained('uzivatele')->cascadeOnDelete();
            $table->enum('stav', ['zajimam_se', 'prihlasena', 'potvrzena', 'zrusena'])->default('zajimam_se');
            $table->text('poznamka')->nullable();
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();

            $table->unique(['akce_id', 'uzivatel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rezervace');
    }
};
