<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scraping_log', function (Blueprint $table) {
            // 0 = plný (bez limitu), >0 = test s limitem
            $table->unsignedInteger('limit_pouzity')->nullable()->after('stav')
                ->comment('Limit počtu URL ke zpracování. NULL/0 = plný běh.');
        });
    }

    public function down(): void
    {
        Schema::table('scraping_log', function (Blueprint $table) {
            $table->dropColumn('limit_pouzity');
        });
    }
};
