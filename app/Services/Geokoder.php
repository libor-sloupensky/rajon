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

        // Zjisti zemi akce z kraje/okresu — jinak by se SK obce (Trnava) navázaly
        // na stejnojmennou českou obec.
        $zemeQuery = $this->jeSlovensko($kraj, $okres) ? 'Slovensko' : 'Česká republika';

        // Postupné varianty queries — od specifické po obecnou
        $queries = $this->sestavQueries($adresa, $misto, $mesto, $okres, $kraj, $zemeQuery);

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
    protected function sestavQueries(?string $adresa, ?string $misto, ?string $mesto, ?string $okres, ?string $kraj, string $zemeQuery = 'Česká republika'): array
    {
        $queries = [];

        // 1) Adresa + misto + kraj — nejúplnější
        if (!empty($adresa) && !empty($misto) && !$this->jeJenKraj($misto)) {
            $queries[] = $this->joinQuery([$adresa, $misto, $kraj], $zemeQuery);
        }
        // 2) Misto + kraj — pro známé objekty (zámek Telč, vinařství...)
        if (!empty($misto) && !$this->jeJenKraj($misto)) {
            $queries[] = $this->joinQuery([$misto, $kraj], $zemeQuery);
        }
        // 3) Adresa + kraj
        if (!empty($adresa) && !$this->jeJenKraj($adresa)) {
            $queries[] = $this->joinQuery([$adresa, $kraj], $zemeQuery);
        }
        // 4) Adresa + město
        if (!empty($adresa) && !empty($mesto)) {
            $queries[] = $this->joinQuery([$adresa, $mesto], $zemeQuery);
        }
        // 5) Misto + okres
        if (!empty($misto) && !empty($okres) && !$this->jeJenKraj($misto)) {
            $queries[] = $this->joinQuery([$misto, $okres], $zemeQuery);
        }
        // 6) Adresa sama
        if (!empty($adresa) && !$this->jeJenKraj($adresa)) {
            $queries[] = $this->joinQuery([$adresa], $zemeQuery);
        }

        // Deduplikuj
        return array_values(array_unique(array_filter($queries)));
    }

    protected function joinQuery(array $parts, string $zemeQuery = 'Česká republika'): ?string
    {
        $parts = array_map('trim', array_filter($parts));
        $parts = array_filter($parts, fn ($p) => !$this->jeJenKraj($p) || count($parts) > 1);
        if (empty($parts)) return null;
        $parts[] = $zemeQuery;
        $q = implode(', ', $parts);
        return mb_strlen($q) < 5 ? null : $q;
    }

    /**
     * Patří kraj/okres do Slovenska? Rozhoduje podle sloupce zeme v DB.
     * Kvůli stejnojmenným obcím (Trnava CZ × SK) musíme znát zemi akce.
     */
    protected function jeSlovensko(?string $kraj, ?string $okres): bool
    {
        $skKraje = Cache::remember('geokoder.sk_kraje', 3600, fn () =>
            \App\Models\Kraj::where('zeme', 'SK')->pluck('nazev')->map(fn ($n) => mb_strtolower($n))->all());
        $skOkresy = Cache::remember('geokoder.sk_okresy', 3600, fn () =>
            \App\Models\Okres::whereIn('kraj_id', \App\Models\Kraj::where('zeme', 'SK')->select('id'))
                ->pluck('nazev')->map(fn ($n) => mb_strtolower($n))->all());

        $k = mb_strtolower(trim((string) $kraj));
        $o = mb_strtolower(trim((string) $okres));

        return ($k !== '' && in_array($k, $skKraje, true))
            || ($o !== '' && in_array($o, $skOkresy, true));
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

            // Kontrola, že je to v ČR nebo SR (lat 47.6–51.2, lng 11.9–22.7).
            // Box pokrývá obě země — Slovensko sahá na východ až k lng ~22.6.
            if ($lat < 47.6 || $lat > 51.2 || $lng < 11.9 || $lng > 22.7) {
                Log::info('Geokoder: výsledek mimo ČR/SR, ignoruji', ['query' => $query, 'lat' => $lat, 'lng' => $lng]);
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
