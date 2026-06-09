<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soubory ke stažení — spravované adminem (upload / přejmenovat / smazat).
 *
 * zdroj:
 *  - 'public'  = soubor je v public/soubory/ (původní 3, v gitu) → přímý odkaz
 *  - 'storage' = nahraný adminem, uložený v storage/app/soubory/ → stahuje se přes controller
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soubory_ke_stazeni', function (Blueprint $table) {
            $table->id();
            $table->string('nazev');                       // zobrazovaný název
            $table->string('popis')->nullable();           // krátký popis
            $table->string('zdroj', 10)->default('storage'); // public | storage
            $table->string('cesta');                       // název souboru
            $table->string('download_nazev')->nullable();  // navrhovaný název při stažení
            $table->string('typ', 10)->nullable();         // PDF, JPG…
            $table->unsignedBigInteger('velikost')->nullable(); // bajty
            $table->integer('poradi')->default(0);
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();
        });

        // Původní 3 soubory (jsou v public/soubory/ → zdroj 'public')
        $ted = now();
        DB::table('soubory_ke_stazeni')->insert([
            ['nazev' => 'Banner 190 × 30 cm', 'popis' => 'Banner na stůl / čelo stánku', 'zdroj' => 'public',
             'cesta' => 'banner-190x30.pdf', 'download_nazev' => 'WormUP-banner-190x30.pdf', 'typ' => 'PDF',
             'velikost' => 2967406, 'poradi' => 1, 'vytvoreno' => $ted, 'upraveno' => $ted],
            ['nazev' => 'Banner na stánek 190 × 90 cm', 'popis' => 'Velký banner na stánek', 'zdroj' => 'public',
             'cesta' => 'banner-stanek-190x90.pdf', 'download_nazev' => 'WormUP-banner-stanek-190x90.pdf', 'typ' => 'PDF',
             'velikost' => 3936876, 'poradi' => 2, 'vytvoreno' => $ted, 'upraveno' => $ted],
            ['nazev' => 'Logo WormUP', 'popis' => 'Logo pro tisk i web', 'zdroj' => 'public',
             'cesta' => 'logo-wormup.jpg', 'download_nazev' => 'logo-wormup.jpg', 'typ' => 'JPG',
             'velikost' => 73124, 'poradi' => 3, 'vytvoreno' => $ted, 'upraveno' => $ted],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('soubory_ke_stazeni');
    }
};
