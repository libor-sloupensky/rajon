<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adresa uživatele (sídlo) — pro výpočet vzdálenosti k akcím.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uzivatele', function (Blueprint $t) {
            if (!Schema::hasColumn('uzivatele', 'mesto')) {
                $t->string('mesto', 100)->nullable()->after('region');
            }
            if (!Schema::hasColumn('uzivatele', 'psc')) {
                $t->string('psc', 10)->nullable()->after('mesto');
            }
            if (!Schema::hasColumn('uzivatele', 'gps_lat')) {
                $t->decimal('gps_lat', 10, 7)->nullable()->after('psc');
            }
            if (!Schema::hasColumn('uzivatele', 'gps_lng')) {
                $t->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('uzivatele', function (Blueprint $t) {
            $t->dropColumn(['mesto', 'psc', 'gps_lat', 'gps_lng']);
        });
    }
};
