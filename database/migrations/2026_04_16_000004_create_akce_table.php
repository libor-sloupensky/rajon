<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akce', function (Blueprint $table) {
            $table->id();
            $table->string('nazev');
            $table->string('slug')->unique();
            $table->enum('typ', [
                'pout', 'food_festival', 'slavnosti', 'vinobrani',
                'dynobrani', 'farmarske_trhy', 'vanocni_trhy',
                'jarmark', 'festival', 'jiny',
            ])->default('jiny');
            $table->text('popis')->nullable();
            $table->date('datum_od')->nullable();
            $table->date('datum_do')->nullable();
            $table->string('misto')->nullable();
            $table->string('adresa')->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->string('okres')->nullable();
            $table->string('kraj')->nullable();
            $table->string('organizator')->nullable();
            $table->string('kontakt_email')->nullable();
            $table->string('kontakt_telefon', 20)->nullable();
            $table->string('web_url', 500)->nullable();
            $table->string('zdroj_url', 500)->nullable();
            $table->enum('zdroj_typ', ['scraping', 'email', 'excel', 'manual'])->default('manual');
            $table->unsignedInteger('najem')->nullable();
            $table->unsignedInteger('obrat')->nullable();
            $table->text('poznamka')->nullable();
            $table->enum('stav', ['navrh', 'overena', 'zrusena'])->default('navrh');
            $table->foreignId('uzivatel_id')->nullable()->constrained('uzivatele')->nullOnDelete();
            $table->foreignId('zdroj_id')->nullable()->constrained('zdroje')->nullOnDelete();
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();

            $table->index(['datum_od', 'datum_do']);
            $table->index('kraj');
            $table->index('okres');
            $table->index('typ');
            $table->index('stav');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akce');
    }
};
