<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extrakce detailu akce z HTML přes Anthropic Claude.
 * Role AI: analytik specializovaný na extrakci dat o kulturních/stánkařských akcích v ČR.
 */
class AkceExtractor
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.anthropic.key');
        $this->model = (string) config('services.anthropic.model', 'claude-haiku-4-5-20251001');
    }

    /**
     * Extrahuje strukturovaná data akce z HTML stránky.
     * Vrací array s poli: nazev, typ, datum_od, datum_do, misto, adresa, gps_lat, gps_lng,
     * okres, kraj, organizator, kontakt_email, kontakt_telefon, web_url, vstupne, popis,
     * velikost_info, velikost_signaly.
     */
    public function extrahuj(string $html, string $url): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('ANTHROPIC_API_KEY not set — cannot extract');
            return null;
        }

        // Očisti HTML — odstraň <script>, <style>, nav a zkrať
        $text = $this->ocistiHtml($html);

        $systemPrompt = <<<'PROMPT'
Jsi analytik specializovaný na extrakci strukturovaných dat o veřejných akcích v České republice (pouti, festivaly, hody, slavnosti, vinobraní, jarmarky, food festivaly, farmářské trhy, historické slavnosti).

Tvým úkolem je z HTML/textu stránky extrahovat jednu akci a vrátit JSON s přesně definovanými poli. Pokud nějaké pole v textu není, vrať null.

DŮLEŽITÁ PRAVIDLA:
- popis: STRUČNĚ 1-2 věty max (ne marketingový text)
- velikost_info: STRUČNĚ 1-2 věty max, jen konkrétní fakta (počty návštěvníků, stánkařů, ročník)
- Nevymýšlej — pokud informaci nemáš, vrať null
- Datumy vždy ve formátu YYYY-MM-DD
- KRAJ je povinný pokud znáš obec/město. Pokud kraj v textu není, ODVOĎ ho ze
  zeměpisné polohy obce/města (např. "Chvalovice u Znojma" → "Jihomoravský kraj",
  "Brno" → "Jihomoravský kraj", "Ostrava" → "Moravskoslezský kraj"). Vrať vždy
  oficiální český název kraje s velkým "K" tam, kde to je gramaticky správně:
  "Jihomoravský kraj", "Moravskoslezský kraj", "Olomoucký kraj", "Pardubický kraj",
  "Královéhradecký kraj", "Zlínský kraj", "Kraj Vysočina", "Středočeský kraj",
  "Jihočeský kraj", "Plzeňský kraj", "Karlovarský kraj", "Ústecký kraj",
  "Liberecký kraj", "Hlavní město Praha".
- Pokud existují dvě obce stejného názvu, použij tu, která je v textu logická
  (podle dalších indicií — typ akce, datum, organizátor); jinak nech null.

Formát odpovědi: POUZE platný JSON objekt, bez úvodního/závěrečného textu, bez markdown bloku.
PROMPT;

        $userPrompt = <<<PROMPT
Z následujícího textu stránky o akci extrahuj JSON s tímto schématem:

{
  "nazev": "název akce (bez roku, pokud to jde — ten bude v datum_od)",
  "typ": "jeden z: pout | hody | dny_mesta | food_festival | vinobrani | dynobrani | jarmark | farmarske_trhy | historicke_slavnosti | hudebni_festival | folklor | vanocni_trhy | velikonocni_trhy | obecni_slavnosti | koncert | divadlo | vystava | workshop | sportovni | jine",
  "datum_od": "YYYY-MM-DD",
  "datum_do": "YYYY-MM-DD nebo null pokud jednodenní",
  "cas": "textově např. 10:00-22:00 nebo null",
  "misto": "název místa (náměstí, park, obec)",
  "adresa": "ulice + číslo",
  "mesto": "obec/město",
  "psc": "PSČ",
  "okres": "okres",
  "kraj": "přesný název kraje (např. 'Jihomoravský kraj', 'Kraj Vysočina')",
  "gps_lat": číslo nebo null,
  "gps_lng": číslo nebo null,
  "organizator": "název organizátora/pořadatele",
  "kontakt_email": "email",
  "kontakt_telefon": "telefon",
  "web_url": "oficiální web akce",
  "vstupne": "cena pro návštěvníka (text — 'zdarma', '150 Kč', null)",
  "popis": "1-2 věty max — stručně o čem akce je",
  "rocnik": "číslo ročníku, např. 25, nebo null",
  "velikost_info": "1-2 věty max — konkrétní fakta (návštěvnost, stánkaři, plocha). Pokud nic konkrétního není, null.",
  "velikost_signaly": {
    "navstevnost": číslo nebo null,
    "pocet_stankaru": číslo nebo null,
    "rocnik": číslo nebo null,
    "plocha_m2": číslo nebo null,
    "trvani_dny": číslo (z datum_od/datum_do)
  }
}

URL stránky: {$url}

Obsah stránky:
{$text}

Vrať POUZE JSON objekt.
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 2048,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('AI extraction failed', [
                    'status' => $response->status(),
                    'url' => $url,
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            $content = $response->json('content.0.text', '');
            return $this->parseJsonFromResponse($content);
        } catch (\Exception $e) {
            Log::error("AI extraction exception: {$e->getMessage()}", ['url' => $url]);
            return null;
        }
    }

    /** Vypočítat velikostní skóre (0-100) z extrahovaných dat. */
    public function vypocetVelikostSkore(array $data): int
    {
        $skore = 0;

        // Typ akce → klíčová slova (+30)
        $typyVelke = ['pout', 'hody', 'dny_mesta', 'food_festival', 'vinobrani', 'dynobrani',
                      'jarmark', 'farmarske_trhy', 'historicke_slavnosti', 'hudebni_festival',
                      'folklor', 'vanocni_trhy', 'velikonocni_trhy', 'obecni_slavnosti'];
        if (in_array($data['typ'] ?? '', $typyVelke, true)) {
            $skore += 30;
        }

        // Outdoor lokace (+20)
        $misto = mb_strtolower(($data['misto'] ?? '') . ' ' . ($data['popis'] ?? ''));
        $keywordsOutdoor = ['náměstí', 'namesti', 'park', 'kolonáda', 'kolonada', 'areál', 'areal',
                             'letiště', 'letiste', 'nádvoří', 'nadvori', 'pole', 'ulice', 'zámek'];
        foreach ($keywordsOutdoor as $kw) {
            if (str_contains($misto, $kw)) {
                $skore += 20;
                break;
            }
        }

        // Trvání
        $trvani = $this->spoctiTrvaniDny($data['datum_od'] ?? null, $data['datum_do'] ?? null);
        if ($trvani >= 3) {
            $skore += 15;
        } elseif ($trvani >= 2) {
            $skore += 10;
        }

        // Registrovaní stánkaři 15+ (+25) — signál ze webtrziste
        $pocetStankaru = (int) ($data['velikost_signaly']['pocet_stankaru'] ?? 0);
        if ($pocetStankaru >= 15) {
            $skore += 25;
        }

        // Ročník 5+ (+10) — etablovaná tradice
        $rocnik = (int) ($data['velikost_signaly']['rocnik'] ?? $data['rocnik'] ?? 0);
        if ($rocnik >= 5) {
            $skore += 10;
        }

        return min(100, $skore);
    }

    /** Odvoď stav velikosti z skore. */
    public function urciStavVelikosti(int $skore): string
    {
        if ($skore >= 50) return 'ano';
        if ($skore >= 40) return 'nejasna';
        return 'ne';
    }

    /** Výpočet délky trvání akce ve dnech. */
    protected function spoctiTrvaniDny(?string $od, ?string $do): int
    {
        if (!$od) return 1;
        try {
            $dOd = new \DateTime($od);
            $dDo = $do ? new \DateTime($do) : $dOd;
            return max(1, $dDo->diff($dOd)->days + 1);
        } catch (\Exception) {
            return 1;
        }
    }

    /** Očisti HTML — vyhoď noise (script/style/nav) a omez délku. */
    protected function ocistiHtml(string $html): string
    {
        $html = preg_replace('/<(script|style|noscript|nav|footer|header)[^>]*>.*?<\/\1>/is', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        $text = strip_tags($html, '<p><h1><h2><h3><h4><br><li><ul><ol><table><tr><td><th><a>');
        $text = preg_replace('/\s+/', ' ', $text);
        return mb_substr(trim($text), 0, 12000);
    }

    /** Vytáhni JSON z AI odpovědi (někdy obsahuje i prefix/suffix). */
    protected function parseJsonFromResponse(string $content): ?array
    {
        $content = trim($content);

        // Přímo JSON
        $data = json_decode($content, true);
        if (is_array($data)) return $data;

        // Extrahuj z markdown bloku
        if (preg_match('/```(?:json)?\s*(\{.+?\})\s*```/s', $content, $m)) {
            $data = json_decode($m[1], true);
            if (is_array($data)) return $data;
        }

        // Najdi první { … } blok
        if (preg_match('/\{.+\}/s', $content, $m)) {
            $data = json_decode($m[0], true);
            if (is_array($data)) return $data;
        }

        Log::warning('Nelze rozparsovat JSON z AI odpovědi', ['content' => mb_substr($content, 0, 500)]);
        return null;
    }
}
