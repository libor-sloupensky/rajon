<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // TEXT (65 KB) je málo pro stack traces u dlouhých scraping běhů.
        // MEDIUMTEXT = 16 MB.
        DB::statement('ALTER TABLE scraping_log MODIFY COLUMN chyby_detail MEDIUMTEXT NULL');
        DB::statement('ALTER TABLE zdroje MODIFY COLUMN posledni_chyby MEDIUMTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE scraping_log MODIFY COLUMN chyby_detail TEXT NULL');
        DB::statement('ALTER TABLE zdroje MODIFY COLUMN posledni_chyby TEXT NULL');
    }
};
