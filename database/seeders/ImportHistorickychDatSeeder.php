<?php

namespace Database\Seeders;

use App\Models\Akce;
use App\Models\AkceVykaz;
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
 * Idempotence — pokud akce se shodným `slug` nebo (nazev, misto) už v DB je,
 * seeder ji přeskočí a aktualizuje jen prázdná pole. Výkaz (akce_id, rok) je
 * unique; updateOrCreate aktualizuje existující.
 */
class ImportHistorickychDatSeeder extends Seeder
{
    private const JSON_PATH = 'database/data/historicka_data.json';

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

        /** @var array<string, int>  externi_klic → akce.id */
        $mapaKlicu = [];
        $novych = 0;
        $doplneno = 0;

        DB::transaction(function () use ($data, &$mapaKlicu, &$novych, &$doplneno) {
            foreach ($data['akce'] as $row) {
                $klic = $row['externi_klic'];
                unset($row['externi_klic']);

                // Zajištění unikátního slugu
                $slug = $row['slug'] ?? Str::slug($row['nazev'] ?? 'akce');
                $row['slug'] = $this->unikatniSlug($slug);

                // Datumy jako strings zůstanou (Eloquent umí oba typy)
                $existujici = Akce::where('slug', $row['slug'])->first();
                if (!$existujici) {
                    // Pokus o shodu podle (nazev, misto) — prevence duplicit
                    $existujici = Akce::where('nazev', $row['nazev'])
                        ->where('misto', $row['misto'] ?? null)
                        ->first();
                }

                if ($existujici) {
                    $zmeneno = false;
                    foreach ($row as $k => $v) {
                        if ($v === null || $v === '') continue;
                        if (empty($existujici->{$k})) {
                            $existujici->{$k} = $v;
                            $zmeneno = true;
                        }
                    }
                    if ($zmeneno) $existujici->save();
                    $mapaKlicu[$klic] = $existujici->id;
                    $doplneno++;
                } else {
                    $akce = Akce::create($row);
                    $mapaKlicu[$klic] = $akce->id;
                    $novych++;
                }
            }

            foreach ($data['vykazy'] as $row) {
                $klic = $row['externi_klic'];
                if (!isset($mapaKlicu[$klic])) {
                    // Neměl by nastat — JSON je v importu ověřen. Přeskočit.
                    continue;
                }
                $rok = (int) $row['rok'];
                unset($row['externi_klic'], $row['rok']);

                AkceVykaz::updateOrCreate(
                    ['akce_id' => $mapaKlicu[$klic], 'rok' => $rok],
                    $row,
                );
            }
        });

        $this->command->line('');
        $this->command->line('=== IMPORT HISTORICKÝCH DAT ===');
        $this->command->line("Nových akcí  : {$novych}");
        $this->command->line("Aktualizováno: {$doplneno}");
        $this->command->line("Výkazů       : " . count($data['vykazy']));
        $this->command->line('================================');
    }

    /**
     * Pokud slug už v DB je, přidá -2, -3… dokud nenajde volný.
     * (U akce která už existuje se stejně použije match → update, tohle je jen
     * pojistka pro čerstvé inserty.)
     */
    private function unikatniSlug(string $base): string
    {
        $slug = $base;
        $i = 2;
        while (Akce::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
            if ($i > 9999) break;
        }
        return $slug;
    }
}
