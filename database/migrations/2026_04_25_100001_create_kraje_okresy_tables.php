<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kraje', function (Blueprint $table) {
            $table->id();
            $table->string('nazev')->unique();
            $table->string('slug')->unique();
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();
        });

        Schema::create('okresy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kraj_id')->constrained('kraje')->cascadeOnDelete();
            $table->string('nazev');
            $table->string('slug')->unique();
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();

            $table->index('nazev');
        });

        // Akce — FK na kraj a okres (nullable, postupně doplníme)
        Schema::table('akce', function (Blueprint $table) {
            $table->foreignId('kraj_id')->nullable()->constrained('kraje')->nullOnDelete();
            $table->foreignId('okres_id')->nullable()->constrained('okresy')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('akce', function (Blueprint $table) {
            $table->dropForeign(['kraj_id']);
            $table->dropForeign(['okres_id']);
            $table->dropColumn(['kraj_id', 'okres_id']);
        });
        Schema::dropIfExists('okresy');
        Schema::dropIfExists('kraje');
    }
};
