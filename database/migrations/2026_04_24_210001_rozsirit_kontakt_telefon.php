<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // VARCHAR(20) bylo málo — AI často vrací "+420 734 567 890, kontakt přes web"
        DB::statement('ALTER TABLE akce MODIFY COLUMN kontakt_telefon VARCHAR(50) NULL');
        DB::statement('ALTER TABLE uzivatele MODIFY COLUMN telefon VARCHAR(50) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE akce MODIFY COLUMN kontakt_telefon VARCHAR(20) NULL');
        DB::statement('ALTER TABLE uzivatele MODIFY COLUMN telefon VARCHAR(20) NULL');
    }
};
