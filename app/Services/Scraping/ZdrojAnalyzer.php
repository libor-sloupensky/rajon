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
        } else {
            $result['chyby'][] = 'Nelze stáhnout hlavní HTML.';
        }

        return $result;
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

    /** Stáhnout sitemap a vrátit seznam URL akcí. */
    public function seznamUrlZSitemap(string $sitemapUrl, string $urlPatternDetail = '/akce/'): array
    {
        $urls = [];
        $pattern = $this->normalizujPattern($urlPatternDetail);

        try {
            $response = Http::withHeaders(['User-Agent' => self::UA])
                ->timeout(30)
                ->get($sitemapUrl);

            if (!$response->successful()) {
                return $urls;
            }

            // Některé servery (např. stankar.cz) posílají whitespace/BOM před <?xml
            $body = ltrim($response->body(), "\xEF\xBB\xBF \t\n\r\0\x0B");
            $xml = @simplexml_load_string($body);
            if (!$xml) return $urls;

            // Sitemap index → načti všechny sub-sitemapy
            if (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sm) {
                    $subUrl = (string) $sm->loc;
                    $urls = array_merge($urls, $this->seznamUrlZSitemap($subUrl, $urlPatternDetail));
                }
                return $urls;
            }

            // Regulér sitemap
            if (isset($xml->url)) {
                foreach ($xml->url as $u) {
                    $loc = (string) $u->loc;
                    if ($pattern === '*' || str_contains($loc, $pattern)) {
                        $urls[] = $loc;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Sitemap parse failed: {$sitemapUrl} — {$e->getMessage()}");
        }

        return $urls;
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
