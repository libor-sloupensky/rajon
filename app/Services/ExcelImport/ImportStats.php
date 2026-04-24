<?php

namespace App\Services\ExcelImport;

class ImportStats
{
    public int $created = 0;
    public int $matched = 0;
    public int $vykazy = 0;

    /** @var array<string, int>  klíč = nazev@misto, value = kolikrát matched */
    public array $matchePerAkce = [];
}
