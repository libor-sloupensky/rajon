<?php

namespace App\Services\Scraping;

use App\Models\Akce;
use Illuminate\Support\Str;

/**
 * Hledá existující akci v DB pro daná nově získaná data.
 * 3 strategie: slug exact, název+datum+město, fuzzy similar + GPS proximity.
 */
class AkceMatcher
{
    protected int $similarityThreshold;
    protected int $dateTolerance;
    protected float $gpsRadiusKm;

    public function __construct()
    {
        $this->similarityThreshold = (int) config('scraping.matching.similarity_threshold', 80);
        $this->dateTolerance = (int) config('scraping.matching.date_tolerance_days', 3);
        $this->gpsRadiusKm = (float) config('scraping.matching.gps_radius_km', 1.0);
    }

    /**
     * Najdi existující akci odpovídající novým datům. Null pokud žádná neexistuje.
     */
    public function najdiExistujici(array $data): ?Akce
    {
        $nazev = $data['nazev'] ?? null;
        $datumOd = $data['datum_od'] ?? null;

        if (empty($nazev) || empty($datumOd)) {
            return null;
        }

        // Strategie 1: slug exact match (nejrychlejší)
        if ($akce = $this->pokusSlug($nazev, $datumOd)) {
            return $akce;
        }

        // Strategie 2: název + datum + město (LIKE)
        if ($akce = $this->pokusNazevDatumMesto($data)) {
            return $akce;
        }

        // Strategie 3: fuzzy similarity + datum ± tolerance + GPS proximity
        if ($akce = $this->pokusFuzzy($data)) {
            return $akce;
        }

        return null;
    }

    /**
     * Najdi kandidáty na ročníkové propojení (stejná akce jiný rok).
     * Vrací akce z předchozích let s podobným názvem + blízkým místem.
     */
    public function navrhniPropojeniRocniku(array $data): array
    {
        $nazev = $data['nazev'] ?? '';
        $datumOd = $data['datum_od'] ?? null;
        if (empty($nazev) || empty($datumOd)) return [];

        $rok = (int) date('Y', strtotime($datumOd));

        // Kandidáti: jiný rok, podobný název
        $kandidati = Akce::query()
            ->whereYear('datum_od', '!=', $rok)
            ->where(function ($q) use ($data) {
                if (!empty($data['kraj'])) {
                    $q->where('kraj', $data['kraj']);
                }
                if (!empty($data['okres'])) {
                    $q->orWhere('okres', $data['okres']);
                }
            })
            ->limit(50)
            ->get();

        $navrhy = [];
        $nazevClean = $this->normalizujNazev($nazev);

        foreach ($kandidati as $k) {
            $kNazev = $this->normalizujNazev($k->nazev);
            $sim = 0;
            similar_text($nazevClean, $kNazev, $sim);

            if ($sim >= $this->similarityThreshold) {
                $navrhy[] = [
                    'akce_id' => $k->id,
                    'nazev' => $k->nazev,
                    'datum_od' => $k->datum_od?->format('Y-m-d'),
                    'misto' => $k->misto,
                    'similarity' => round($sim, 1),
                ];
            }
        }

        usort($navrhy, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);
        return array_slice($navrhy, 0, 5);
    }

    protected function pokusSlug(string $nazev, string $datumOd): ?Akce
    {
        $rok = date('Y', strtotime($datumOd));
        $slug = Str::slug($nazev . '-' . $rok);
        return Akce::where('slug', $slug)->first();
    }

    protected function pokusNazevDatumMesto(array $data): ?Akce
    {
        $nazev = $data['nazev'];
        $datumOd = $data['datum_od'];
        $mesto = $data['mesto'] ?? $data['misto'] ?? null;

        $query = Akce::query()
            ->where('nazev', $nazev)
            ->whereBetween('datum_od', [
                date('Y-m-d', strtotime("{$datumOd} -{$this->dateTolerance} days")),
                date('Y-m-d', strtotime("{$datumOd} +{$this->dateTolerance} days")),
            ]);

        if ($mesto) {
            $query->where(function ($q) use ($mesto) {
                $q->where('misto', 'like', "%{$mesto}%")
                  ->orWhere('adresa', 'like', "%{$mesto}%");
            });
        }

        return $query->first();
    }

    protected function pokusFuzzy(array $data): ?Akce
    {
        $nazev = $data['nazev'];
        $datumOd = $data['datum_od'];

        // Zúžit okruh kandidátů
        $kandidati = Akce::query()
            ->whereBetween('datum_od', [
                date('Y-m-d', strtotime("{$datumOd} -{$this->dateTolerance} days")),
                date('Y-m-d', strtotime("{$datumOd} +{$this->dateTolerance} days")),
            ])
            ->when(!empty($data['kraj']), fn ($q) => $q->where('kraj', $data['kraj']))
            ->limit(30)
            ->get();

        $nazevClean = $this->normalizujNazev($nazev);

        foreach ($kandidati as $k) {
            $kNazev = $this->normalizujNazev($k->nazev);
            $sim = 0;
            similar_text($nazevClean, $kNazev, $sim);

            if ($sim < $this->similarityThreshold) continue;

            // GPS check (pokud oba mají souřadnice)
            if (!empty($data['gps_lat']) && !empty($data['gps_lng'])
                && $k->gps_lat && $k->gps_lng) {
                $distance = $this->haversineKm(
                    (float) $data['gps_lat'], (float) $data['gps_lng'],
                    $k->gps_lat, $k->gps_lng
                );

                if ($distance <= $this->gpsRadiusKm) {
                    return $k;
                }
                // GPS je mimo radius — pravděpodobně jiná akce stejného názvu
                continue;
            }

            // Pokud nemáme GPS, similarity stačí
            return $k;
        }

        return null;
    }

    protected function normalizujNazev(string $nazev): string
    {
        // Odstranit rok, normalizovat interpunkci
        $nazev = preg_replace('/\b(19|20)\d{2}\b/', '', $nazev);
        $nazev = Str::slug($nazev);
        return trim($nazev, '-');
    }

    /** Vzdálenost dvou GPS bodů v km (Haversine). */
    protected function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $R * 2 * asin(sqrt($a));
    }
}
