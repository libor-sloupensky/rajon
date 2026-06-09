<?php

use App\Models\Stranka;
use Illuminate\Database\Migrations\Migration;

/**
 * Desatero u Franšízantů: <ol> (auto-číslování) → odstavce s čísly vepsanými do
 * textu (průběžně 1–10). Trix neumí pokračující číslování přes podnadpisy
 * (každý <ol> začne od 1), takže čísla dáme do textu, ať je má admin pod kontrolou.
 *
 * Čte ŽIVÝ obsah z DB (ne ze seederu) — zachová případné webové editace.
 */
return new class extends Migration
{
    public function up(): void
    {
        $s = Stranka::where('slug', 'fransizanti')->first();
        if (! $s || ! str_contains((string) $s->obsah, '<ol')) {
            return;
        }
        $s->update(['obsah' => $this->prevod($s->obsah)]);
    }

    private function prevod(string $html): string
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8" ?><div id="rajon-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xp = new \DOMXPath($doc);
        $cislo = 1;
        foreach (iterator_to_array($xp->query('//ol')) as $ol) {
            foreach (iterator_to_array($ol->getElementsByTagName('li')) as $li) {
                $p = $doc->createElement('p');
                $p->appendChild($doc->createTextNode($cislo . '. '));
                while ($li->firstChild) {
                    $p->appendChild($li->firstChild);
                }
                $ol->parentNode->insertBefore($p, $ol);
                $cislo++;
            }
            $ol->parentNode->removeChild($ol);
        }

        $root = $doc->getElementById('rajon-root');
        $out = '';
        foreach ($root->childNodes as $c) {
            $out .= $doc->saveHTML($c);
        }

        return trim($out);
    }

    public function down(): void
    {
        // jednosměrné — zpět nelze spolehlivě (čísla jsou už v textu)
    }
};
