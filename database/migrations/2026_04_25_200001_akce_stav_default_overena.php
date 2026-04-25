<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existující 'navrh' → 'overena' (scrapnuté akce jsou automaticky platné)
        DB::table('akce')->where('stav', 'navrh')->update(['stav' => 'overena']);

        // Změnit default sloupce
        DB::statement("ALTER TABLE akce MODIFY COLUMN stav ENUM('navrh', 'overena', 'zrusena') NOT NULL DEFAULT 'overena'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE akce MODIFY COLUMN stav ENUM('navrh', 'overena', 'zrusena') NOT NULL DEFAULT 'navrh'");
    }
};
