<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraping_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zdroj_id')->constrained('zdroje')->cascadeOnDelete();
            $table->timestamp('zacatek')->nullable();
            $table->timestamp('konec')->nullable();
            $table->enum('stav', ['probiha', 'uspech', 'chyba', 'castecne'])->default('probiha');
            $table->unsignedInteger('pocet_nalezenych')->default(0);
            $table->unsignedInteger('pocet_novych')->default(0);
            $table->unsignedInteger('pocet_aktualizovanych')->default(0);
            $table->unsignedInteger('pocet_preskocenych')->default(0)->comment('Mimo region nebo malá akce');
            $table->unsignedInteger('pocet_chyb')->default(0);
            $table->text('chyby_detail')->nullable();
            $table->json('statistiky')->nullable()->comment('Detailní statistiky (podle regionu, typu...)');
            $table->timestamp('vytvoreno')->nullable();

            $table->index('zdroj_id');
            $table->index('zacatek');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraping_log');
    }
};
