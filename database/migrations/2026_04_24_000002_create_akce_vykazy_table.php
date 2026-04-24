<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akce_vykazy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akce_id')->constrained('akce')->cascadeOnDelete();
            $table->unsignedSmallInteger('rok');
            $table->date('datum_od')->nullable();
            $table->date('datum_do')->nullable();
            $table->unsignedInteger('trzba')->nullable()->comment('CZK');
            $table->unsignedInteger('najem')->nullable()->comment('CZK');
            $table->text('poznamka')->nullable();
            $table->string('zdroj_excel')->nullable()->comment('nazev_souboru / nazev_sheetu');
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();

            $table->unique(['akce_id', 'rok']);
            $table->index('rok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akce_vykazy');
    }
};
