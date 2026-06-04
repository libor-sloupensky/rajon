<?php

use Database\Seeders\KrajeOkresySeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Naseeduje slovenské kraje + okresy (rozšíření scrapingu na SR).
 *
 * Volá idempotentní KrajeOkresySeeder (updateOrCreate) — bezpečné při
 * opakovaném běhu. Díky tomu se SK lokalita nahraje automaticky při deployi
 * (deploy-hook spouští `migrate --force`), bez nutnosti ručního &seed s tokenem.
 *
 * Po naseedování umí LokalizaceResolver navázat slovenské akce na kraj_id/okres_id
 * a seznam okresů v AI promptu se automaticky rozšíří o slovenské.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new KrajeOkresySeeder())->run();
    }

    public function down(): void
    {
        // Data-only migrace — kraje/okresy ČR i SR ponecháváme (sdílené s ostatními daty).
        // Případný rollback by řešil samostatný cílený seeder.
    }
};
