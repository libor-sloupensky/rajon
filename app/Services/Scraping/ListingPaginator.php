<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generický crawler pro zdroje bez sitemap.xml.
 *
 * Iteruje stránkováním (paginací) a sbírá odkazy na detaily akcí.
 * Detekce paginace (auto):
 *   - "Další stránka" / "Next" tlačítko (rel="next" nebo href="?page=N")
 *   - "?page=N", "?strana=N", "?p=N", "&pg=N", "/page/N/"
 *   - Číselné odkazy paginace
 *
 * Funguje pro libovolný katalog akcí — žádný per-zdroj kód není potřeba.
 */
class ListingPaginator
{
    protected const UA = 'Mozilla/5.0 (compatible; RajonBot/1.0; +https://rajon.tuptudu.cz/)';
    protected const MAX_STRANEK = 100;       // bezpečnostní limit
    protected const MAX_DETAIL_URL = 5000;   // celkový limit posbíraných URL

    /**
     * Sbírej odkazy na detaily akcí ze zdroje s paginací.
     *
     * @param string $listingUrl  Vstupní URL listingu (např. /trhy/akce/)
     * @param string $detailPattern  String filtr pro detail URL (např. "/akce/", "/events/", "program.php?id=")
     * @param string $baseUrl  Pro resolve relativních URL
     * @return array<int,string> seznam absolutních URL detailů
     */
    public function sbirej(string $listingUrl, string $detailPattern, string $baseUrl): array
    {
        $detailUrls = [];
        $videno = [];                   // anti-cycle ochrana
        $kFronte = [$listingUrl];

        $i = 0;
        while ($kFronte && $i < self::MAX_STRANEK && count($detailUrls) < self::MAX_DETAIL_URL) {
            $url = array_shift($kFronte);
            $kanonickaUrl = $this->kanonickaUrl($url);
            if (isset($videno[$kanonickaUrl])) continue;
            $videno[$kanonickaUrl] = true;

            $html = $this->fetchHtml($url);
            if (!$html) {
                $i++;
                continue;
            }

            // 1. Z HTML vytáhnout všechny <a href> a roztřídit
            $odkazy = $this->extrahujOdkazy($html, $url, $baseUrl);

            foreach ($odkazy as $href) {
                if ($this->jeDetailUrl($href, $detailPattern)) {
                    $detailUrls[$href] = true;       // unique
                } elseif ($this->jeListingUrl($href, $listingUrl)) {
                    if (!isset($videno[$this->kanonickaUrl($href)])) {
                        $kFronte[] = $href;
                    }
                }
            }

            $i++;
        }

        return array_keys($detailUrls);
    }

    /** Heuristika: je URL stránkováním (paginací) listingu? */
    protected function jeListingUrl(string $url, string $listingUrl): bool
    {
        $listingPath = parse_url($listingUrl, PHP_URL_PATH) ?? '/';

        // Stejná base path
        $urlPath = parse_url($url, PHP_URL_PATH) ?? '';
        if (!str_starts_with($urlPath, rtrim($listingPath, '/'))) {
            return false;
        }

        // Obsahuje paginační patterns
        return (bool) preg_match('#(\?|&)(page|strana|p|pg|pageNum|pn)=\d+#i', $url)
            || (bool) preg_match('#/page/\d+/?#i', $url)
            || (bool) preg_match('#/strana/\d+/?#i', $url)
            || $url === $listingUrl;  // úvodní stránka
    }

    /** Heuristika: je URL detail jedné akce? */
    protected function jeDetailUrl(string $url, string $detailPattern): bool
    {
        if ($detailPattern === '*' || $detailPattern === '') {
            // Bez explicitního patternu — detail typically obsahuje slug nebo id
            return (bool) preg_match('#/(akce|events|akcie|event|kalendar)/[\w\-]+|\?id=\d+#i', $url);
        }

        return str_contains($url, $detailPattern);
    }

    /** Vytáhnout všechny URL z HTML (resolve k base). */
    protected function extrahujOdkazy(string $html, string $aktualniUrl, string $baseUrl): array
    {
        if (!preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $m)) {
            return [];
        }

        $urls = [];
        foreach ($m[1] as $href) {
            $href = trim($href);
            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')
                || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $urls[] = $this->resolveUrl($href, $aktualniUrl, $baseUrl);
        }

        // Filter: jen na stejný host
        $host = parse_url($baseUrl, PHP_URL_HOST);
        return array_values(array_unique(array_filter($urls, function ($u) use ($host) {
            $h = parse_url($u, PHP_URL_HOST);
            return $h === $host;
        })));
    }

    protected function resolveUrl(string $href, string $aktualniUrl, string $baseUrl): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }
        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $href;
        }
        if (str_starts_with($href, '/')) {
            return rtrim($baseUrl, '/') . $href;
        }
        // Relativní vůči aktuální URL
        $aktualniDir = dirname($aktualniUrl);
        return rtrim($aktualniDir, '/') . '/' . $href;
    }

    /** Normalizace URL pro detekci duplikátů (odstranit fragment, abc parametry). */
    protected function kanonickaUrl(string $url): string
    {
        return strtok(rtrim($url, '/'), '#');
    }

    protected function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => self::UA,
                'Accept-Language' => 'cs,en;q=0.5',
            ])->timeout(30)->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (\Exception $e) {
            Log::warning("ListingPaginator fetch failed: {$url} — {$e->getMessage()}");
            return null;
        }
    }
}
