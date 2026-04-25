<?php

namespace Database\Seeders;

use App\Models\Akce;
use App\Models\AkceVykaz;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Načte `database/data/historicka_data.json` a vloží do DB ve **chunkech**:
 *   - `akce` (1021 záznamů)
 *   - `akce_vykazy` (529 záznamů)
 *
 * Webglobe shared hosting má proxy timeout < 60 s a output buffering, takže
 * dlouhý seeder spadne na 500 bez výstupu. Tento seeder čte parametry z $_GET
 * a importuje **jen jeden chunk** (default 200 řádků).
 *
 * URL přes deploy-hook:
 *   ?token=...&seed=ImportHistorickychDatSeeder&phase=akce&offset=0
 *   ?token=...&seed=ImportHistorickychDatSeeder&phase=akce&offset=200
 *   ?token=...&seed=ImportHistorickychDatSeeder&phase=akce&offset=400
 *   ... až vrátí "phase kompletní" ...
 *   ?token=...&seed=ImportHistorickychDatSeeder&phase=vykazy&offset=0
 *   ...
 */
class ImportHistorickychDatSeeder extends Seeder
{
    private const JSON_PATH = 'database/data/historicka_data.json';

    private const CHUNK_DEFAULT = 200;

    public function run(): void
    {
        $path = base_path(self::JSON_PATH);
        if (!is_file($path)) {
            $this->command->error("Data soubor nenalezen: {$path}");
            return;
        }

        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data) || !isset($data['akce'], $data['vykazy'])) {
            $this->command->error('Nevalidní formát JSON.');
            return;
        }

        $phase = $_GET['phase'] ?? 'akce';
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $limit = max(1, (int) ($_GET['limit'] ?? self::CHUNK_DEFAULT));

        $this->command->line(sprintf(
            'Načteno: %d akcí + %d výkazů. Spouštím phase=%s offset=%d limit=%d',
            $data['meta']['pocet_akci'] ?? count($data['akce']),
            $data['meta']['pocet_vykazu'] ?? count($data['vykazy']),
            $phase, $offset, $limit,
        ));

        if ($phase === 'akce') {
            $this->importAkceChunk($data['akce'], $offset, $limit);
        } elseif ($phase === 'vykazy') {
            $this->importVykazyChunk($data['vykazy'], $offset, $limit);
        } else {
            $this->command->error("Unknown phase: {$phase}. Použijte 'akce' nebo 'vykazy'.");
        }
    }

    /**
     * @param list<array<string,mixed>> $vsechnyAkce
     */
    private function importAkceChunk(array $vsechnyAkce, int $offset, int $limit): void
    {
        $chunk = array_slice($vsechnyAkce, $offset, $limit);
        $celkem = count($vsechnyAkce);
        $end = min($offset + $limit, $celkem);

        if (empty($chunk)) {
            $this->command->line("Phase 'akce' kompletní (offset {$offset} >= {$celkem}).");
            return;
        }

        // Pre-load existující akce do paměti (jen 1× při větším inserte)
        $existSlug = Akce::query()->pluck('id', 'slug')->all();
        $existByNazevMisto = Akce::query()
            ->select('id', 'nazev', 'misto')
            ->get()
            ->mapWithKeys(fn ($a) => [$this->klicNazevMisto($a->nazev, $a->misto) => $a->id])
            ->all();

        $now = now();
        $bufInsert = [];
        $matched = 0;

        DB::transaction(function () use ($chunk, &$bufInsert, &$matched, $existSlug, $existByNazevMisto, $now) {
            foreach ($chunk as $row) {
                unset($row['externi_klic']);

                // Match nejdřív podle (nazev, misto)
                $klicNM = $this->klicNazevMisto($row['nazev'] ?? '', $row['misto'] ?? null);
                if (isset($existByNazevMisto[$klicNM])) {
                    $matched++;
                    continue;
                }

                // Vygenerovat unikátní slug
                $slug = $row['slug'] ?? Str::slug($row['nazev'] ?? 'akce');
                if ($slug === '') $slug = 'akce';
                $finalSlug = $slug;
                $i = 2;
                while (isset($existSlug[$finalSlug])) {
                    $finalSlug = $slug . '-' . $i++;
                    if ($i > 9999) break;
                }
                $row['slug'] = $finalSlug;
                $existSlug[$finalSlug] = -1;
                $existByNazevMisto[$klicNM] = -1;

                $bufInsert[] = $this->normalizujRadek($row, $now);
            }

            if ($bufInsert) {
                Akce::insert($bufInsert);
            }
        });

        $this->command->line(sprintf(
            "Akce [%d–%d / %d]: nových=%d, matched=%d, mem=%.1f MB",
            $offset, $end, $celkem,
            count($bufInsert), $matched,
            memory_get_peak_usage(true) / 1048576,
        ));

        if ($end < $celkem) {
            $this->command->line("→ pokračuj: ?phase=akce&offset={$end}");
        } else {
            $this->command->line("✓ Phase 'akce' kompletní. Pokračuj: ?phase=vykazy&offset=0");
        }
    }

    /**
     * @param list<array<string,mixed>> $vsechnyVykazy
     */
    private function importVykazyChunk(array $vsechnyVykazy, int $offset, int $limit): void
    {
        $chunk = array_slice($vsechnyVykazy, $offset, $limit);
        $celkem = count($vsechnyVykazy);
        $end = min($offset + $limit, $celkem);

        if (empty($chunk)) {
            $this->command->line("Phase 'vykazy' kompletní (offset {$offset} >= {$celkem}).");
            return;
        }

        // Mapa (nazev|misto) → akce_id z DB — výkaz se napáruje přes externi_klic na akci
        // (externi_klic v JSON byl spočítán z normalizovaného nazev+misto)
        $mapaKlicu = $this->nactiMapuKlicuVsechAkci();

        $now = now();
        $bufVykaz = [];
        $orphans = 0;

        // V JSONu má každý vykaz vlastní externi_klic. Mapa pro něj musí být
        // postavena ze stejného algoritmu jako v exportu (NazevNormalizer).
        // Akce v DB ale nemají externi_klic — musíme ho dohledat přes (nazev, misto).
        // Načteme všechny akce s normalizovaným klíčem.
        $akceById = Akce::query()->select('id', 'nazev', 'misto')->get();
        $klicePerAkceId = [];
        foreach ($akceById as $a) {
            $klicePerAkceId[$this->externiKlicNormalized($a->nazev, $a->misto)] = $a->id;
        }

        foreach ($chunk as $row) {
            $extKlic = $row['externi_klic'];
            $rok = (int) ($row['rok'] ?? 0);
            if ($rok < 2020 || $rok > 2030) continue;

            $akceId = $klicePerAkceId[$extKlic] ?? null;
            if (!$akceId) {
                $orphans++;
                continue;
            }

            $bufVykaz[] = [
                'akce_id' => $akceId,
                'rok' => $rok,
                'datum_od' => !empty($row['datum_od']) ? Carbon::parse($row['datum_od'])->toDateString() : null,
                'datum_do' => !empty($row['datum_do']) ? Carbon::parse($row['datum_do'])->toDateString() : null,
                'trzba' => $row['trzba'] ?? null,
                'najem' => $row['najem'] ?? null,
                'poznamka' => $row['poznamka'] ?? null,
                'zdroj_excel' => $row['zdroj_excel'] ?? null,
                'vytvoreno' => $now,
                'upraveno' => $now,
            ];
        }

        if ($bufVykaz) {
            AkceVykaz::upsert($bufVykaz, ['akce_id', 'rok'], [
                'datum_od', 'datum_do', 'trzba', 'najem', 'poznamka', 'zdroj_excel', 'upraveno',
            ]);
        }

        $this->command->line(sprintf(
            "Výkazy [%d–%d / %d]: zapsáno=%d, orphan=%d, mem=%.1f MB",
            $offset, $end, $celkem,
            count($bufVykaz), $orphans,
            memory_get_peak_usage(true) / 1048576,
        ));

        if ($end < $celkem) {
            $this->command->line("→ pokračuj: ?phase=vykazy&offset={$end}");
        } else {
            $this->command->line("✓ Hotovo, všechny výkazy nahrány.");
        }
    }

    /** @return array<string, int> */
    private function nactiMapuKlicuVsechAkci(): array
    {
        $map = [];
        Akce::query()->select('id', 'nazev', 'misto')->chunk(500, function ($akce) use (&$map) {
            foreach ($akce as $a) {
                $map[$this->externiKlicNormalized($a->nazev, $a->misto)] = $a->id;
            }
        });
        return $map;
    }

    /** Replikuje NazevNormalizer logiku — musí dát stejný výsledek jako export. */
    private function externiKlicNormalized(?string $nazev, ?string $misto): string
    {
        return $this->slugLikeNormalize((string) $nazev) . '|' . $this->slugLikeNormalize((string) ($misto ?? ''));
    }

    private function slugLikeNormalize(string $s): string
    {
        $s = preg_replace('/\b(19|20)\d{2}\b/', ' ', $s);
        $s = preg_replace('/\b\d+\.?\s*(ročník|ročníku)\b/iu', ' ', $s);
        $s = preg_replace('/\b\d+\.\s/u', ' ', $s);
        $s = preg_replace('/\b\d+[tn]ý\b/u', ' ', $s);
        return trim(Str::slug($s), '-');
    }

    private function klicNazevMisto(?string $nazev, ?string $misto): string
    {
        return mb_strtolower(trim((string) $nazev)) . '|' . mb_strtolower(trim((string) ($misto ?? '')));
    }

    /** @param array<string, mixed> $row */
    private function normalizujRadek(array $row, $now): array
    {
        // Excel parser někdy nasypal text mimo odpovídající sloupec
        // (např. popis akce do `kontakt_telefon`). Validuj formát:
        $telefon = $row['kontakt_telefon'] ?? null;
        $email = $row['kontakt_email'] ?? null;

        // Email — pokud nemá @, není to email; pokud vypadá jako telefon, přesuň
        if ($email && !str_contains($email, '@')) {
            if (!$telefon && preg_match('/^[\d\s\+\-\(\)\.\/]+$/', $email)) {
                $telefon = $email;
            }
            $email = null;
        }
        // Telefon — pokud má znaky které nepatří do telefonu, není to telefon
        if ($telefon && !preg_match('/^[\d\s\+\-\(\)\.\/]+$/', $telefon)) {
            $telefon = null;
        }
        // Bezpečnostní ořez na limity sloupců
        $telefon = $this->trunc($telefon, 20);
        $email = $this->trunc($email, 255);

        return [
            'nazev' => $this->trunc($row['nazev'] ?? null, 255),
            'slug' => $this->trunc($row['slug'] ?? null, 255),
            'typ' => $row['typ'] ?? 'jiny',
            'misto' => $this->trunc($row['misto'] ?? null, 255),
            'kraj' => $this->trunc($row['kraj'] ?? null, 255),
            'organizator' => $this->trunc($row['organizator'] ?? null, 255),
            'kontakt_email' => $email,
            'kontakt_telefon' => $telefon,
            'web_url' => $this->trunc($row['web_url'] ?? null, 500),
            'zdroj_typ' => 'excel',
            'zdroj_url' => $this->trunc($row['zdroj_url'] ?? null, 500),
            'stav' => $row['stav'] ?? 'navrh',
            'datum_od' => !empty($row['datum_od']) ? Carbon::parse($row['datum_od'])->toDateString() : null,
            'datum_do' => !empty($row['datum_do']) ? Carbon::parse($row['datum_do'])->toDateString() : null,
            'vytvoreno' => $now,
            'upraveno' => $now,
        ];
    }

    private function trunc(?string $s, int $max): ?string
    {
        if ($s === null || $s === '') return null;
        return mb_substr($s, 0, $max);
    }
}
