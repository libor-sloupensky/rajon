<?php

namespace App\Services\ExcelImport;

use App\Models\Akce;
use App\Models\AkceVykaz;
use Illuminate\Support\Str;

/**
 * Importuje jeden XLSX soubor do DB (akce + akce_vykazy).
 *
 * Strategie odpovídají bloku „Pořadí zpracování" v modules/historicka_data.md.
 * Každý soubor má svou metodu zpracujXxx() protože schéma je v každém jiné.
 */
class FileImporter
{
    /** @var array<string, string>  spl_object_hash(Akce) → externi_klic */
    private array $klicePerAkce = [];

    public function __construct(
        private readonly AkceHistoricMatcher $matcher,
        private readonly ImportStats $stats,
        private readonly bool $dryRun,
        private readonly \Closure $log,
        private readonly ?ExportCollector $collector = null,
    ) {}

    private function externiKlic(string $nazev, ?string $misto): string
    {
        $n = NazevNormalizer::normalizuj($nazev);
        $m = $misto ? NazevNormalizer::normalizuj($misto) : '';
        return $n . '|' . $m;
    }

    /** Stabilní klíč akce napříč updatey (zamrzne se při prvním poznání). */
    private function klicAkce(Akce $akce): string
    {
        $hash = spl_object_hash($akce);
        if (!isset($this->klicePerAkce[$hash])) {
            $this->klicePerAkce[$hash] = $this->externiKlic($akce->nazev, $akce->misto);
        }
        return $this->klicePerAkce[$hash];
    }

    public function zpracuj(FileSpec $spec): void
    {
        ($this->log)("=== {$spec->nazev} (strategie: {$spec->strategie}) ===");

        $sheets = XlsxReader::nactiVse($spec->cesta);

        match ($spec->strategie) {
            'mesicni_vykaz' => $this->zpracujMesicniPrehled($spec, $sheets),
            'priprava_hybrid' => $this->zpracujPripravuHybrid($spec, $sheets),
            'plan_s_rocnikovymi_trzbami' => $this->zpracujPlanSTrzbami($spec, $sheets),
            'analyza_2023' => $this->zpracujAnalyzu($spec, $sheets),
            'poute' => $this->zpracujPoute($spec, $sheets),
            'ales_4_typy' => $this->zpracujAles($spec, $sheets),
            'plan_mesicni' => $this->zpracujPlanMesicni($spec, $sheets),
            'plan_jednoduchy' => $this->zpracujPlanJednoduchy($spec, $sheets),
        };
    }

    // ---------------------------------------------------------------------
    // Měsíční přehled.xlsx — nejčistší zdroj (Tržba/Nájem/Mzda/Jméno)
    // Sheet "Říjen 2024", "Listopad 2024" … rok z názvu sheetu.
    // ---------------------------------------------------------------------
    private function zpracujMesicniPrehled(FileSpec $spec, array $sheets): void
    {
        foreach ($sheets as $sheetName => $radky) {
            $rok = self::rokZeSheetu($sheetName) ?? $spec->defaultRok;
            $h = XlsxReader::najdiHlavicku($radky);
            if (!$h) continue;

            for ($i = $h['index'] + 1; $i < count($radky); $i++) {
                $rawRow = $this->mapujRadek($radky[$i], $h['mapping']);
                $nazev = trim((string) ($rawRow['nazev'] ?? ''));
                if ($nazev === '' || str_contains($nazev, 'Celkem')) continue;

                $datum = DateParser::parse($rawRow['datum'] ?? null, $rok);
                $akce = $this->najdiNeboVytvor($nazev, null, 'jiny', $spec, $rawRow, $datum);
                if (!$akce) continue;

                $this->zapisVykaz($akce, $rok, $datum, [
                    'trzba' => MoneyParser::parse($rawRow['trzba'] ?? null),
                    'najem' => MoneyParser::parse($rawRow['najem'] ?? null),
                ], $spec, $sheetName);
            }
        }
    }

    // ---------------------------------------------------------------------
    // Příprava 2024 výkaz 2023.xlsx — hybrid:
    //   Únor–Květen: výkazy 2023 (PRODÁNO, obrat)
    //   Červen–Prosinec: plán 2024 + "Poznámky z minulých let" = 2023 výkaz
    //   Rezervované akce: plán 2024 s stavem
    // ---------------------------------------------------------------------
    private function zpracujPripravuHybrid(FileSpec $spec, array $sheets): void
    {
        $vykazMesice = ['Únor', 'Březen', 'Duben', 'Květen'];
        $planMesice = ['Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'];

        foreach ($sheets as $sheetName => $radky) {
            $h = XlsxReader::najdiHlavicku($radky);
            if (!$h) continue;

            if (in_array($sheetName, $vykazMesice, true)) {
                $this->importVykazMesic($radky, $h, rokVykazu: 2023, spec: $spec, sheetName: $sheetName);
            } elseif (in_array($sheetName, $planMesice, true)) {
                $this->importPlanMesic($radky, $h, rokPlan: 2024, spec: $spec, sheetName: $sheetName);
            } elseif ($sheetName === 'Rezervované akce') {
                $this->importPlanMesic($radky, $h, rokPlan: 2024, spec: $spec, sheetName: $sheetName);
            }
        }
    }

    // ---------------------------------------------------------------------
    // Příprava 2025 výkaz 2024 v1..xlsx — plán 2025 + parsování "tržba 2024"
    // ---------------------------------------------------------------------
    private function zpracujPlanSTrzbami(FileSpec $spec, array $sheets): void
    {
        foreach ($sheets as $sheetName => $radky) {
            $h = XlsxReader::najdiHlavicku($radky);
            if (!$h) continue;
            $this->importPlanMesic($radky, $h, rokPlan: 2025, spec: $spec, sheetName: $sheetName);
        }
    }

    private function importVykazMesic(array $radky, array $h, int $rokVykazu, FileSpec $spec, string $sheetName): void
    {
        for ($i = $h['index'] + 1; $i < count($radky); $i++) {
            $raw = $this->mapujRadek($radky[$i], $h['mapping']);
            $nazev = trim((string) ($raw['nazev'] ?? ''));
            if ($nazev === '') continue;

            $datum = DateParser::parse($raw['datum'] ?? null, $rokVykazu);
            $akce = $this->najdiNeboVytvor($nazev, null, 'jiny', $spec, $raw, $datum);
            if (!$akce) continue;

            $this->zapisVykaz($akce, $rokVykazu, $datum, [
                'trzba' => MoneyParser::parse($raw['trzba'] ?? null),
                'najem' => MoneyParser::parse($raw['najem'] ?? null),
            ], $spec, $sheetName);
        }
    }

    private function importPlanMesic(array $radky, array $h, int $rokPlan, FileSpec $spec, string $sheetName): void
    {
        for ($i = $h['index'] + 1; $i < count($radky); $i++) {
            $raw = $this->mapujRadek($radky[$i], $h['mapping']);
            $nazev = trim((string) ($raw['nazev'] ?? ''));
            if ($nazev === '') continue;

            $datum = DateParser::parse($raw['datum'] ?? null, $rokPlan);
            $misto = trim((string) ($raw['misto'] ?? ''));
            $akce = $this->najdiNeboVytvor($nazev, $misto ?: null, 'jiny', $spec, $raw, $datum);
            if (!$akce) continue;

            // Případný výkaz v roce plánu (pokud má trzba/najem)
            $trzba = MoneyParser::parse($raw['trzba'] ?? null);
            $najem = MoneyParser::parse($raw['najem'] ?? null);
            if ($trzba !== null || $najem !== null) {
                $this->zapisVykaz($akce, $rokPlan, $datum, ['trzba' => $trzba, 'najem' => $najem], $spec, $sheetName);
            }

            // Poznámky z minulých let → extra výkazy
            $poznamkaText = (string) (($raw['poznamky_minulych_let'] ?? '') . ' ' . ($raw['trzba'] ?? '') . ' ' . ($raw['poznamka'] ?? ''));
            foreach (PoznamkyParser::vytahni($poznamkaText) as $mv) {
                $this->zapisVykaz($akce, $mv['rok'], ['datum_od' => null, 'datum_do' => null],
                    ['trzba' => $mv['trzba'], 'najem' => $mv['najem']], $spec, $sheetName . ' (poznámky)');
            }
        }
    }

    // ---------------------------------------------------------------------
    // Analýza festivaly.xlsx — 28 top akcí 2023, Sheet1:
    //   [nazev, datum, dní, celkem, na den, FB vlastní stránka, FB příspěvek]
    // ---------------------------------------------------------------------
    private function zpracujAnalyzu(FileSpec $spec, array $sheets): void
    {
        $radky = $sheets['Sheet1'] ?? null;
        if (!$radky) return;

        // Hlavička netradiční, první 2 sloupce bez názvu → r1: ['', '', 'dní', 'celkem', ...]
        // Datové řádky: ['Název', 'termin', 'dní', 'celkem', 'na den', 'FB stránka', 'FB příspěvek']
        foreach ($radky as $idx => $row) {
            if ($idx === 0) continue; // header
            $nazev = trim((string) ($row[0] ?? ''));
            if ($nazev === '') continue;

            $datum = DateParser::parse($row[1] ?? null, 2023);
            $trzba = MoneyParser::parse($row[3] ?? null);
            $fbPozn = trim((string) ($row[5] ?? '') . ' ' . (string) ($row[6] ?? ''));

            $akce = $this->najdiNeboVytvor($nazev, null, 'jiny', $spec, [], $datum);
            if (!$akce) continue;

            $this->zapisVykaz($akce, 2023, $datum, [
                'trzba' => $trzba,
                'najem' => null,
                'poznamka' => trim($fbPozn) ?: null,
            ], $spec, 'Sheet1');
        }
    }

    // ---------------------------------------------------------------------
    // Poutě 2025.xlsx — typ=pout pro všechny měsíce, typ=sportovni_akce pro Pochody
    // ---------------------------------------------------------------------
    private function zpracujPoute(FileSpec $spec, array $sheets): void
    {
        foreach ($sheets as $sheetName => $radky) {
            $typ = $sheetName === 'Pochody' ? 'sportovni_akce' : 'pout';

            // Hlavička je vždy řádek 0 — [Akce, Místo, Kraj, datum, Návštěvnost, Nájem / Poznámky]
            if (empty($radky)) continue;
            $h = ['index' => 0, 'mapping' => HeaderMapper::mapuj($radky[0]), 'header' => $radky[0]];

            for ($i = 1; $i < count($radky); $i++) {
                $raw = $this->mapujRadek($radky[$i], $h['mapping']);
                $nazev = trim((string) ($raw['nazev'] ?? ''));
                if ($nazev === '') continue;

                $datum = DateParser::parse($raw['datum'] ?? null, $spec->defaultRok);
                $misto = trim((string) ($raw['misto'] ?? ''));
                $kraj = trim((string) ($raw['kraj'] ?? ''));

                $akce = $this->najdiNeboVytvor($nazev, $misto ?: null, $typ, $spec, $raw, $datum);
                if (!$akce) continue;

                // Poutě mají často nájem zapsaný v "Nájem / Poznámky" — pokus o extrakci
                $najem = MoneyParser::parse($raw['poznamka'] ?? null);
                if ($najem !== null) {
                    $this->zapisVykaz($akce, $spec->defaultRok, $datum,
                        ['trzba' => null, 'najem' => $najem], $spec, $sheetName);
                }

                // Uložit signály velikosti (návštěvnost)
                if (!empty($raw['navstevnost'])) {
                    $this->rozsirVelikostSignaly($akce, 'navstevnost', (string) $raw['navstevnost']);
                }
                if (!$akce->kraj && $kraj !== '') {
                    $akce->kraj = $this->normalizujKraj($kraj);
                    if (!$this->dryRun) $akce->save();
                }
            }
        }
    }

    // ---------------------------------------------------------------------
    // Festivaly 2024 (2.část) - Trhy Aleš.xlsx — 4 tematické sheety
    // ---------------------------------------------------------------------
    private function zpracujAles(FileSpec $spec, array $sheets): void
    {
        $typMap = [
            'České festivaly vína 2024' => 'vinobrani',
            'Food festivaly 2024' => 'food_festival',
            'Craft beer festy 2024' => 'festival',
            'Řemeslné trhy a jarmarky 2024' => 'jarmark',
        ];

        foreach ($sheets as $sheetName => $radky) {
            $typ = $typMap[$sheetName] ?? 'jiny';
            if (empty($radky)) continue;

            // Hlavička: [Datum, Název akce, Ročník, Místo konání, Čas konání, Očekávaná návštěvnost]
            $header = $radky[0];
            $mapping = [];
            foreach ($header as $i => $col) {
                $norm = HeaderMapper::prelozSloupec((string) ($col ?? ''));
                $mapping[$i] = $norm;
            }

            for ($i = 1; $i < count($radky); $i++) {
                $raw = $this->mapujRadek($radky[$i], $mapping);
                $nazev = trim((string) ($raw['nazev'] ?? ''));
                if ($nazev === '') continue;

                $datum = DateParser::parse($raw['datum'] ?? null, 2024);
                $misto = trim((string) ($raw['misto'] ?? ''));

                $akce = $this->najdiNeboVytvor($nazev, $misto ?: null, $typ, $spec, $raw, $datum);
                if (!$akce) continue;

                if (!empty($raw['rocnik'])) {
                    $this->rozsirVelikostSignaly($akce, 'rocnik', (string) $raw['rocnik']);
                }
                if (!empty($raw['navstevnost'])) {
                    $this->rozsirVelikostSignaly($akce, 'navstevnost', (string) $raw['navstevnost']);
                }
                if (!empty($raw['cas_konani'])) {
                    $this->rozsirVelikostSignaly($akce, 'otevreno', (string) $raw['cas_konani']);
                }
            }
        }
    }

    // ---------------------------------------------------------------------
    // Festivaly 2022/2023.xlsx — plán po měsících, bez trzeb
    // ---------------------------------------------------------------------
    private function zpracujPlanMesicni(FileSpec $spec, array $sheets): void
    {
        foreach ($sheets as $sheetName => $radky) {
            if ($sheetName === 'Vánoční akce') continue; // volný text, přeskočit

            $h = XlsxReader::najdiHlavicku($radky);
            if (!$h) continue;

            for ($i = $h['index'] + 1; $i < count($radky); $i++) {
                $raw = $this->mapujRadek($radky[$i], $h['mapping']);
                $nazev = trim((string) ($raw['nazev'] ?? ''));
                if ($nazev === '') continue;

                $datum = DateParser::parse($raw['datum'] ?? null, $spec->defaultRok);
                $misto = trim((string) ($raw['misto'] ?? ''));

                $akce = $this->najdiNeboVytvor($nazev, $misto ?: null, 'jiny', $spec, $raw, $datum);
                if (!$akce) continue;

                // V těchto souborech bývá `cena najmu` občas vyplněný, i "trzba cca"
                $najem = MoneyParser::parse($raw['najem'] ?? null);
                $trzba = MoneyParser::parse($raw['trzba'] ?? null);
                if ($najem !== null || $trzba !== null) {
                    $this->zapisVykaz($akce, $spec->defaultRok, $datum,
                        ['trzba' => $trzba, 'najem' => $najem], $spec, $sheetName);
                }
            }
        }
    }

    // ---------------------------------------------------------------------
    // Festivaly 2024 (1.část).xlsx — jeden sheet List1, jednoduchý kontaktní list
    // ---------------------------------------------------------------------
    private function zpracujPlanJednoduchy(FileSpec $spec, array $sheets): void
    {
        foreach ($sheets as $sheetName => $radky) {
            if (empty($radky)) continue;

            // Hlavička je 1. řádek
            $header = $radky[0];
            $mapping = HeaderMapper::mapuj($header);
            // Ověřit, že to je opravdu hlavička
            if (!in_array('nazev', $mapping, true) && !in_array('datum', $mapping, true)) continue;

            for ($i = 1; $i < count($radky); $i++) {
                $raw = $this->mapujRadek($radky[$i], $mapping);
                $nazev = trim((string) ($raw['nazev'] ?? ''));
                if ($nazev === '') continue;

                $datum = DateParser::parse($raw['datum'] ?? null, $spec->defaultRok);
                $misto = trim((string) ($raw['misto'] ?? ''));
                $this->najdiNeboVytvor($nazev, $misto ?: null, 'jiny', $spec, $raw, $datum);
            }
        }
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    /**
     * @param list<mixed> $row
     * @param array<int, ?string> $mapping
     * @return array<string, mixed>  klíč = normalizovaný název
     */
    private function mapujRadek(array $row, array $mapping): array
    {
        $r = [];
        foreach ($mapping as $idx => $key) {
            if ($key === null) continue;
            $r[$key] = $row[$idx] ?? null;
        }
        return $r;
    }

    /**
     * Najdi existující akci nebo vytvoř novou + doplň kontaktní data.
     *
     * @param array<string, mixed> $raw
     * @param array{datum_od: ?string, datum_do: ?string} $datum
     */
    private function najdiNeboVytvor(
        string $nazev,
        ?string $misto,
        string $typ,
        FileSpec $spec,
        array $raw,
        array $datum,
    ): ?Akce {
        $akce = $this->matcher->najdi($nazev, $misto);

        if ($akce) {
            $this->stats->matched++;
            $klic = $akce->nazev . ' @ ' . ($akce->misto ?? '-');
            $this->stats->matchePerAkce[$klic] = ($this->stats->matchePerAkce[$klic] ?? 1) + 1;

            // V export módu doplň prázdné atributy existujícího záznamu (merge)
            if ($this->collector) {
                $externiKlic = $this->klicAkce($akce);
                $doplnit = [];
                foreach (['organizator' => 'organizator', 'kontakt_email' => 'mail', 'kontakt_telefon' => 'mobil', 'web_url' => 'web'] as $dbKey => $rawKey) {
                    if (!empty($raw[$rawKey])) {
                        $doplnit[$dbKey] = mb_substr(trim((string) $raw[$rawKey]), 0, $dbKey === 'web_url' ? 500 : 255);
                    }
                }
                if (!empty($raw['kraj'])) $doplnit['kraj'] = $this->normalizujKraj((string) $raw['kraj']);
                if ($doplnit) $this->collector->ulozAkci($externiKlic, $doplnit);
            }
            // Doplnit prázdná kontaktní pole
            $zmeneno = false;
            foreach (['organizator', 'kontakt_email' => 'mail', 'kontakt_telefon' => 'mobil', 'web_url' => 'web'] as $dbKey => $rawKey) {
                if (is_int($dbKey)) { $dbKey = $rawKey; }
                $v = $raw[$rawKey] ?? null;
                if (!empty($v) && empty($akce->{$dbKey}) && !$akce->jePoleUzamceno($dbKey)) {
                    $akce->{$dbKey} = mb_substr(trim((string) $v), 0, $dbKey === 'web_url' ? 500 : 255);
                    $zmeneno = true;
                }
            }
            if (!$akce->kraj && !empty($raw['kraj'])) {
                $akce->kraj = $this->normalizujKraj((string) $raw['kraj']);
                $zmeneno = true;
            }
            if (!$akce->misto && $misto) {
                $akce->misto = mb_substr($misto, 0, 255);
                $zmeneno = true;
            }
            if ($zmeneno && !$this->dryRun) $akce->save();
            return $akce;
        }

        // Nová akce
        $this->stats->created++;
        $akce = new Akce();
        $akce->nazev = mb_substr($nazev, 0, 255);
        $akce->slug = $this->vygenerujSlug($nazev);
        $akce->typ = $typ;
        $akce->misto = $misto ? mb_substr($misto, 0, 255) : null;
        if (!empty($raw['kraj'])) $akce->kraj = $this->normalizujKraj((string) $raw['kraj']);
        if (!empty($raw['organizator'])) $akce->organizator = mb_substr(trim((string) $raw['organizator']), 0, 255);
        if (!empty($raw['mail'])) $akce->kontakt_email = mb_substr(trim((string) $raw['mail']), 0, 255);
        if (!empty($raw['mobil'])) $akce->kontakt_telefon = mb_substr(trim((string) $raw['mobil']), 0, 20);
        if (!empty($raw['web'])) $akce->web_url = mb_substr(trim((string) $raw['web']), 0, 500);
        $akce->datum_od = $datum['datum_od'];
        $akce->datum_do = $datum['datum_do'];
        $akce->zdroj_typ = 'excel';
        $akce->zdroj_url = $spec->nazev;
        $akce->stav = 'navrh';

        if (!$this->dryRun) {
            $akce->save();
        } else {
            $akce->id = 0; // pro dry-run ať matcher cache funguje
        }

        $this->matcher->pridej($akce);
        $this->klicAkce($akce); // zamrazit klíč pro budoucí updaty

        // V export módu zapiš novou akci do collectoru
        if ($this->collector) {
            $externiKlic = $this->externiKlic($akce->nazev, $akce->misto);
            $this->collector->ulozAkci($externiKlic, [
                'nazev' => $akce->nazev,
                'slug' => $akce->slug,
                'typ' => $akce->typ,
                'misto' => $akce->misto,
                'kraj' => $akce->kraj,
                'organizator' => $akce->organizator,
                'kontakt_email' => $akce->kontakt_email,
                'kontakt_telefon' => $akce->kontakt_telefon,
                'web_url' => $akce->web_url,
                'datum_od' => $akce->datum_od,
                'datum_do' => $akce->datum_do,
                'zdroj_typ' => 'excel',
                'zdroj_url' => $akce->zdroj_url,
                'stav' => 'navrh',
            ]);
        }
        return $akce;
    }

    private function vygenerujSlug(string $nazev): string
    {
        $base = Str::slug($nazev);
        if ($base === '') $base = 'akce';
        if ($this->dryRun) return $base;

        // unique: v reálu se to v DB hlídá, zde generujeme unikátní variantu
        $slug = $base;
        $i = 2;
        while (Akce::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
            if ($i > 999) break;
        }
        return $slug;
    }

    /**
     * @param array{datum_od: ?string, datum_do: ?string} $datum
     * @param array{trzba?: ?int, najem?: ?int, poznamka?: ?string} $vykaz
     */
    private function zapisVykaz(Akce $akce, int $rok, array $datum, array $vykaz, FileSpec $spec, string $sheetName): void
    {
        if ($rok < 2020 || $rok > 2030) return;
        if (($vykaz['trzba'] ?? null) === null && ($vykaz['najem'] ?? null) === null
            && empty($vykaz['poznamka']) && !$datum['datum_od']) {
            return; // nic k uložení
        }

        $zdroj = $spec->nazev . ' / ' . $sheetName;
        $atributy = [
            'datum_od' => $datum['datum_od'],
            'datum_do' => $datum['datum_do'],
            'trzba' => $vykaz['trzba'] ?? null,
            'najem' => $vykaz['najem'] ?? null,
            'poznamka' => $vykaz['poznamka'] ?? null,
            'zdroj_excel' => $zdroj,
        ];

        // Export mód — zapiš do collectoru
        if ($this->collector) {
            $externiKlic = $this->klicAkce($akce);
            // Pojistka: pokud akce v collectoru (ještě) není, bootstrapni ji z aktuálního
            // stavu objektu. Stává se, když se klíč zamrazil až po změně misto/kraj v matched větvi.
            if (!isset($this->collector->akce[$externiKlic])) {
                $this->collector->ulozAkci($externiKlic, [
                    'nazev' => $akce->nazev,
                    'slug' => $akce->slug,
                    'typ' => $akce->typ,
                    'misto' => $akce->misto,
                    'kraj' => $akce->kraj,
                    'organizator' => $akce->organizator,
                    'kontakt_email' => $akce->kontakt_email,
                    'kontakt_telefon' => $akce->kontakt_telefon,
                    'web_url' => $akce->web_url,
                    'datum_od' => $akce->datum_od,
                    'datum_do' => $akce->datum_do,
                    'zdroj_typ' => 'excel',
                    'zdroj_url' => $akce->zdroj_url,
                    'stav' => 'navrh',
                ]);
            }
            $this->collector->ulozVykaz($externiKlic, $rok, $atributy);
            $this->stats->vykazy++;
            return;
        }

        if ($this->dryRun) {
            $this->stats->vykazy++;
            return;
        }

        AkceVykaz::updateOrCreate(
            ['akce_id' => $akce->id, 'rok' => $rok],
            $atributy,
        );
        $this->stats->vykazy++;
    }

    private function rozsirVelikostSignaly(Akce $akce, string $klic, string $hodnota): void
    {
        $s = $akce->velikost_signaly ?? [];
        if (!isset($s[$klic])) {
            $s[$klic] = trim($hodnota);
            $akce->velikost_signaly = $s;
            if (!$this->dryRun) $akce->save();
        }
    }

    private function normalizujKraj(string $kraj): string
    {
        $kraj = trim($kraj);
        $map = [
            'Středočeský' => 'Středočeský kraj',
            'Jihočeský' => 'Jihočeský kraj',
            'Plzeňský' => 'Plzeňský kraj',
            'Karlovarský' => 'Karlovarský kraj',
            'Ústecký' => 'Ústecký kraj',
            'Liberecký' => 'Liberecký kraj',
            'Královéhradecký' => 'Královéhradecký kraj',
            'Královehradecký' => 'Královéhradecký kraj',
            'Pardubický' => 'Pardubický kraj',
            'Vysočina' => 'Kraj Vysočina',
            'Jihomoravský' => 'Jihomoravský kraj',
            'Olomoucký' => 'Olomoucký kraj',
            'Olomouc' => 'Olomoucký kraj',
            'Zlínský' => 'Zlínský kraj',
            'Moravskoslezský' => 'Moravskoslezský kraj',
            'Plzeńský' => 'Plzeňský kraj',
            'Jihočeský ' => 'Jihočeský kraj',
            'Jihomoravský ' => 'Jihomoravský kraj',
        ];
        return $map[$kraj] ?? $kraj;
    }

    private static function rokZeSheetu(string $sheetName): ?int
    {
        if (preg_match('/\b(20\d{2})\b/', $sheetName, $m)) {
            return (int) $m[1];
        }
        return null;
    }
}
