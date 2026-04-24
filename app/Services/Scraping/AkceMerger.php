<?php

namespace App\Services\Scraping;

use App\Models\Akce;
use App\Models\Zdroj;

/**
 * Merge nových dat do existující akce podle trust ranking.
 * Pravidla (priorita shora dolů):
 *   1. Manuální úprava adminem = NIKDY nepřepsat
 *   2. Prázdné pole = DOPLNIT
 *   3. Vyšší trust zdroje = PŘEPSAT
 *   4. Popis: pokud nový je výrazně delší (1.2×), přepsat
 *   5. velikost_info: APPEND z více zdrojů
 *   6. velikost_signaly: MERGE JSON (zachovat ne-null hodnoty)
 *   7. velikost_skore: vyšší vyhrává
 */
class AkceMerger
{
    /** Pole která se mergují field-level (všechno scraping plní). */
    protected array $mergovana = [
        'typ', 'popis', 'datum_od', 'datum_do', 'misto', 'adresa',
        'gps_lat', 'gps_lng', 'okres', 'kraj', 'organizator',
        'kontakt_email', 'kontakt_telefon', 'web_url', 'vstupne',
    ];

    /** Pole která se NIKDY nepřepíší scrapingem (chrání business data). */
    protected array $chranene = [
        'najem', 'obrat', 'uzivatel_id', 'stav', 'slug', 'nazev',
        'propojena_s_akci_id',
    ];

    public function merge(Akce $existujici, array $noveData, Zdroj $zdroj, string $url): array
    {
        // Detekce: je aktuální zdroj webem pořadatele této akce?
        $jeOdPoradatele = $this->jeOdPoradatele($zdroj, $url, $existujici, $noveData);
        $zdrojKey = $jeOdPoradatele ? 'web_poradatele' : ($zdroj->cms_typ ?: 'custom');

        $trust = config('scraping.trust');
        $mergeCfg = config('scraping.merge');

        $poleManualni = $existujici->pole_manualni ?? [];
        $poleZdroje = $existujici->pole_zdroje ?? [];
        $konflikty = $existujici->konflikty ?? [];
        $mergeLog = $existujici->merge_log ?? [];

        $zmeny = [];
        $noveKonflikty = [];

        foreach ($this->mergovana as $pole) {
            if (!array_key_exists($pole, $noveData)) continue;
            $novaHodnota = $noveData[$pole];
            if ($novaHodnota === null || $novaHodnota === '') continue;

            // 1. Manuální úprava → SKIP
            if (isset($poleManualni[$pole])) {
                continue;
            }

            $puvodniHodnota = $existujici->$pole;

            // 2. Prázdné pole → DOPLNIT
            if ($this->jePrazdne($puvodniHodnota)) {
                $existujici->$pole = $novaHodnota;
                $poleZdroje[$pole] = $zdrojKey;
                $zmeny[$pole] = ['action' => 'filled', 'from' => $zdrojKey];
                continue;
            }

            // 3. Speciální pravidlo pro popis — delší vyhrává
            if ($pole === 'popis') {
                $factor = $mergeCfg['popis_prefer_longer_factor'];
                if (mb_strlen((string) $novaHodnota) > mb_strlen((string) $puvodniHodnota) * $factor) {
                    $existujici->$pole = $novaHodnota;
                    $poleZdroje[$pole] = $zdrojKey;
                    $zmeny[$pole] = ['action' => 'replaced_longer', 'from' => $zdrojKey];
                    continue;
                }
            }

            // 4. Porovnat trust
            $trustNovy = $this->trustPole($trust, $zdrojKey, $pole);
            $puvodniZdroj = $poleZdroje[$pole] ?? 'custom';
            $trustPuvodni = $this->trustPole($trust, $puvodniZdroj, $pole);

            // 5. Konflikt při porovnání klíčových polí (datum, místo, GPS)
            if ($this->jeKlicovePole($pole)
                && $trustNovy >= $trustPuvodni
                && !$this->souHlasneHodnoty($pole, $puvodniHodnota, $novaHodnota)) {
                // Uložit do konfliktů, nepřepisovat automaticky
                $noveKonflikty[] = [
                    'pole' => $pole,
                    'puvodni' => ['zdroj' => $puvodniZdroj, 'hodnota' => $puvodniHodnota, 'trust' => $trustPuvodni],
                    'novy' => ['zdroj' => $zdrojKey, 'hodnota' => $novaHodnota, 'trust' => $trustNovy],
                    'datum' => now()->toIso8601String(),
                ];
                $zmeny[$pole] = ['action' => 'conflict', 'from' => $zdrojKey];
                continue;
            }

            // 6. Trust ranking — nový vyšší → přepsat
            if ($trustNovy > $trustPuvodni) {
                $existujici->$pole = $novaHodnota;
                $poleZdroje[$pole] = $zdrojKey;
                $zmeny[$pole] = ['action' => 'overwritten', 'from' => $zdrojKey, 'trust' => $trustNovy];
            }
        }

        // 7. velikost_info — append z více zdrojů
        if (!empty($noveData['velikost_info']) && empty($poleManualni['velikost_info'])) {
            $existing = (string) ($existujici->velikost_info ?? '');
            $prefix = "[{$zdroj->nazev}]";

            // Neduplikovat — pokud už text obsahuje stejný prefix, nahradit
            if (str_contains($existing, $prefix)) {
                $existing = preg_replace('/' . preg_quote($prefix, '/') . '.*?(?=\[|$)/s', '', $existing);
            }

            $existujici->velikost_info = trim($existing . "\n{$prefix} " . $noveData['velikost_info']);
            $zmeny['velikost_info'] = ['action' => 'appended', 'from' => $zdrojKey];
        }

        // 8. velikost_signaly — merge JSON
        if (!empty($noveData['velikost_signaly'])) {
            $current = (array) ($existujici->velikost_signaly ?? []);
            foreach ($noveData['velikost_signaly'] as $k => $v) {
                if ($v !== null && (!isset($current[$k]) || $current[$k] === null)) {
                    $current[$k] = $v;
                    $zmeny["velikost_signaly.{$k}"] = ['action' => 'merged', 'from' => $zdrojKey];
                }
            }
            $existujici->velikost_signaly = $current;
        }

        // 9. velikost_skore — vyšší vyhrává
        if (!empty($noveData['_skore']) && $noveData['_skore'] > $existujici->velikost_skore) {
            $existujici->velikost_skore = $noveData['_skore'];
            $existujici->velikost_stav = $noveData['_stav'] ?? $existujici->velikost_stav;
            $zmeny['velikost_skore'] = ['action' => 'increased', 'from' => $zdrojKey, 'hodnota' => $noveData['_skore']];
        }

        // Uložit metadata
        $existujici->pole_zdroje = $poleZdroje;

        if ($noveKonflikty) {
            $existujici->konflikty = array_merge($konflikty, $noveKonflikty);
        }

        // Merge log — posledních N operací
        if ($zmeny) {
            $mergeLog[] = [
                'datum' => now()->toIso8601String(),
                'zdroj' => $zdrojKey,
                'url' => $url,
                'zmeny' => $zmeny,
            ];
            $max = (int) config('scraping.merge.merge_log_max', 20);
            $existujici->merge_log = array_slice($mergeLog, -$max);
        }

        $existujici->externi_hash = hash('sha256', json_encode($noveData));

        if ($existujici->isDirty()) {
            $existujici->save();
        }

        return [
            'zmeny' => $zmeny,
            'konflikty_pridano' => count($noveKonflikty),
        ];
    }

    /** Vyplnit pole_zdroje pro novou akci (všechna pole jsou ze zdroje). */
    public function inicializujZdroje(Akce $akce, Zdroj $zdroj): void
    {
        // Detekce webu pořadatele i pro novou akci
        $jeOdPoradatele = $this->jeOdPoradatele($zdroj, $akce->zdroj_url ?? '', $akce, []);
        $zdrojKey = $jeOdPoradatele ? 'web_poradatele' : ($zdroj->cms_typ ?: 'custom');
        $zdroje = [];

        foreach ($this->mergovana as $pole) {
            if (!$this->jePrazdne($akce->$pole)) {
                $zdroje[$pole] = $zdrojKey;
            }
        }

        $akce->pole_zdroje = $zdroje;
        $akce->merge_log = [[
            'datum' => now()->toIso8601String(),
            'zdroj' => $zdrojKey,
            'zmeny' => ['_init' => 'created'],
        ]];
        $akce->save();
    }

    protected function trustPole(array $trust, string $zdrojKey, string $pole): int
    {
        $zdrojovy = $trust[$zdrojKey] ?? ($trust['custom'] ?? ['*' => 50]);
        return (int) ($zdrojovy[$pole] ?? $zdrojovy['*'] ?? 50);
    }

    /**
     * Detekuje, zda aktuální zdroj je webem pořadatele akce.
     * Kritéria:
     *   1. Zdroj má explicitní flag je_web_poradatele=true
     *   2. Doména URL scrapingu se shoduje s doménou akce.web_url
     */
    protected function jeOdPoradatele(Zdroj $zdroj, string $url, Akce $akce, array $noveData): bool
    {
        if ($zdroj->je_web_poradatele) {
            return true;
        }

        $webAkce = $noveData['web_url'] ?? $akce->web_url ?? null;
        if (empty($webAkce) || empty($url)) {
            return false;
        }

        return $this->stejnaDomena($url, $webAkce);
    }

    protected function stejnaDomena(string $url1, string $url2): bool
    {
        $h1 = parse_url($url1, PHP_URL_HOST);
        $h2 = parse_url($url2, PHP_URL_HOST);
        if (!$h1 || !$h2) return false;
        $h1 = preg_replace('/^www\./', '', mb_strtolower($h1));
        $h2 = preg_replace('/^www\./', '', mb_strtolower($h2));
        return $h1 === $h2;
    }

    protected function jePrazdne(mixed $hodnota): bool
    {
        return $hodnota === null || $hodnota === '' || $hodnota === [];
    }

    protected function jeKlicovePole(string $pole): bool
    {
        return in_array($pole, ['datum_od', 'datum_do', 'misto', 'gps_lat', 'gps_lng'], true);
    }

    /** Porovnat dvě hodnoty "významově". Datum přes strtotime, čísla s tolerancí. */
    protected function souHlasneHodnoty(string $pole, mixed $a, mixed $b): bool
    {
        if ($a === null || $b === null) return $a === $b;

        if (in_array($pole, ['datum_od', 'datum_do'])) {
            return date('Y-m-d', strtotime((string) $a)) === date('Y-m-d', strtotime((string) $b));
        }

        if (in_array($pole, ['gps_lat', 'gps_lng'])) {
            return abs((float) $a - (float) $b) < 0.001; // ~100m
        }

        // Textový match s normalizací
        $an = mb_strtolower(trim((string) $a));
        $bn = mb_strtolower(trim((string) $b));
        return $an === $bn;
    }
}
