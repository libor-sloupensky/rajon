<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_volani', function (Blueprint $table) {
            $table->id();
            $table->string('model', 60);
            $table->string('ucel', 60)->comment('akce_extrakce, email_extrakce, ...');
            $table->foreignId('zdroj_id')->nullable()->constrained('zdroje')->nullOnDelete();
            $table->foreignId('akce_id')->nullable()->constrained('akce')->nullOnDelete();
            $table->foreignId('uzivatel_id')->nullable()->constrained('uzivatele')->nullOnDelete();
            $table->foreignId('scraping_log_id')->nullable()->constrained('scraping_log')->nullOnDelete();

            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_creation_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);

            $table->decimal('cena_usd', 10, 6)->default(0)->comment('Vypočtená cena podle modelu');

            $table->boolean('uspech')->default(true);
            $table->text('chyba')->nullable();
            $table->timestamp('vytvoreno')->nullable();

            $table->index('vytvoreno');
            $table->index('uzivatel_id');
            $table->index('zdroj_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_volani');
    }
};
