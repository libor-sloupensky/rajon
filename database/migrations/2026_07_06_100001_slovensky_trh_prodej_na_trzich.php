<?php

use App\Models\Stranka;
use Illuminate\Database\Migrations\Migration;

/**
 * Doplní do stránky „Slovenský trh" sekci 5 – Prodej na trzích (povolení obce).
 * Vkládá do ŽIVÉHO obsahu před „Užitečné odkazy" (zachová webové editace),
 * idempotentní (pokud už tam je, nedělá nic).
 */
return new class extends Migration
{
    public function up(): void
    {
        $s = Stranka::where('slug', 'slovensky-trh')->first();
        if (! $s || str_contains((string) $s->obsah, 'Prodej na trzích')) {
            return;
        }

        $sekce = <<<'HTML'
<h1>5. Prodej na trzích (povolení obce)</h1>
<p>Pro prodej na konkrétním trhu je potřeba <strong>povolení k prodeji na trhovém místě</strong> (zák. 178/1998 Z. z.), které vydává <strong>obec/město</strong> daného trhu. V praxi to za prodejce obvykle vyřizuje <strong>organizátor (správce) trhu</strong> při přihlášení — ověřte si to však u něj předem a doložte požadované doklady (živnostenské oprávnění, registraci na RVPS, doklad o pokladně). <strong>Bez tohoto povolení nelze na trhu prodávat.</strong></p>

HTML;

        $obsah = $s->obsah;
        if (str_contains($obsah, '<h1>Užitečné odkazy</h1>')) {
            $obsah = str_replace('<h1>Užitečné odkazy</h1>', $sekce . '<h1>Užitečné odkazy</h1>', $obsah);
        } else {
            $obsah .= "\n" . $sekce;
        }

        $s->update(['obsah' => $obsah]);
    }

    public function down(): void
    {
        // jednosměrné
    }
};
