<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zdroje', function (Blueprint $table) {
            $table->id();
            $table->string('nazev');
            $table->string('url', 500);
            $table->enum('typ', ['katalog', 'web_mesta', 'email', 'excel', 'manual'])->default('katalog');
            $table->enum('stav', ['aktivni', 'neaktivni', 'chyba'])->default('aktivni');
            $table->timestamp('posledni_scraping')->nullable();
            $table->unsignedInteger('pocet_akci')->default(0);
            $table->foreignId('uzivatel_id')->nullable()->constrained('uzivatele')->nullOnDelete();
            $table->text('poznamka')->nullable();
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zdroje');
    }
};
