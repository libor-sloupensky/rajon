<?php

use Database\Seeders\FolklorfestZdrojSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Založí slovenský scraping zdroj Folklorfest.sk (idempotentní seeder).
 * Spustí se automaticky při deployi (deploy-hook migrate --force).
 */
return new class extends Migration
{
    public function up(): void
    {
        (new FolklorfestZdrojSeeder())->run();
    }

    public function down(): void
    {
        // Data-only migrace. Zdroj nemažeme automaticky (mohl by kaskádově
        // smazat navázané akce) — případné odebrání řešit ručně v adminu.
    }
};
