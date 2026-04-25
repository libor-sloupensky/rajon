<?php

namespace App\Services\Scraping;

use App\Models\Akce;
use App\Models\AkceZdroj;
use App\Models\ScrapingLog;
use App\Models\Zdroj;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrace scrapingu — pro jeden zdroj:
 * 1. Stáhne seznam URL ze sitemap
 * 2. Pro každou URL extrahuje detail (AkceExtractor)
 * 3. Filtruje podle regionu (7 krajů východní ČR)
 * 4. Klasifikuje velikost akce
 * 5. Dedupluje a ukládá do DB
 * 6. Loguje výsledek
 */
class ScrapingPipeline
{
    public function __construct(
        protected ZdrojAnalyzer $analyzer,
        protected AkceExtractor $extractor,
        protected AkceMatcher $matcher,
        protected AkceMerger $merger,
        protected LokalizaceResolver $lokalizace,
        protected ListingPaginator $paginator,
    ) {}

    /**
     * Spustí scraping pro daný zdroj.
     * @param int|null $limit Maximální počet detailů (pro testování)
     */
    public function scrapujZdroj(Zdroj $zdroj, ?int $limit = null, bool $pouzeRegion = true): ScrapingLog
    {
        $log = ScrapingLog::create([
            'zdroj_id' => $zdroj->id,
            'zacatek' => now(),
            'stav' => 'probiha',
            'limit_pouzity' => $limit ?: 0,
            'vytvoreno' => now(),
        ]);

        $chyby = [];
        $statistiky = ['podle_kraje' => [], 'podle_typu' => [], 'podle_velikosti' => []];

        try {
            // 1. Sitemap → seznam URL
            $urls = $this->ziskejUrls($zdroj);
            $log->pocet_nalezenych = count($urls);

            // 2. Pre-filtry (před AI calls — šetří tokeny):
            //    a) URL s rokem < aktuální v slugu (např. "vinobrani-2018")
            //    b) URL co už máme v DB jako akce s datum_od < dnes
            $urlsZneresene = count($urls);
            $urls = $this->predFiltrujUrls($urls, $zdroj);
            $log->pocet_preskocenych = $urlsZneresene - count($urls);  // pre-filter skip

            if ($limit) {
                $urls = array_slice($urls, 0, $limit);
            }

            // 2. Pro každou URL
            foreach ($urls as $url) {
                try {
                    $vysledek = $this->zpracujAkci($zdroj, $url, $pouzeRegion, $statistiky);

                    match ($vysledek['stav']) {
                        'novy' => $log->increment('pocet_novych'),
                        'aktualizovany' => $log->increment('pocet_aktualizovanych'),
                        'preskoceny' => $log->increment('pocet_preskocenych'),
                        'chyba' => (function () use ($log, $vysledek, &$chyby) {
                            $log->increment('pocet_chyb');
                            $chyby[] = $vysledek['chyba'] ?? 'Unknown';
                        })(),
                        default => null,
                    };
                } catch (\Exception $e) {
                    $log->increment('pocet_chyb');
                    $chyby[] = "URL {$url}: {$e->getMessage()}";
                    Log::warning("Scraping error {$url}: {$e->getMessage()}");
                }
            }

            $log->stav = $log->pocet_chyb > 0 && $log->pocet_chyb === count($urls) ? 'chyba'
                : ($log->pocet_chyb > 0 ? 'castecne' : 'uspech');

            // Aktualizace zdroje
            $zdroj->update([
                'posledni_scraping' => now(),
                'pocet_akci' => $zdroj->akce()->count(),
                'posledni_chyby' => $chyby ? implode("\n", array_slice($chyby, 0, 10)) : null,
            ]);
        } catch (\Exception $e) {
            $log->stav = 'chyba';
            $chyby[] = "Fatal: {$e->getMessage()}";
            Log::error("Scraping pipeline failed: {$e->getMessage()}");
        } finally {
            $log->konec = now();
            $log->chyby_detail = $chyby ? implode("\n", array_slice($chyby, 0, 50)) : null;
            $log->statistiky = $statistiky;
            $log->save();
        }

        return $log;
    }

    /**
     * Získej seznam URL ke scrapingu — generická logika fungující pro libovolný katalog:
     *   1. Pokud má zdroj sitemap_url → použít sitemap (nejrychlejší)
     *   2. Jinak: ListingPaginator projde listing s paginací (pro zdroje bez sitemapu)
     *   3. Poslední fallback: jen odkazy z hlavní stránky
     *
     * Vždy vrací absolutní URL.
     */
    protected function ziskejUrls(Zdroj $zdroj): array
    {
        $baseUrl = $this->extractBaseUrl($zdroj->url);
        $detailPattern = $zdroj->url_pattern_detail ?: '*';

        if ($zdroj->sitemap_url) {
            $urls = $this->analyzer->seznamUrlZSitemap($zdroj->sitemap_url, $detailPattern);
        } else {
            // Generický paginator — funguje pro libovolný katalog s listingem
            $listingUrl = $zdroj->url_pattern_list
                ? $this->absolutniUrl($zdroj->url_pattern_list, $baseUrl)
                : $zdroj->url;

            $urls = $this->paginator->sbirej($listingUrl, $detailPattern, $baseUrl);

            // Poslední fallback — pokud paginator nenašel nic, jen analyzátor hlavní stránky
            if (empty($urls)) {
                $analyza = $this->analyzer->analyzuj($zdroj->url);
                $urls = $analyza['struktura']['odkazy_akci'] ?? [];
            }
        }

        // Převést všechny relativní URL na absolutní
        return array_map(fn ($u) => $this->absolutniUrl($u, $baseUrl), $urls);
    }

    /**
     * Pre-filtr URL — zahodí ty, které:
     *   a) Mají v slugu rok starší než aktuální rok (např. "vinobrani-2018")
     *   b) Už máme v DB jako akce s datum_do < dnes (proběhly)
     *
     * Tím šetříme AI tokeny — neděláme drahé extrakce na akcích, které už nás nezajímají.
     */
    protected function predFiltrujUrls(array $urls, Zdroj $zdroj): array
    {
        $aktualniRok = (int) date('Y');
        $dnes = now()->toDateString();

        // (a) URL pattern filter — slug obsahuje rok < aktuální
        $urls = array_values(array_filter($urls, function ($url) use ($aktualniRok) {
            // Hledáme rok 20XX v URL — buď samostatně oddělený pomlčkami nebo lomítky
            if (preg_match('/[\/-](20\d{2})(?:[\/_-]|$)/', $url, $m)) {
                $rok = (int) $m[1];
                if ($rok < $aktualniRok) {
                    return false;  // skip
                }
            }
            return true;
        }));

        // (b) DB filter — URL už máme s akcí, jejíž datum proběhlo
        $existujici = \App\Models\AkceZdroj::query()
            ->where('zdroj_id', $zdroj->id)
            ->whereIn('url', $urls)
            ->join('akce', 'akce_zdroje.akce_id', '=', 'akce.id')
            ->where(function ($q) use ($dnes) {
                $q->whereNotNull('akce.datum_do')
                  ->whereDate('akce.datum_do', '<', $dnes);
            })
            ->orWhere(function ($q) use ($dnes, $zdroj, $urls) {
                $q->where('akce_zdroje.zdroj_id', $zdroj->id)
                  ->whereIn('akce_zdroje.url', $urls)
                  ->whereNull('akce.datum_do')
                  ->whereNotNull('akce.datum_od')
                  ->whereDate('akce.datum_od', '<', $dnes);
            })
            ->pluck('akce_zdroje.url')
            ->all();

        $existujiciSet = array_flip($existujici);
        return array_values(array_filter($urls, fn ($u) => !isset($existujiciSet[$u])));
    }

    /** Resolvuj URL proti base — vrátí absolutní URL. */
    protected function absolutniUrl(string $url, string $baseUrl): string
    {
        // Už absolutní
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        // Protocol-relative
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        // Absolutní path — přidat base
        if (str_starts_with($url, '/')) {
            return rtrim($baseUrl, '/') . $url;
        }
        // Relativní — přidat base + /
        return rtrim($baseUrl, '/') . '/' . $url;
    }

    protected function extractBaseUrl(string $url): string
    {
        $parsed = parse_url($url);
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
    }

    /** Zpracuj jednu akci — extrakce, filtry, uložení. */
    protected function zpracujAkci(Zdroj $zdroj, string $url, bool $pouzeRegion, array &$statistiky): array
    {
        // 1. Fetch HTML
        $html = $this->fetchHtml($url);
        if (!$html) {
            return ['stav' => 'chyba', 'chyba' => "Nelze stáhnout {$url}"];
        }

        // 2. AI extrakce
        $data = $this->extractor->extrahuj($html, $url);
        if (!$data) {
            return ['stav' => 'chyba', 'chyba' => "AI extrakce selhala {$url}"];
        }

        // 2b. Filter minulých akcí — neukládat akce co už proběhly
        $datumOd = $data['datum_od'] ?? null;
        $datumDo = $data['datum_do'] ?? $datumOd;
        if ($datumDo) {
            try {
                $konecAkce = new \DateTime($datumDo);
                if ($konecAkce < new \DateTime('today')) {
                    return ['stav' => 'preskoceny', 'duvod' => "Akce už proběhla ({$datumDo})"];
                }
            } catch (\Exception) { /* ignoruj chyby parsování */ }
        }

        // 3. Lokalizace — z AI textových názvů zjistíme kraj_id + okres_id z DB.
        // Preferenčně přes okres (přesnější), kraj se odvodí.
        $loc = $this->lokalizace->resolve($data['kraj'] ?? null, $data['okres'] ?? null);
        $data['kraj_id'] = $loc['kraj_id'];
        $data['okres_id'] = $loc['okres_id'];

        // Pokud máme kraj_id, normalizuj i textový kraj (ať máme konzistentní hodnotu)
        if ($loc['kraj_id']) {
            $data['kraj'] = \App\Models\Kraj::find($loc['kraj_id'])?->nazev ?? $data['kraj'];
        }

        // 4. Statistiky
        $krajStat = $data['kraj'] ?? 'neznámý';
        $statistiky['podle_kraje'][$krajStat] = ($statistiky['podle_kraje'][$krajStat] ?? 0) + 1;

        // 5. Region filter — přes kraj_id (DB), s fallbackem na text
        if ($pouzeRegion) {
            $krajSlug = $loc['kraj_id'] ? (\App\Models\Kraj::find($loc['kraj_id'])?->slug) : null;
            $jeVRegionu = $krajSlug
                ? in_array($krajSlug, Akce::KRAJE_VYCHOD_SLUGS, true)
                : in_array($data['kraj'] ?? null, Akce::KRAJE_VYCHOD, true);

            if (!$jeVRegionu) {
                return ['stav' => 'preskoceny', 'duvod' => "Mimo region: {$krajStat}"];
            }
        }

        // 5. Velikostní scoring
        $skore = $this->extractor->vypocetVelikostSkore($data);
        $stav = $this->extractor->urciStavVelikosti($skore);

        $statistiky['podle_velikosti'][$stav] = ($statistiky['podle_velikosti'][$stav] ?? 0) + 1;
        $statistiky['podle_typu'][$data['typ'] ?? 'jiny'] = ($statistiky['podle_typu'][$data['typ'] ?? 'jiny'] ?? 0) + 1;

        // 6. Deduplikace + uložení (fuzzy match + merge)
        $puvodniPocetKonfliktu = 0;
        if ($existujici = $this->matcher->najdiExistujici($data)) {
            $puvodniPocetKonfliktu = count($existujici->konflikty ?? []);
        }

        $akce = $this->ulozAkci($data, $url, $skore, $stav, $zdroj);
        $novy = $akce->wasRecentlyCreated;

        $this->ulozAkceZdroj($akce, $zdroj, $url, $data);

        // 7. Fallback na web pořadatele — pokud po merge přibyly konflikty a akce má web_url
        $nyniPocetKonfliktu = count($akce->konflikty ?? []);
        if (!$novy && $nyniPocetKonfliktu > $puvodniPocetKonfliktu && !empty($akce->web_url)) {
            $vyresenoZPoradatele = $this->zkusitVyresitZPoradatele($akce);
            if ($vyresenoZPoradatele) {
                return ['stav' => 'aktualizovany', 'akce_id' => $akce->id, 'poznamka' => 'konflikty_vyreseny_poradatelem'];
            }
        }

        return ['stav' => $novy ? 'novy' : 'aktualizovany', 'akce_id' => $akce->id];
    }

    /**
     * Pokus se vyřešit konflikty stažením webu pořadatele.
     * Web pořadatele má trust 95+ (jen manual je 100), takže jeho data přepíšou konflikty.
     */
    protected function zkusitVyresitZPoradatele(Akce $akce): bool
    {
        $webPoradatele = $akce->web_url;
        if (empty($webPoradatele)) return false;

        // Už jsme scrapovali tento web? Pokud ano, nedělat to znovu
        $existingScrape = $akce->akceZdroje()->where('url', $webPoradatele)->exists();
        if ($existingScrape) return false;

        // Fetch + AI extrakce
        $html = $this->fetchHtml($webPoradatele);
        if (!$html) return false;

        $data = $this->extractor->extrahuj($html, $webPoradatele);
        if (!$data) return false;

        // Stejné sanitace jako u běžného merge (typ enum, max délky)
        $data = $this->oriznStringy($data);

        // Vytvořit dočasný "virtuální" zdroj s flagem je_web_poradatele=true
        // Nebo použít existující zdroj ale flagovat URL jako od pořadatele
        $virtualZdroj = new \App\Models\Zdroj([
            'nazev' => 'Web pořadatele (' . parse_url($webPoradatele, PHP_URL_HOST) . ')',
            'url' => $webPoradatele,
            'cms_typ' => 'web_poradatele',
            'je_web_poradatele' => true,
        ]);

        // Merge — AkceMerger detekuje web_poradatele (buď flag, nebo doména)
        $this->merger->merge($akce, $data, $virtualZdroj, $webPoradatele);

        // Uložit do akce_zdroje s flagem
        \App\Models\AkceZdroj::updateOrCreate(
            ['zdroj_id' => $akce->zdroj_id, 'url' => $webPoradatele],
            [
                'akce_id' => $akce->id,
                'je_od_poradatele' => true,
                'surova_data' => $data,
                'posledni_ziskani' => now(),
            ]
        );

        return true;
    }

    protected function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; RajonBot/1.0; +https://rajon.tuptudu.cz/)',
                'Accept-Language' => 'cs,en;q=0.5',
            ])->timeout(30)->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (\Exception) {
            return null;
        }
    }

    /** Ulož nebo aktualizuj akci — fuzzy matching + field-level merge. */
    protected function ulozAkci(array $data, string $url, int $skore, string $stav, Zdroj $zdroj): Akce
    {
        $data['typ'] = $this->normalizujTyp($data['typ'] ?? 'jiny');
        $data['_skore'] = $skore;
        $data['_stav'] = $stav;

        // Oříznout textová pole na max délky DB (AI vrací libovolné délky)
        $data = $this->oriznStringy($data);

        // 1. Fuzzy match na existující akci
        $existing = $this->matcher->najdiExistujici($data);

        if ($existing) {
            // Update přes AkceMerger (respektuje manuální pole + trust)
            $this->merger->merge($existing, $data, $zdroj, $url);
            return $existing;
        }

        // 2. Nová akce — vytvořit + inicializovat pole_zdroje
        $nazev = $data['nazev'] ?? 'Bez názvu';
        $datumOd = $data['datum_od'] ?? null;
        $slug = $this->vytvorUnikatniSlug($nazev, $datumOd, null);

        $payload = [
            'nazev' => $nazev,
            'slug' => $slug,
            'typ' => $data['typ'],
            'popis' => $data['popis'] ?? null,
            'datum_od' => $datumOd,
            'datum_do' => $data['datum_do'] ?? null,
            'misto' => $data['misto'] ?? null,
            'adresa' => $data['adresa'] ?? null,
            'gps_lat' => $data['gps_lat'] ?? null,
            'gps_lng' => $data['gps_lng'] ?? null,
            'okres' => $data['okres'] ?? null,
            'kraj' => $data['kraj'] ?? null,
            'kraj_id' => $data['kraj_id'] ?? null,
            'okres_id' => $data['okres_id'] ?? null,
            'organizator' => $data['organizator'] ?? null,
            'kontakt_email' => $data['kontakt_email'] ?? null,
            'kontakt_telefon' => $data['kontakt_telefon'] ?? null,
            'web_url' => $data['web_url'] ?? null,
            'zdroj_url' => $url,
            'zdroj_typ' => 'scraping',
            'zdroj_id' => $zdroj->id,
            'vstupne' => $data['vstupne'] ?? null,
            'externi_hash' => hash('sha256', json_encode($data)),
            'velikost_skore' => $skore,
            'velikost_stav' => $stav,
            'velikost_info' => $data['velikost_info']
                ? "[{$zdroj->nazev}] " . $data['velikost_info']
                : null,
            'velikost_signaly' => $data['velikost_signaly'] ?? null,
            'stav' => 'navrh',
        ];

        $akce = Akce::create($payload);

        // Inicializace pole_zdroje pro všechna vyplněná pole
        $this->merger->inicializujZdroje($akce, $zdroj);

        // Ročníkové propojení — auto při similarity ≥ threshold, jinak návrh adminovi
        $navrhy = $this->matcher->navrhniPropojeniRocniku($data);
        if (!empty($navrhy)) {
            $autoThreshold = (int) config('scraping.auto_propojeni_similarity', 90);
            $nejlepsi = $navrhy[0];

            if (!empty($nejlepsi['similarity']) && $nejlepsi['similarity'] >= $autoThreshold) {
                // Automatické propojení na nejpodobnější starší ročník
                $akce->propojena_s_akci_id = $nejlepsi['akce_id'];
                $akce->navrh_propojeni = array_slice($navrhy, 1);  // ponechat další návrhy
            } else {
                $akce->navrh_propojeni = $navrhy;
            }
            $akce->save();
        }

        return $akce;
    }

    /** Ulož záznam do akce_zdroje (many-to-many). */
    protected function ulozAkceZdroj(Akce $akce, Zdroj $zdroj, string $url, array $data): void
    {
        AkceZdroj::updateOrCreate(
            ['zdroj_id' => $zdroj->id, 'url' => $url],
            [
                'akce_id' => $akce->id,
                'externi_id' => $data['externi_id'] ?? null,
                'surova_data' => $data,
                'posledni_ziskani' => now(),
            ]
        );
    }

    /**
     * Oříznout textová pole na max délky sloupců v DB.
     * AI občas vrací delší hodnoty (např. telefon s poznámkou "volat 9-17h").
     */
    protected function oriznStringy(array $data): array
    {
        $max = [
            'nazev' => 255,
            'misto' => 255,
            'adresa' => 255,
            'okres' => 100,
            'kraj' => 100,
            'organizator' => 255,
            'kontakt_email' => 255,
            'kontakt_telefon' => 50,    // DB má VARCHAR(20), ale AI vrací delší → ořez na 50, v DB pak na 20
            'web_url' => 500,
            'zdroj_url' => 500,
            'vstupne' => 100,
            'cas' => 100,
        ];

        foreach ($max as $pole => $limit) {
            if (!empty($data[$pole]) && is_string($data[$pole]) && mb_strlen($data[$pole]) > $limit) {
                $data[$pole] = mb_substr($data[$pole], 0, $limit);
            }
        }

        // Explicitně validní enum typ
        if (isset($data['typ'])) {
            $data['typ'] = $this->normalizujTyp((string) $data['typ']);
        }

        return $data;
    }

    /** Normalizace typu akce na hodnoty enum v DB. */
    protected function normalizujTyp(string $typ): string
    {
        $mapping = [
            'pout' => 'pout',
            'food_festival' => 'food_festival',
            'vinobrani' => 'vinobrani',
            'dynobrani' => 'dynobrani',
            'farmarske_trhy' => 'farmarske_trhy',
            'vanocni_trhy' => 'vanocni_trhy',
            'jarmark' => 'jarmark',
            'festival' => 'festival',
            'hudebni_festival' => 'festival',
            'historicke_slavnosti' => 'slavnosti',
            'folklor' => 'slavnosti',
            'hody' => 'slavnosti',
            'dny_mesta' => 'slavnosti',
            'obecni_slavnosti' => 'slavnosti',
            'velikonocni_trhy' => 'jarmark',
        ];
        return $mapping[$typ] ?? 'jiny';
    }

    protected function vytvorUnikatniSlug(string $nazev, ?string $datumOd, ?int $existingId): string
    {
        $rok = $datumOd ? date('Y', strtotime($datumOd)) : date('Y');
        $base = Str::slug($nazev . '-' . $rok);
        $slug = $base;
        $counter = 2;

        while (Akce::where('slug', $slug)->when($existingId, fn ($q) => $q->where('id', '!=', $existingId))->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
