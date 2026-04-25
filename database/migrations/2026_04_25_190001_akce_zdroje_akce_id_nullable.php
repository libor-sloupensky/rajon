<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akce_zdroje', function (Blueprint $table) {
            // Zruš existující FK + recreate jako nullable
            $table->dropForeign(['akce_id']);
        });
        Schema::table('akce_zdroje', function (Blueprint $table) {
            $table->foreignId('akce_id')->nullable()->change();
            $table->foreign('akce_id')->references('id')->on('akce')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Při rollbacku: nejdřív smazat osiřelé řádky
        \DB::statement('DELETE FROM akce_zdroje WHERE akce_id IS NULL');

        Schema::table('akce_zdroje', function (Blueprint $table) {
            $table->dropForeign(['akce_id']);
        });
        Schema::table('akce_zdroje', function (Blueprint $table) {
            $table->foreignId('akce_id')->nullable(false)->change();
            $table->foreign('akce_id')->references('id')->on('akce')->cascadeOnDelete();
        });
    }
};
