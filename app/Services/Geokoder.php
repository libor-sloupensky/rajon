<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geokódování adres přes Mapy.cz API.
 *
 * Vstup: adresa, město, okres, kraj — sestavíme nejlepší možný query.
 * Výstup: [lat, lng] nebo null.
 *
 * API: https://api.mapy.cz/v1/geocode?lang=cs&apikey=KEY&query=...
 *      Vrací { items: [{ position: { lon, lat }, ... }] }
 *
 * Cache: 30 dní per query — adresy se nemění často, šetří API quota.
 */
class Geokoder
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) config('services.mapycz.api_key');
    }

    /**
     * Geokóduj adresu uživatele (město + volitelně PSČ).
     * PSČ pomáhá disambiguovat obce se stejným názvem.
     */
    public function geokodujAdresuUzivatele(string $mesto, ?string $psc = null): ?array
    {
        if (empty($this->apiKey)) {
            \Illuminate\Support\Facades\Log::warning('Geokoder: MAPYCZ_API_KEY není nastaveno');
            return null;
        }

        $queries = [];
        if (!empty($psc)) {
            $queries[] = trim($mesto) . ' ' . trim($psc) . ', Česká republika';
        }
        $queries[] = trim($mesto) . ', Česká republika';

        foreach ($queries as $query) {
            $cacheKey = 'geokoder.user.' . md5($query);
            $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
                return $this->volajApi($query);
            });
            if ($result) return $result;
        }
        return null;
    }

    /**
     * Geokóduj kombinaci adresa/místo/město/okres/kraj.
     * Vrací array ['gps_lat' => ..., 'gps_lng' => ..., 'okres' => ..., 'kraj' => ...] nebo null.
     *
     * Zkouší více variant query — pokud první selže, postupně zjednodušuje.
     */
    public function geokoduj(?string $adresa, ?string $misto, ?string $mesto, ?string $okres, ?string $kraj): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Geokoder: MAPYCZ_API_KEY není nastaveno');
            return null;
        }

        // Postupné varianty queries — od specifické po obecnou
        $queries = $this->sestavQueries($adresa, $misto, $mesto, $okres, $kraj);

        foreach ($queries as $query) {
            $cacheKey = 'geokoder.' . md5($query);
            $result = Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
                return $this->volajApi($query);
            });
            if ($result) return $result;
        }
        return null;
    }

    /** Sestaví seznam queries (od nejvíc specifické po obecnou) pro fallback. */
    protected function sestavQueries(?string $adresa, ?string $misto, ?string $mesto, ?string $okres, ?string $kraj): array
    {
        $queries = [];

        // 1) Adresa + misto + kraj — nejúplnější
        if (!empty($adresa) && !empty($misto) && !$this->jeJenKraj($misto)) {
            $queries[] = $this->joinQuery([$adresa, $misto, $kraj]);
        }
        // 2) Misto + kraj — pro známé objekty (zámek Telč, vinařství...)
        if (!empty($misto) && !$this->jeJenKraj($misto)) {
            $queries[] = $this->joinQuery([$misto, $kraj]);
        }
        // 3) Adresa + kraj
        if (!empty($adresa) && !$this->jeJenKraj($adresa)) {
            $queries[] = $this->joinQuery([$adresa, $kraj]);
        }
        // 4) Adresa + město
        if (!empty($adresa) && !empty($mesto)) {
            $queries[] = $this->joinQuery([$adresa, $mesto]);
        }
        // 5) Misto + okres
        if (!empty($misto) && !empty($okres) && !$this->jeJenKraj($misto)) {
            $queries[] = $this->joinQuery([$misto, $okres]);
        }
        // 6) Adresa sama
        if (!empty($adresa) && !$this->jeJenKraj($adresa)) {
            $queries[] = $this->joinQuery([$adresa]);
        }

        // Deduplikuj
        return array_values(array_unique(array_filter($queries)));
    }

    protected function joinQuery(array $parts): ?string
    {
        $parts = array_map('trim', array_filter($parts));
        $parts = array_filter($parts, fn ($p) => !$this->jeJenKraj($p) || count($parts) > 1);
        if (empty($parts)) return null;
        $parts[] = 'Česká republika';
        $q = implode(', ', $parts);
        return mb_strlen($q) < 5 ? null : $q;
    }

    /** Sestaví query string z dostupných polí. Preferuje úplnost. */
    protected function sestavQuery(?string $adresa, ?string $misto, ?string $mesto, ?string $okres, ?string $kraj): ?string
    {
        $parts = [];

        // Adresa (ulice + číslo) má nejvyšší prioritu
        if (!empty($adresa) && !$this->jeJenKraj($adresa)) {
            $parts[] = trim($adresa);
        }

        // Místo (např. náměstí) — pomáhá pokud nemáme adresu
        // POZOR: některé Stánkař akce mají misto="Středočeský kraj" — to ignorujeme
        if (!empty($misto) && $misto !== $adresa && !$this->jeJenKraj($misto)) {
            $parts[] = trim($misto);
        }

        // Město
        if (!empty($mesto) && !$this->jeJenKraj($mesto)) {
            $parts[] = trim($mesto);
        }

        // Okres jako zpřesnění (jen pokud nemáme adresu/město)
        if (empty($parts) && !empty($okres)) {
            $parts[] = trim($okres);
        }

        // Pokud máme jen kraj (žádné konkrétní místo), nemá smysl geokódovat
        // — vrátilo by to střed kraje, což není užitečné GPS pro akci.
        if (empty($parts)) return null;

        // Přidat ČR pro disambiguaci
        $parts[] = 'Česká republika';

        $q = implode(', ', $parts);
        return mb_strlen($q) < 5 ? null : $q;
    }

    /** Detekuje, jestli string obsahuje jen název kraje (bez konkrétního místa). */
    protected function jeJenKraj(string $text): bool
    {
        $kraje = [
            'praha', 'středočeský', 'jihočeský', 'plzeňský', 'karlovarský',
            'ústecký', 'liberecký', 'královéhradecký', 'pardubický',
            'vysočina', 'jihomoravský', 'olomoucký', 'zlínský', 'moravskoslezský',
            'kraj vysočina', 'hlavní město praha',
        ];
        $t = mb_strtolower(trim($text));
        // Pokud text končí "kraj" nebo je jen jméno kraje
        if (str_ends_with($t, ' kraj') || str_starts_with($t, 'kraj ')) return true;
        foreach ($kraje as $k) {
            if ($t === $k || $t === $k . ' kraj') return true;
        }
        return false;
    }

    /** Volá Mapy.cz Geocoding API. Vrací gps + okres + kraj z regionalStructure. */
    protected function volajApi(string $query): ?array
    {
        try {
            $response = Http::timeout(10)->get('https://api.mapy.cz/v1/geocode', [
                'query' => $query,
                'lang' => 'cs',
                'limit' => 1,
                'apikey' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                Log::warning('Geokoder API chyba', ['status' => $response->status(), 'query' => $query]);
                return null;
            }

            $items = $response->json('items', []);
            $first = $items[0] ?? null;
            if (!$first || empty($first['position'])) return null;

            $lat = $first['position']['lat'] ?? null;
            $lng = $first['position']['lon'] ?? null;
            if (!is_numeric($lat) || !is_numeric($lng)) return null;

            // Kontrola, že je to v ČR (49-51 lat, 12-19 lng)
            if ($lat < 48.5 || $lat > 51.5 || $lng < 12 || $lng > 19) {
                Log::info('Geokoder: výsledek mimo ČR, ignoruji', ['query' => $query, 'lat' => $lat, 'lng' => $lng]);
                return null;
            }

            $result = ['gps_lat' => (float) $lat, 'gps_lng' => (float) $lng];

            // Okres + kraj z regionalStructure
            // Mapy.cz vrací položky typu "regional.region" — okres má prefix "okres ",
            // kraje mají suffix " kraj" nebo "Kraj Vysočina".
            $regions = $first['regionalStructure'] ?? [];
            foreach ($regions as $r) {
                $name = $r['name'] ?? '';
                if (str_starts_with($name, 'okres ')) {
                    $result['okres'] = trim(mb_substr($name, 6));
                } elseif (str_ends_with($name, ' kraj') || str_starts_with($name, 'Kraj ')
                        || $name === 'Hlavní město Praha') {
                    $result['kraj'] = $name;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning("Geokoder výjimka: {$e->getMessage()}", ['query' => $query]);
            return null;
        }
    }
}
