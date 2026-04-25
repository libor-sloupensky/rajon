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

    public function __construct(
        protected ?LokalizaceResolver $lokalizace = null,
        protected ?JsonLdExtractor $jsonLd = null,
    ) {
        $this->apiKey = (string) config('services.anthropic.key');
        $this->model = (string) config('services.anthropic.model', 'claude-haiku-4-5-20251001');
        $this->lokalizace ??= app(LokalizaceResolver::class);
        $this->jsonLd ??= app(JsonLdExtractor::class);
    }

    /**
     * Extrahuje strukturovaná data akce z HTML stránky.
     * Vrací array s poli: nazev, typ, datum_od, datum_do, misto, adresa, gps_lat, gps_lng,
     * okres, kraj, organizator, kontakt_email, kontakt_telefon, web_url, vstupne, popis,
     * velikost_info, velikost_signaly.
     */
    /** Kontextová data pro logging AI volání (nastavené z venku). */
    protected array $kontext = [];

    /** Nastavit metadata pro tracking AI volání (zdroj_id, scraping_log_id, uzivatel_id). */
    public function nastavKontext(array $kontext): void
    {
        $this->kontext = $kontext;
    }

    /** Vrátí aktuální kontext (pro merge dalších polí jako akce_id). */
    public function kontextProSdileni(): array
    {
        return $this->kontext;
    }

    public function extrahuj(string $html, string $url): ?array
    {
        // 1. Vždy zkusíme JSON-LD (zdarma) — má nejpřesnější datum/GPS když je
        $jsonLdData = $this->jsonLd->extrahuj($html);

        // Pokud JSON-LD pokrývá VŠECHNY důležité fronty, AI nezavolat (úspora tokenů)
        if ($jsonLdData && $this->jeKompletni($jsonLdData)) {
            Log::info('Použit JSON-LD (bez AI volání)', ['url' => $url]);
            return $jsonLdData;
        }

        // 2. AI extrakce — JSON-LD chybí nebo je neúplné
        if (empty($this->apiKey)) {
            Log::warning('ANTHROPIC_API_KEY not set — cannot extract via AI');
            return $jsonLdData ?: null;
        }

        // Očisti HTML — odstraň <script>, <style>, nav a zkrať
        $text = $this->ocistiHtml($html);

        $systemPrompt = <<<'PROMPT'
Jsi analytik specializovaný na extrakci strukturovaných dat o veřejných akcích v České republice (pouti, festivaly, hody, slavnosti, vinobraní, jarmarky, food festivaly, farmářské trhy, historické slavnosti).

Tvým úkolem je z HTML/textu stránky extrahovat jednu akci a vrátit JSON s přesně definovanými poli. Pokud nějaké pole v textu není, vrať null.

DŮLEŽITÁ PRAVIDLA:
- popis: STRUČNĚ 1-2 věty max (ne marketingový text). Téměř vždy aspoň krátký popis udělej z textu — i pokud webový "popis" sekce neexistuje, sestav 1 větu z toho, co o akci víš. NIKDY nevracej prázdný řetězec — buď popis nebo null.
- velikost_info: STRUČNĚ 1-2 věty max, jen konkrétní fakta (počty návštěvníků, stánkařů, ročník)
- Nevymýšlej — pokud informaci nemáš, vrať null (NE prázdný řetězec)
- Datumy vždy ve formátu YYYY-MM-DD
- KONTAKTY: pečlivě procházej celý text — email/telefon mohou být uvnitř popisu,
  v patičce, v sekci "Kontakt", "Pořadatel", apod. Neignoruj je.
- ORGANIZÁTOR: pokud najdeš kontakt nebo zmínku o pořadateli (i v popisu), vrať
  jeho název. Když není v textu, vrať null.
- WEB_URL: oficiální web AKCE nebo POŘADATELE (ne URL stránky kterou scrapuju).
  Hledej v textu odkazy na "www.akce.cz", "viz: ...", apod.
- MISTO musí být konkrétní MÍSTO (náměstí, park, areál, obec). NIKDY nevracej
  jen kraj (např. "Středočeský kraj") jako misto. Když znáš jen kraj, vrať
  místo=null a kraj=ten kraj.
- MESTO: jméno obce/města (např. "Praha", "Tři Studně")
- OKRES je povinný pokud znáš obec/město. Vyber jeden z níže uvedeného seznamu
  okresů ČR podle zeměpisné polohy obce. POZOR: některé obce mají stejný název,
  rozhoduj podle dalších indicií v textu (PSČ, kraj, blízká města, organizátor).
  Pokud opravdu nemáš dost informací, vrať okres=null.
- KRAJ není potřeba odvozovat — bude doplněn z okresu automaticky. Pokud je
  v textu explicitně, můžeš ho vrátit, jinak null.
- EXTRAKCE Z NÁZVU: pokud v textu stránky není město, ZKUS HO NAJÍT v NÁZVU
  akce. Typicky: "Festival X v Brně 2026" → mesto="Brno", "DEN - Sobotka 2026"
  → mesto="Sobotka", "Slavnosti chřestu v Ivančicích" → mesto="Ivančice".
  Pozor na české skloňování: "v Janských Lázních" → mesto="Janské Lázně",
  "v Brně" → mesto="Brno", "v Praze" → mesto="Praha".
- TYP — KLÍČOVÁ pravidla (pro WormUP stánkaře jsou DŮLEŽITÉ jen velké veřejné akce):
  * **kurz / workshop / lekce / školení / dílna** → typ="workshop" (NE "slavnosti")
  * **přednáška / beseda** → typ="prednaska" (NE "slavnosti")
  * **samostatný koncert (1 kapela/sólista, klubový sál)** → typ="koncert"
  * **hudební festival** (víc kapel, program jiných žánrů) → typ="festival"
  * **malá výstava obrazů / galerie / muzeum / expozice** → typ="vystava"
  * **veletržní výstava** (prodejní s mnoha vystavovateli) → typ="trhy_jarmarky"
  * **pouť / slavnosti / hody** — ZŮSTÁVÁ pout/slavnosti i když má v programu
    koncerty/přednášky/divadlo (jsou doplňky hlavní akce)
  * Pokud má v názvu "Trio", "Kvartet", "Jazz X" — pravděpodobně samostatný
    koncert hudební skupiny → typ="koncert"
  * Pokud akce VYŽADUJE rezervaci/registraci nebo má omezenou kapacitu, je
    to typicky uzavřená malá akce → typ="workshop" nebo "prednaska"
- TYP: vyber CO NEJPŘESNĚJI z níže uvedeného seznamu. Pokud akce má v názvu
  "jarmark", "trh", "vánoční trh", musí být typ="trhy_jarmarky" — NIKDY ne "jine".
  Pokud má v názvu "pouť", "pout", musí být "pout". Při pochybnostech radši zvol
  typ="jine" než nesprávný.

KRITICKÉ POLE — vhodne_pro_stankare:
WormUP jsou stánkaři, kteří prodávají na veřejných outdoor akcích. Vhodné jsou jen:
- Pouti, hody, slavnosti, městské akce, festivaly (outdoor, masové, veřejné)
- Trhy a jarmarky (vč. vnitřních prostor — vánoční trhy v halách OK)
- Sportovní akce (závody, turnaje s diváky)
- Food festivaly, vinobraní, obraní, gastrofestivaly

NEVHODNÉ (vrať vhodne_pro_stankare=false):
- Klubové koncerty (Jazz Dock, hudební klub) — i když AI typ='slavnosti' nebo 'koncert'
- Galerie, muzea, expozice, výstavy obrazů (i v exteriéru muzea)
- Divadelní představení, opery, baletní vystoupení
- Přednášky, besedy, prezentace, autorské čtení
- Kurzy, workshopy, lekce, dílny, školení (i pro děti)
- Komorní vystoupení, recitály, autorské večery
- Akce pro uzavřené skupiny (členové klubu, žáci školy)
- Prohlídky (zámky, kostely, města)
- Dlouhodobé výstavy a expozice (víc než 14 dní)
- Indoor akce v sálech, divadlech, kinech, kostelech, knihovnách

Při rozhodování zvažuj:
- Místo: outdoor (náměstí, park, areál) = OK; indoor (klub, sál, galerie) = většinou NE
- Trvání: dlouhodobé (víc než 2 týdny) = NE
- Charakter: masová veřejná akce = OK; komorní/exkluzivní = NE
- Cílovka: každý kdo přijde = OK; jen registrovaní/specifická skupina = NE

Formát odpovědi: POUZE platný JSON objekt, bez úvodního/závěrečného textu, bez markdown bloku.
PROMPT;

        $seznamOkresu = $this->lokalizace->seznamProPrompt();

        $userPrompt = <<<PROMPT
Z následujícího textu stránky o akci extrahuj JSON s tímto schématem:

{
  "nazev": "název akce (bez roku, pokud to jde — ten bude v datum_od)",
  "typ": "jeden z: pout | slavnosti (zahrnuje hody/dny města/městské/obecní/historické slavnosti/folklor — VŠECHNO sloučené) | food_festival | obrani (vinobraní/dýňobraní/bramborobraní/jablkobraní/jakékoliv *braní) | trhy_jarmarky (farmářské/vánoční/velikonoční trhy + jarmarky — všechno pod jeden typ) | festival | koncert | divadlo | vystava | sportovni | jine",
  "datum_od": "YYYY-MM-DD",
  "datum_do": "YYYY-MM-DD nebo null pokud jednodenní",
  "cas": "textově např. 10:00-22:00 nebo null",
  "misto": "název místa (náměstí, park, obec)",
  "adresa": "ulice + číslo",
  "mesto": "obec/město",
  "psc": "PSČ",
  "okres": "PŘESNÝ název okresu z níže uvedeného seznamu, jinak null",
  "kraj": "název kraje pokud je v textu, jinak null (doplní se z okresu)",
  "gps_lat": číslo nebo null,
  "gps_lng": číslo nebo null,
  "organizator": "název organizátora/pořadatele",
  "kontakt_email": "email",
  "kontakt_telefon": "telefon",
  "web_url": "oficiální web akce",
  "vstupne": "cena pro návštěvníka (text — 'zdarma', '150 Kč', null)",
  "popis": "1-2 věty max — stručně o čem akce je",
  "vhodne_pro_stankare": true | false (true = veřejná masová outdoor akce s prostorem pro stánky; false = klub/galerie/muzeum/divadlo/přednáška/kurz/komorní akce — viz pravidla výše),
  "duvod_nevhodnosti": "krátký důvod 2-5 slov, jen když vhodne_pro_stankare=false (např. 'klubový koncert', 'expozice galerie', 'prohlídka zámku')",
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

SEZNAM OKRESŮ ČR (vyber jeden, pokud znáš obec/město):
{$seznamOkresu}

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
                $this->logAiVolani(0, 0, 0, 0, false, "HTTP {$response->status()}");
                return null;
            }

            // Cost tracking — uložit usage z odpovědi
            $usage = $response->json('usage', []);
            $this->logAiVolani(
                (int) ($usage['input_tokens'] ?? 0),
                (int) ($usage['output_tokens'] ?? 0),
                (int) ($usage['cache_creation_input_tokens'] ?? 0),
                (int) ($usage['cache_read_input_tokens'] ?? 0),
                true,
                null,
            );

            $content = $response->json('content.0.text', '');
            $aiData = $this->parseJsonFromResponse($content);

            // 3. Merge JSON-LD + AI — JSON-LD má prioritu pro datum/GPS,
            //    AI doplní popis/organizátor/kontakty/adresu/vstupné.
            if ($jsonLdData && $aiData) {
                return $this->mergeJsonLdSAi($jsonLdData, $aiData);
            }

            return $aiData;
        } catch (\Exception $e) {
            Log::error("AI extraction exception: {$e->getMessage()}", ['url' => $url]);
            $this->logAiVolani(0, 0, 0, 0, false, mb_substr($e->getMessage(), 0, 500));
            return null;
        }
    }

    /**
     * Mergeuje JSON-LD a AI data. JSON-LD má vyšší trust pro strukturovaná pole
     * (datum, GPS, vstupné), AI pro textová (popis, organizátor, kontakty).
     */
    protected function mergeJsonLdSAi(array $jsonLd, array $ai): array
    {
        // Pole, kde JSON-LD má přednost pokud je vyplněné
        $jsonLdPriority = ['datum_od', 'datum_do', 'gps_lat', 'gps_lng', 'vstupne'];
        // Pole, kde AI má přednost pokud je vyplněné (delší/úplnější popis, atd.)
        $aiPriority = ['popis', 'organizator', 'kontakt_email', 'kontakt_telefon',
                       'adresa', 'okres', 'kraj', 'web_url', 'velikost_info',
                       'velikost_signaly', 'rocnik'];

        $merged = $jsonLd;
        foreach ($aiPriority as $f) {
            if (!empty($ai[$f]) && (empty($merged[$f]) || $merged[$f] === '')) {
                $merged[$f] = $ai[$f];
            }
        }
        // AI typ je často přesnější (z textu); JSON-LD má jen schema.org typy
        if (!empty($ai['typ']) && $ai['typ'] !== 'jine') {
            $merged['typ'] = $ai['typ'];
        }
        // Datum/GPS — pokud JSON-LD nemá, vzít AI
        foreach ($jsonLdPriority as $f) {
            if (empty($merged[$f]) && !empty($ai[$f])) {
                $merged[$f] = $ai[$f];
            }
        }
        // Misto — pokud JSON-LD vrátil kraj jako misto (typický Stánkař problém),
        // a AI má lepší misto, použít AI
        if (!empty($ai['misto']) && (empty($merged['misto']) ||
                str_contains((string) $merged['misto'], 'kraj'))) {
            $merged['misto'] = $ai['misto'];
        }

        $merged['_zdroj_extrakce'] = 'json_ld+ai';
        return $merged;
    }

    /** Uložit záznam o AI volání do tabulky ai_volani. */
    protected function logAiVolani(int $input, int $output, int $cacheWrite, int $cacheRead, bool $uspech, ?string $chyba): void
    {
        $cenik = config('scraping.cenik')[$this->model] ?? config('scraping.cenik.default');

        // Cena: input + output + cache write + cache read (vše per 1M tokens)
        $cena = ($input * $cenik['input']
            + $output * $cenik['output']
            + $cacheWrite * $cenik['cache_write']
            + $cacheRead * $cenik['cache_read']) / 1_000_000.0;

        try {
            \App\Models\AiVolani::create([
                'model' => $this->model,
                'ucel' => $this->kontext['ucel'] ?? 'akce_extrakce',
                'zdroj_id' => $this->kontext['zdroj_id'] ?? null,
                'akce_id' => $this->kontext['akce_id'] ?? null,
                'uzivatel_id' => $this->kontext['uzivatel_id'] ?? null,
                'scraping_log_id' => $this->kontext['scraping_log_id'] ?? null,
                'input_tokens' => $input,
                'output_tokens' => $output,
                'cache_creation_tokens' => $cacheWrite,
                'cache_read_tokens' => $cacheRead,
                'cena_usd' => $cena,
                'uspech' => $uspech,
                'chyba' => $chyba,
                'vytvoreno' => now(),
            ]);
        } catch (\Exception $e) {
            // Neblokovat scraping kvůli logování
            Log::warning("Cost tracking save failed: {$e->getMessage()}");
        }
    }

    /**
     * Je JSON-LD data dostatečně kompletní? (klíčové pole vyplněné)
     * Pokud ano, AI fallback není potřeba.
     *
     * Striktní kritéria — Stánkař a podobné weby vrací MINIMUM (nazev/datum/kraj)
     * bez popisu, organizátora, kontaktů. V takovém případě MUSÍME zavolat AI,
     * jinak nám chybí 90% informací.
     */
    protected function jeKompletni(array $data): bool
    {
        if (empty($data['nazev']) || empty($data['datum_od'])) {
            return false;
        }

        // Misto musí být skutečné místo, ne jen kraj (Stánkař vrací location.name = kraj)
        $misto = trim((string) ($data['misto'] ?? ''));
        $maMisto = $misto !== '' && !str_contains(mb_strtolower($misto), 'kraj');

        // Aspoň 1 z těchto musí být vyplněno (popis, organizator, kontakty, GPS, adresa)
        $maPopis = !empty($data['popis']) && mb_strlen((string) $data['popis']) >= 20;
        $maOrg = !empty($data['organizator']);
        $maKontakt = !empty($data['kontakt_email']) || !empty($data['kontakt_telefon']);
        $maGps = !empty($data['gps_lat']) && !empty($data['gps_lng']);
        $maAdresu = !empty($data['adresa']);

        // Vyžadujeme: misto + (aspoň 2 z bohatších polí)
        $bohataPole = ($maPopis ? 1 : 0) + ($maOrg ? 1 : 0) + ($maKontakt ? 1 : 0)
            + ($maGps ? 1 : 0) + ($maAdresu ? 1 : 0);

        return $maMisto && $bohataPole >= 2;
    }

    /** Vypočítat velikostní skóre (0-100) z extrahovaných dat. */
    public function vypocetVelikostSkore(array $data): int
    {
        $skore = 0;

        // Typ akce → klíčová slova (+30)
        $typyVelke = ['pout', 'slavnosti',
                      'food_festival', 'obrani',
                      'trhy_jarmarky',
                      'festival'];
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
