<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uzivatele', function (Blueprint $table) {
            $table->id();
            $table->string('jmeno');
            $table->string('prijmeni');
            $table->string('email')->unique();
            $table->string('telefon', 20)->nullable();
            $table->string('heslo');
            $table->string('google_id')->nullable()->unique();
            $table->enum('role', ['admin', 'fransizan'])->default('fransizan');
            $table->string('region')->nullable();
            $table->timestamp('email_overen_v')->nullable();
            $table->rememberToken();
            $table->timestamp('vytvoreno')->nullable();
            $table->timestamp('upraveno')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('uzivatele');
    }
};
