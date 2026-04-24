<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout', 'food_festival', 'slavnosti', 'vinobrani',
            'dynobrani', 'farmarske_trhy', 'vanocni_trhy',
            'jarmark', 'festival', 'sportovni_akce', 'jiny'
        ) NOT NULL DEFAULT 'jiny'");
    }

    public function down(): void
    {
        DB::statement("UPDATE akce SET typ = 'jiny' WHERE typ = 'sportovni_akce'");
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout', 'food_festival', 'slavnosti', 'vinobrani',
            'dynobrani', 'farmarske_trhy', 'vanocni_trhy',
            'jarmark', 'festival', 'jiny'
        ) NOT NULL DEFAULT 'jiny'");
    }
};
