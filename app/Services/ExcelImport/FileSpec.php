<?php

namespace App\Services\ExcelImport;

/**
 * Metadata o jednom zdrojovém XLSX souboru.
 */
class FileSpec
{
    public function __construct(
        public readonly string $nazev,       // "Měsíční přehled.xlsx"
        public readonly string $cesta,       // absolutní cesta
        public readonly int $defaultRok,     // 2022 / 2023 / 2024 / 2025
        public readonly string $strategie,   // viz FileImporter::zpracuj()
    ) {}

    /**
     * Vrátí seznam FileSpec v pořadí podle modules/historicka_data.md § 6.
     *
     * @return list<FileSpec>
     */
    public static function vse(string $adresar): array
    {
        return [
            new self('Měsíční přehled.xlsx',                  "$adresar/Měsíční přehled.xlsx",                  2024, 'mesicni_vykaz'),
            new self('Příprava 2024 výkaz 2023.xlsx',         "$adresar/Příprava 2024 výkaz 2023.xlsx",         2024, 'priprava_hybrid'),
            new self('Příprava 2025 výkaz 2024 v1..xlsx',     "$adresar/Příprava 2025 výkaz 2024 v1..xlsx",     2025, 'plan_s_rocnikovymi_trzbami'),
            new self('Analýza festivaly.xlsx',                "$adresar/Analýza festivaly.xlsx",                2023, 'analyza_2023'),
            new self('Poutě 2025.xlsx',                       "$adresar/Poutě 2025.xlsx",                       2025, 'poute'),
            new self('Festivaly 2024 (2.část) - Trhy Aleš.xlsx', "$adresar/Festivaly 2024 (2.část) - Trhy Aleš.xlsx", 2024, 'ales_4_typy'),
            new self('Festivaly 2024 (1.část).xlsx',          "$adresar/Festivaly 2024 (1.část).xlsx",          2024, 'plan_jednoduchy'),
            new self('Festivaly 2023.xlsx',                   "$adresar/Festivaly 2023.xlsx",                   2023, 'plan_mesicni'),
            new self('Festivaly 2022.xlsx',                   "$adresar/Festivaly 2022.xlsx",                   2022, 'plan_mesicni'),
        ];
    }
}
