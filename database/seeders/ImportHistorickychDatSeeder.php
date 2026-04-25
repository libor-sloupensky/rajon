<?php

namespace Database\Seeders;

use App\Models\Akce;
use App\Models\AkceVykaz;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Načte `database/data/historicka_data.json` a vloží do DB:
 * - `akce` — kanonické akce (1021 záznamů)
 * - `akce_vykazy` — ročníkové výkazy 2022–2025 (529 záznamů)
 *
 * JSON je vygenerovaný lokálně:
 *   php artisan excel:import temporary --export=database/data/historicka_data.json
 *
 * Spuštění přes deploy-hook:
 *   ?token=...&seed=ImportHistorickychDatSeeder
 *
 * Optimalizace pro shared hosting (PHP-FPM/proxy timeout):
 *   - Existující akce nahraju do PHP mapy 2× (slug, nazev|misto) → 2 SQL queries.
 *   - Nové akce v dávkách po 200 přes bulk INSERT (`Akce::insert()`).
 *   - Po insertu zpětně dohledám ID podle slugů (1 SELECT na dávku).
 *   - Výkazy přes `AkceVykaz::upsert()` v dávkách po 200 (1 query per dávka,
 *     idempotence díky unique [akce_id, rok]).
 *
 * Tím klesne počet DB queries z ~3000 na ~30 a celé proběhne pod 5 sekund.
 */
class ImportHistorickychDatSeeder extends Seeder
{
    private const JSON_PATH = 'database/data/historicka_data.json';

    private const CHUNK = 200;

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

        $this->command->line("Načteno: {$data['meta']['pocet_akci']} akcí, {$data['meta']['pocet_vykazu']} výkazů");

        // 1) Předem nahrát existující akce do paměti — vyhneme se per-row queries
        $existSlug = Akce::query()->pluck('id', 'slug');                 // [slug => id]
        $existByNazevMisto = Akce::query()
            ->select('id', 'nazev', 'misto')
            ->get()
            ->mapWithKeys(fn ($a) => [$this->klicNazevMisto($a->nazev, $a->misto) => $a->id]);

        /** @var array<string, int>  externi_klic → akce.id */
        $mapaKlicu = [];
        $novych = 0;
        $matched = 0;
        $now = now();

        DB::transaction(function () use ($data, &$mapaKlicu, &$novych, &$matched, $existSlug, $existByNazevMisto, $now) {
            $bufInsert = [];
            $bufKlice = [];

            foreach ($data['akce'] as $row) {
                $klic = $row['externi_klic'];
                unset($row['externi_klic']);

                // Match nejdřív podle (nazev, misto) — to byl primární klíč při exportu.
                $idExist = $existByNazevMisto[$this->klicNazevMisto($row['nazev'] ?? '', $row['misto'] ?? null)] ?? null;
                if ($idExist) {
                    $mapaKlicu[$klic] = $idExist;
                    $matched++;
                    continue;
                }

                // Vygenerovat unikátní slug podle stávající mapy
                $slug = $row['slug'] ?? Str::slug($row['nazev'] ?? 'akce');
                if ($slug === '') $slug = 'akce';
                $finalSlug = $slug;
                $i = 2;
                while (isset($existSlug[$finalSlug])) {
                    $finalSlug = $slug . '-' . $i++;
                    if ($i > 9999) break;
                }
                $row['slug'] = $finalSlug;
                $existSlug[$finalSlug] = -1; // rezervovat — ID doplníme po insertu

                $row = $this->normalizujRadek($row);
                $row['vytvoreno'] = $now;
                $row['upraveno'] = $now;
                $bufInsert[] = $row;
                $bufKlice[] = $klic;

                if (count($bufInsert) >= self::CHUNK) {
                    $novych += $this->flushInsertAkce($bufInsert, $bufKlice, $mapaKlicu);
                }
            }
            if ($bufInsert) {
                $novych += $this->flushInsertAkce($bufInsert, $bufKlice, $mapaKlicu);
            }

            // 2) Výkazy — upsert v dávkách
            $bufVykaz = [];
            foreach ($data['vykazy'] as $row) {
                $klic = $row['externi_klic'];
                if (!isset($mapaKlicu[$klic])) continue; // orphan ochrana
                $rok = (int) $row['rok'];
                unset($row['externi_klic'], $row['rok']);

                $bufVykaz[] = [
                    'akce_id' => $mapaKlicu[$klic],
                    'rok' => $rok,
                    'datum_od' => $row['datum_od'] ?? null,
                    'datum_do' => $row['datum_do'] ?? null,
                    'trzba' => $row['trzba'] ?? null,
                    'najem' => $row['najem'] ?? null,
                    'poznamka' => $row['poznamka'] ?? null,
                    'zdroj_excel' => $row['zdroj_excel'] ?? null,
                    'vytvoreno' => $now,
                    'upraveno' => $now,
                ];

                if (count($bufVykaz) >= self::CHUNK) {
                    AkceVykaz::upsert($bufVykaz, ['akce_id', 'rok'], [
                        'datum_od', 'datum_do', 'trzba', 'najem', 'poznamka', 'zdroj_excel', 'upraveno',
                    ]);
                    $bufVykaz = [];
                }
            }
            if ($bufVykaz) {
                AkceVykaz::upsert($bufVykaz, ['akce_id', 'rok'], [
                    'datum_od', 'datum_do', 'trzba', 'najem', 'poznamka', 'zdroj_excel', 'upraveno',
                ]);
            }
        });

        $this->command->line('');
        $this->command->line('=== IMPORT HISTORICKÝCH DAT ===');
        $this->command->line("Nových akcí          : {$novych}");
        $this->command->line("Match na existující  : {$matched}");
        $this->command->line("Výkazů (cca)         : " . count($data['vykazy']));
        $this->command->line('================================');
    }

    /**
     * @param array<int, array<string, mixed>> $bufInsert
     * @param array<int, string> $bufKlice
     * @param array<string, int> $mapaKlicu
     */
    private function flushInsertAkce(array &$bufInsert, array &$bufKlice, array &$mapaKlicu): int
    {
        $count = count($bufInsert);
        if ($count === 0) return 0;

        Akce::insert($bufInsert);

        // Dohled ID podle slugů
        $slugs = array_column($bufInsert, 'slug');
        $idsBySlug = Akce::whereIn('slug', $slugs)->pluck('id', 'slug');
        foreach ($bufInsert as $i => $row) {
            $klic = $bufKlice[$i];
            $mapaKlicu[$klic] = $idsBySlug[$row['slug']] ?? 0;
        }

        $bufInsert = [];
        $bufKlice = [];
        return $count;
    }

    /** Klíč pro PHP-side dedup proti existujícím akcím. */
    private function klicNazevMisto(?string $nazev, ?string $misto): string
    {
        return mb_strtolower(trim((string) $nazev)) . '|' . mb_strtolower(trim((string) ($misto ?? '')));
    }

    /**
     * Sjednotí pole pro bulk insert — vyhodí klíče které ne-fillable, převede datumy.
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizujRadek(array $row): array
    {
        $out = [];
        foreach (['nazev', 'slug', 'typ', 'misto', 'kraj', 'organizator',
                  'kontakt_email', 'kontakt_telefon', 'web_url',
                  'zdroj_typ', 'zdroj_url', 'stav'] as $k) {
            $out[$k] = $row[$k] ?? null;
        }
        $out['datum_od'] = ! empty($row['datum_od']) ? Carbon::parse($row['datum_od'])->toDateString() : null;
        $out['datum_do'] = ! empty($row['datum_do']) ? Carbon::parse($row['datum_do'])->toDateString() : null;
        return $out;
    }
}
