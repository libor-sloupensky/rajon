<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Many-to-many: jedna akce může být na více zdrojích
        Schema::create('akce_zdroje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akce_id')->constrained('akce')->cascadeOnDelete();
            $table->foreignId('zdroj_id')->constrained('zdroje')->cascadeOnDelete();
            $table->string('url', 500);
            $table->string('externi_id', 200)->nullable();
            $table->json('surova_data')->nullable()->comment('Raw extracted data + HTML snippet');
            $table->timestamp('posledni_ziskani')->nullable();
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();

            $table->unique(['zdroj_id', 'url'], 'uniq_zdroj_url');
            $table->index(['akce_id', 'zdroj_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akce_zdroje');
    }
};
