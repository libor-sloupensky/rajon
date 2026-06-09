<?php

use Database\Seeders\StrankySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editovatelné články (Franšízanti, Jak prodávat). Obsah HTML, editace na webu (Trix).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stranky', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nadpis');
            $table->longText('obsah')->nullable();
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();
        });

        (new StrankySeeder())->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('stranky');
    }
};
