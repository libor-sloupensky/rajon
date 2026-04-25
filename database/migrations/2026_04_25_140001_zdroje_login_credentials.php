<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zdroje', function (Blueprint $table) {
            // URL login formuláře (kam POST credentials)
            $table->string('login_url', 500)->nullable()->after('vyzaduje_login');

            // Šifrovaný JSON s pole-name → hodnota (např. {"login":"x","heslo":"y"}).
            // Šifrování přes Crypt::encryptString — Laravel APP_KEY.
            $table->text('login_credentials')->nullable()->after('login_url')
                ->comment('Crypt::encryptString JSON s form fields');

            // Cache ne-trvalé session cookie + expirace
            $table->text('login_session')->nullable()->after('login_credentials')
                ->comment('Cached cookie jar (expire after N hours)');

            $table->timestamp('login_session_until')->nullable()->after('login_session');
        });
    }

    public function down(): void
    {
        Schema::table('zdroje', function (Blueprint $table) {
            $table->dropColumn(['login_url', 'login_credentials', 'login_session', 'login_session_until']);
        });
    }
};
