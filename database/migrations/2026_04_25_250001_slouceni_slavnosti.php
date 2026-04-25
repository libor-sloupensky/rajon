<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sjednocení 'slavnosti' + 'mestske_slavnosti' → jediný typ 'slavnosti'.
 * Label v UI: "Slavnosti a městské akce".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET SESSION sql_mode=''");

        // 1) Defenzivně přepsat orphan/prázdné na 'jiny'
        DB::table('akce')->where('typ', '')->orWhereNull('typ')->update(['typ' => 'jiny']);

        // 2) Sloučit mestske_slavnosti → slavnosti
        DB::table('akce')->where('typ', 'mestske_slavnosti')->update(['typ' => 'slavnosti']);

        // 3) Zúžit ENUM bez mestske_slavnosti
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout',
            'food_festival',
            'slavnosti',
            'obrani',
            'trhy_jarmarky',
            'festival',
            'sportovni_akce',
            'koncert',
            'divadlo',
            'vystava',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode=''");
        DB::statement("ALTER TABLE akce MODIFY COLUMN typ ENUM(
            'pout',
            'food_festival',
            'slavnosti',
            'mestske_slavnosti',
            'obrani',
            'trhy_jarmarky',
            'festival',
            'sportovni_akce',
            'koncert',
            'divadlo',
            'vystava',
            'jiny'
        ) NOT NULL DEFAULT 'jiny'");
    }
};
