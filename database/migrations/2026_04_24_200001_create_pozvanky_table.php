<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pozvanky', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();         // náhodný token do URL
            $table->string('email');
            $table->string('jmeno')->nullable();
            $table->string('prijmeni')->nullable();
            $table->enum('role', ['admin', 'fransizan'])->default('fransizan');
            $table->string('region')->nullable();           // předvyplněné při registraci

            $table->enum('stav', ['cekajici', 'prijata', 'expirovana', 'zrusena'])->default('cekajici');
            $table->timestamp('plati_do')->nullable();      // expirace pozvánky
            $table->timestamp('prijata_v')->nullable();

            $table->foreignId('pozval_uzivatel_id')->nullable()->constrained('uzivatele')->nullOnDelete();
            $table->foreignId('uzivatel_id')->nullable()->constrained('uzivatele')->nullOnDelete()
                ->comment('Nově vzniklý uživatel po přijetí pozvánky');

            $table->text('poznamka')->nullable();
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();

            $table->index(['email', 'stav']);
            $table->index('stav');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pozvanky');
    }
};
