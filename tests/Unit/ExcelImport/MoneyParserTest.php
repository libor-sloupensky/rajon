<?php

namespace Tests\Unit\ExcelImport;

use App\Services\ExcelImport\MoneyParser;
use PHPUnit\Framework\TestCase;

class MoneyParserTest extends TestCase
{
    public function test_cele_cislo(): void
    {
        $this->assertSame(6480, MoneyParser::parse(6480));
        $this->assertSame(6480, MoneyParser::parse(6480.0));
    }

    public function test_string_bez_mezery(): void
    {
        $this->assertSame(3000, MoneyParser::parse('3000bez dph'));
        $this->assertSame(1500, MoneyParser::parse('1500,- kč'));
    }

    public function test_string_s_mezerami(): void
    {
        $this->assertSame(40000, MoneyParser::parse('tržba 2024 cca 40 000,-'));
        $this->assertSame(11495, MoneyParser::parse('11495,-'));
    }

    public function test_prazdny_vstup(): void
    {
        $this->assertNull(MoneyParser::parse(null));
        $this->assertNull(MoneyParser::parse(''));
    }

    public function test_nenumericky_volny_text(): void
    {
        $this->assertNull(MoneyParser::parse('tisíce'));
        $this->assertNull(MoneyParser::parse('desetitisíce lidí'));
    }

    public function test_procenta_se_ignoruji(): void
    {
        $this->assertNull(MoneyParser::parse('NFC 20%'));
        $this->assertNull(MoneyParser::parse('18% NFC'));
    }

    public function test_rok_se_ignoruje(): void
    {
        // "tržba 2024 cca 40 000" — vezme 40000, ne 2024
        $this->assertSame(40000, MoneyParser::parse('2024 cca 40 000 kč'));
    }

    public function test_vice_cisel_vezme_nejvyssi(): void
    {
        $this->assertSame(50000, MoneyParser::parse('50000 Kč, poplatek 500,-'));
    }
}
