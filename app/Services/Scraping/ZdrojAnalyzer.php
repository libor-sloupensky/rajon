<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Analyzátor zdroje — pro zadané URL zjistí:
 * - robots.txt a sitemap URL
 * - typ CMS (WordPress/MEC, Joomla, custom)
 * - URL vzor pro list/detail
 * - strukturu HTML (pro pozdější extrakci)
 */
class ZdrojAnalyzer
{
    protected const UA = 'Mozilla/5.0 (compatible; RajonBot/1.0; +https://rajon.tuptudu.cz/)';

    public function analyzuj(string $url): array
    {
        $result = [
            'url' => $url,
            'base_url' => $this->extractBaseUrl($url),
            'robots_url' => null,
            'sitemap_url' => null,
            'cms_typ' => null,
            'url_pattern_list' => null,
            'url_pattern_detail' => null,
            'pocet_url_v_sitemap' => 0,
            'ma_jsonld_event' => false,
            'html_ukazka' => null,
            'struktura' => [],
            'chyby' => [],
        ];

        // 1. Robots.txt
        $robotsData = $this->nactiRobots($result['base_url']);
        $result['robots_url'] = $robotsData['url'];
        if ($robotsData['sitemap']) {
            $result['sitemap_url'] = $robotsData['sitemap'];
        }

        // 2. Sitemap (zkusit standardní cesty, pokud nebyla v robots)
        if (!$result['sitemap_url']) {
            $result['sitemap_url'] = $this->najdiSitemap($result['base_url']);
        }

        // 3. Hlavní HTML
        $html = $this->fetchHtml($url);
        if ($html) {
            $result['html_ukazka'] = mb_substr($html, 0, 3000);
            $result['cms_typ'] = $this->detekujCms($html);
            $result['struktura'] = $this->extrahujStrukturu($html);
            $result['ma_jsonld_event'] = !empty($result['struktura']['jsonld_events']);
        } else {
            $result['chyby'][] = 'Nelze stáhnout hlavní HTML.';
        }

        // 4. Auto-detekce URL pattern detailu — ze sitemap (pokud ji máme)
        if ($result['sitemap_url']) {
            $sampleUrls = $this->seznamUrlZSitemap($result['sitemap_url'], '*');
            $result['pocet_url_v_sitemap'] = count($sampleUrls);
            if (!empty($sampleUrls)) {
                $result['url_pattern_detail'] = $this->detekujUrlPattern($sampleUrls);
            }
        }
        // Fallback: zkus detekovat z odkazů na hlavní stránce
        if (empty($result['url_pattern_detail']) && !empty($result['struktura']['odkazy_akci'])) {
            $result['url_pattern_detail'] = $this->detekujUrlPattern($result['struktura']['odkazy_akci']);
        }

        return $result;
    }

    /**
     * Auto-detekce URL pattern detailu — najde nejčastější path prefix mezi URL.
     * Např. ["/akce/foo", "/akce/bar", "/contact"] → "/akce/"
     */
    protected function detekujUrlPattern(array $urls): ?string
    {
        if (empty($urls)) return null;

        $prefixCount = [];
        foreach ($urls as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?? '';
            // Vezmi první 1-2 segmenty cesty
            if (preg_match('#^(/[\w\-]+/)#', $path, $m)) {
                $prefixCount[$m[1]] = ($prefixCount[$m[1]] ?? 0) + 1;
            }
            if (preg_match('#^(/[\w\-]+/[\w\-]+/)#', $path, $m)) {
                $prefixCount[$m[1]] = ($prefixCount[$m[1]] ?? 0) + 1;
            }
        }

        if (empty($prefixCount)) return null;

        // Preferuj prefixy které vypadají jako "akce/events/event/akcie/..."
        $klicovaSlova = ['akce', 'events', 'event', 'akcie', 'kalendar', 'calendar', 'pout', 'festival', 'program', 'trhy'];
        $skore = [];
        foreach ($prefixCount as $prefix => $cnt) {
            $skore[$prefix] = $cnt;
            foreach ($klicovaSlova as $kw) {
                if (str_contains(mb_strtolower($prefix), $kw)) {
                    $skore[$prefix] *= 5;  // boost
                    break;
                }
            }
        }

        arsort($skore);
        return array_key_first($skore);
    }

    /** robots.txt → najdi Sitemap: direktivy. */
    protected function nactiRobots(string $baseUrl): array
    {
        $robotsUrl = rtrim($baseUrl, '/') . '/robots.txt';

        try {
            $response = Http::withHeaders(['User-Agent' => self::UA])
                ->timeout(15)
                ->get($robotsUrl);

            if (!$response->successful()) {
                return ['url' => $robotsUrl, 'sitemap' => null];
            }

            $body = $response->body();
            $sitemap = null;

            if (preg_match('/^Sitemap:\s*(.+)$/mi', $body, $m)) {
                $sitemap = trim($m[1]);
            }

            return ['url' => $robotsUrl, 'sitemap' => $sitemap];
        } catch (\Exception $e) {
            Log::warning("Robots.txt fetch failed: {$robotsUrl} — {$e->getMessage()}");
            return ['url' => $robotsUrl, 'sitemap' => null];
        }
    }

    /** Najdi sitemap na standardních cestách. */
    protected function najdiSitemap(string $baseUrl): ?string
    {
        $cesty = [
            '/sitemap.xml',
            '/sitemap_index.xml',
            '/wp-sitemap.xml',
            '/sitemap-index.xml',
        ];

        foreach ($cesty as $cesta) {
            $url = rtrim($baseUrl, '/') . $cesta;
            try {
                $response = Http::withHeaders(['User-Agent' => self::UA])
                    ->timeout(10)
                    ->get($url);

                if ($response->successful() && str_contains($response->body(), '<urlset') || str_contains($response->body(), '<sitemapindex')) {
                    return $url;
                }
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }

    /** Detekce CMS z HTML. */
    protected function detekujCms(string $html): string
    {
        // WordPress + Modern Events Calendar
        if (str_contains($html, 'wp-content') && str_contains(strtolower($html), 'modern-events-calendar')) {
            return 'wordpress_mec';
        }

        // WordPress obecně
        if (str_contains($html, 'wp-content') || str_contains($html, 'wp-includes')) {
            return 'wordpress';
        }

        // Joomla
        if (str_contains($html, 'Joomla!') || str_contains($html, '/templates/') || str_contains($html, 'joomla')) {
            return 'joomla';
        }

        // Kudyznudy (specifický portál)
        if (str_contains($html, 'kudyznudy')) {
            return 'kudyznudy';
        }

        // Webtržiště
        if (str_contains($html, 'webtrziste')) {
            return 'webtrziste';
        }

        // Drupal
        if (str_contains($html, 'drupal-settings-json')) {
            return 'drupal';
        }

        return 'custom';
    }

    /** Extrahuj základní strukturu z HTML (odkazy, meta, JSON-LD). */
    protected function extrahujStrukturu(string $html): array
    {
        $struktura = [
            'title' => null,
            'meta_description' => null,
            'jsonld_events' => [],
            'odkazy_akci' => [],
        ];

        // Title
        if (preg_match('/<title[^>]*>(.+?)<\/title>/is', $html, $m)) {
            $struktura['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Meta description
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $struktura['meta_description'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // JSON-LD Event (schema.org)
        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.+?)<\/script>/is', $html, $mAll)) {
            foreach ($mAll[1] as $json) {
                $data = json_decode(trim($json), true);
                if (!$data) continue;

                $items = isset($data['@graph']) ? $data['@graph'] : [$data];
                foreach ($items as $item) {
                    if (!is_array($item)) continue;
                    $type = $item['@type'] ?? null;
                    if ($type === 'Event' || (is_array($type) && in_array('Event', $type))) {
                        $struktura['jsonld_events'][] = $item;
                    }
                }
            }
        }

        // Odkazy na detaily akcí (heuristika)
        if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            foreach ($m[1] as $href) {
                if (preg_match('#/(akce|events|akcie|event|kalendar)/[\w\-]+#i', $href)) {
                    $struktura['odkazy_akci'][] = $href;
                }
            }
            $struktura['odkazy_akci'] = array_values(array_unique($struktura['odkazy_akci']));
        }

        return $struktura;
    }

    /** Stáhnout HTML. */
    protected function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => self::UA,
                'Accept-Language' => 'cs,en;q=0.5',
            ])->timeout(30)->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            Log::warning("Fetch failed: {$url} — {$e->getMessage()}");
        }
        return null;
    }

    protected function extractBaseUrl(string $url): string
    {
        $parsed = parse_url($url);
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
    }

    /**
     * Stáhnout sitemap a vrátit seznam URL akcí.
     * @return array<int, string> seznam absolutních URL (zpětná kompatibilita)
     */
    public function seznamUrlZSitemap(string $sitemapUrl, string $urlPatternDetail = '/akce/'): array
    {
        $sLastmody = $this->seznamUrlLastmodZSitemap($sitemapUrl, $urlPatternDetail);
        return array_keys($sLastmody);
    }

    /**
     * Stáhnout sitemap a vrátit pole [url => lastmod_string|null].
     * lastmod je z <lastmod> tagu sitemapu — pokud chybí, hodnota je null.
     *
     * @return array<string, string|null>
     */
    public function seznamUrlLastmodZSitemap(string $sitemapUrl, string $urlPatternDetail = '/akce/'): array
    {
        $vysledek = [];
        $pattern = $this->normalizujPattern($urlPatternDetail);

        try {
            $response = Http::withHeaders(['User-Agent' => self::UA])
                ->timeout(30)
                ->get($sitemapUrl);

            if (!$response->successful()) {
                return $vysledek;
            }

            $body = ltrim($response->body(), "\xEF\xBB\xBF \t\n\r\0\x0B");
            $xml = @simplexml_load_string($body);
            if (!$xml) return $vysledek;

            // Sitemap index → načti všechny sub-sitemapy
            if (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sm) {
                    $subUrl = (string) $sm->loc;
                    $vysledek = array_merge($vysledek, $this->seznamUrlLastmodZSitemap($subUrl, $urlPatternDetail));
                }
                return $vysledek;
            }

            // Regulér sitemap
            if (isset($xml->url)) {
                foreach ($xml->url as $u) {
                    $loc = (string) $u->loc;
                    if ($pattern !== '*' && !str_contains($loc, $pattern)) continue;

                    $lastmod = isset($u->lastmod) ? (string) $u->lastmod : null;
                    $vysledek[$loc] = $lastmod ?: null;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Sitemap parse failed: {$sitemapUrl} — {$e->getMessage()}");
        }

        return $vysledek;
    }

    /**
     * Normalizuj URL pattern — strip placeholder `{slug}`, `{id}` apod.
     * "/akce/{slug}" → "/akce/", "/trhy/akce/program.php?id={id}" → "/trhy/akce/program.php"
     */
    protected function normalizujPattern(string $pattern): string
    {
        if ($pattern === '*' || $pattern === '') return '*';

        // Odstranit vše od první `{` dál — včetně query stringu s placeholderem
        if (($pos = strpos($pattern, '{')) !== false) {
            $pattern = rtrim(substr($pattern, 0, $pos), '?&=/');
            // Pokud zůstala jen cesta bez query, ponechat lomítko
            if (!str_contains($pattern, '?')) {
                $pattern = rtrim($pattern, '/') . '/';
            }
        }

        return $pattern;
    }
}
