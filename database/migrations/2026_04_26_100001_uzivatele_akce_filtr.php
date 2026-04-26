<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uzivatele', function (Blueprint $t) {
            if (!Schema::hasColumn('uzivatele', 'akce_filtr')) {
                $t->json('akce_filtr')->nullable()->after('gps_lng');
            }
        });
    }

    public function down(): void
    {
        Schema::table('uzivatele', function (Blueprint $t) {
            $t->dropColumn('akce_filtr');
        });
    }
};
