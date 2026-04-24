<?php

namespace Tests\Unit\ExcelImport;

use App\Services\ExcelImport\PoznamkyParser;
use PHPUnit\Framework\TestCase;

class PoznamkyParserTest extends TestCase
{
    public function test_prodej_s_najmem(): void
    {
        $r = PoznamkyParser::vytahni('2023: Prodej 6480 Kč, Nájem 1350 Kč');
        $this->assertCount(1, $r);
        $this->assertSame(2023, $r[0]['rok']);
        $this->assertSame(6480, $r[0]['trzba']);
        $this->assertSame(1350, $r[0]['najem']);
    }

    public function test_prodej_bez_najmu(): void
    {
        $r = PoznamkyParser::vytahni('2023: Prodej 55509 Kč, Nájem 20%');
        // 20% se ignoruje (procento)
        $this->assertCount(1, $r);
        $this->assertSame(55509, $r[0]['trzba']);
        $this->assertNull($r[0]['najem']);
    }

    public function test_prazdny_vstup(): void
    {
        $this->assertSame([], PoznamkyParser::vytahni(''));
        $this->assertSame([], PoznamkyParser::vytahni('volný text bez dat'));
    }

    public function test_trzba_rok_varianta(): void
    {
        $r = PoznamkyParser::vytahni('tržba 2024 - cca 40 000,-');
        $this->assertCount(1, $r);
        $this->assertSame(2024, $r[0]['rok']);
        $this->assertSame(40000, $r[0]['trzba']);
    }

    public function test_rok_se_nededupikuje(): void
    {
        // Stejný rok víckrát — bere první
        $r = PoznamkyParser::vytahni('2023: Prodej 1000 Kč. 2023: Prodej 2000 Kč');
        $this->assertCount(1, $r);
        $this->assertSame(1000, $r[0]['trzba']);
    }
}
