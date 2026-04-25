<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Stávající divadla → zrusena (nebudou se zobrazovat v katalogu).
        // Konfigurovatelné přes config('scraping.ignorovane_typy').
        $ignorovaneTypy = (array) config('scraping.ignorovane_typy', ['divadlo']);

        if (!empty($ignorovaneTypy)) {
            DB::table('akce')
                ->whereIn('typ', $ignorovaneTypy)
                ->where('stav', '!=', 'zrusena')
                ->update(['stav' => 'zrusena']);
        }
    }

    public function down(): void
    {
        // Vrátit stav 'overena' pro ignorované typy (nelze rozlišit, jaké stav
        // měly před touto migrací — defaultně overena).
        $ignorovaneTypy = (array) config('scraping.ignorovane_typy', ['divadlo']);

        if (!empty($ignorovaneTypy)) {
            DB::table('akce')
                ->whereIn('typ', $ignorovaneTypy)
                ->where('stav', 'zrusena')
                ->update(['stav' => 'overena']);
        }
    }
};
