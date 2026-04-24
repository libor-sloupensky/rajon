<?php

namespace App\Services\ExcelImport;

use OpenSpout\Reader\XLSX\Reader;

/**
 * Wrapper kolem openspout — načte celý XLSX naráz (soubory jsou < 250 KB,
 * pár stovek řádků per sheet). Umožňuje více průchodů.
 */
class XlsxReader
{
    /**
     * @return array<string, list<list<mixed>>>  sheet_name → list řádků → list buněk
     */
    public static function nactiVse(string $cesta): array
    {
        $reader = new Reader();
        $reader->open($cesta);

        $result = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $rows = [];
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
                $result[$sheet->getName()] = $rows;
            }
        } finally {
            $reader->close();
        }
        return $result;
    }

    /**
     * Najdi index řádku s hlavičkou a namapuj jeho sloupce.
     *
     * @param list<list<mixed>> $radky
     * @return array{index: int, mapping: array<int, ?string>, header: list<string>}|null
     */
    public static function najdiHlavicku(array $radky): ?array
    {
        foreach ($radky as $idx => $row) {
            $lower = array_map(fn ($v) => is_scalar($v) ? strtolower(trim((string) $v)) : '', $row);
            $hasNazev = array_intersect($lower, ['nazev', 'akce', 'název']);
            $hasDatum = array_intersect($lower, ['datum', 'termin', 'termín']);
            if (!empty($hasNazev) && !empty($hasDatum)) {
                return [
                    'index' => $idx,
                    'mapping' => HeaderMapper::mapuj($row),
                    'header' => array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $row),
                ];
            }
        }
        return null;
    }
}
