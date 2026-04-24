<?php

namespace App\Services\ExcelImport;

/**
 * Sbírá data z parsování XLSX do dvou seznamů, které se uloží jako JSON.
 *
 * Klíč `externi_klic` (slug-normalizovaný název + místo) slouží v seederu
 * k propojení výkazů na akce — nahrazuje `akce_id`, který neznáme před
 * vlastním insertem.
 */
class ExportCollector
{
    /** @var array<string, array<string, mixed>>  externi_klic → atributy akce */
    public array $akce = [];

    /** @var list<array<string, mixed>>  každý vykaz má 'externi_klic' */
    public array $vykazy = [];

    /**
     * Ulož nebo update akci (idempotentně — opakované volání stejného klíče doplní prázdná pole).
     *
     * @param array<string, mixed> $atributy  sloupce akce (nazev, slug, typ, …)
     */
    public function ulozAkci(string $externiKlic, array $atributy): void
    {
        if (!isset($this->akce[$externiKlic])) {
            $this->akce[$externiKlic] = array_merge(['externi_klic' => $externiKlic], $atributy);
            return;
        }
        // Doplň prázdná pole, nepřepisuj už vyplněná
        foreach ($atributy as $klic => $hodnota) {
            if ($hodnota === null || $hodnota === '') continue;
            if (!isset($this->akce[$externiKlic][$klic])
                || $this->akce[$externiKlic][$klic] === null
                || $this->akce[$externiKlic][$klic] === '') {
                $this->akce[$externiKlic][$klic] = $hodnota;
            }
        }
    }

    /**
     * Přidej nebo sluč výkaz. Klíč (externi_klic + rok) musí být unikátní.
     *
     * @param array<string, mixed> $atributy
     */
    public function ulozVykaz(string $externiKlic, int $rok, array $atributy): void
    {
        $idx = $this->najdiVykazIdx($externiKlic, $rok);
        $atributy = array_merge(['externi_klic' => $externiKlic, 'rok' => $rok], $atributy);

        if ($idx === null) {
            $this->vykazy[] = $atributy;
            return;
        }
        // Merge — doplň prázdná pole
        foreach ($atributy as $k => $v) {
            if ($v === null || $v === '') continue;
            if (!isset($this->vykazy[$idx][$k]) || $this->vykazy[$idx][$k] === null) {
                $this->vykazy[$idx][$k] = $v;
            }
        }
    }

    private function najdiVykazIdx(string $externiKlic, int $rok): ?int
    {
        foreach ($this->vykazy as $i => $v) {
            if ($v['externi_klic'] === $externiKlic && $v['rok'] === $rok) return $i;
        }
        return null;
    }

    public function toJson(): string
    {
        return json_encode(
            [
                'meta' => [
                    'generated_at' => date('c'),
                    'pocet_akci' => count($this->akce),
                    'pocet_vykazu' => count($this->vykazy),
                ],
                'akce' => array_values($this->akce),
                'vykazy' => $this->vykazy,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
