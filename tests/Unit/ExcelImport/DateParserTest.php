<?php

namespace Tests\Unit\ExcelImport;

use App\Services\ExcelImport\DateParser;
use Carbon\Carbon;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DateParserTest extends TestCase
{
    public function test_parse_datetime_immutable(): void
    {
        $d = new DateTimeImmutable('2023-06-15');
        $r = DateParser::parse($d, 2023);
        $this->assertSame('2023-06-15', $r['datum_od']);
        $this->assertSame('2023-06-15', $r['datum_do']);
    }

    public function test_excel_posunuty_rok_se_opravi(): void
    {
        // Buňka říká 2022, kontext souboru 2023
        $d = new DateTimeImmutable('2022-02-18');
        $r = DateParser::parse($d, 2023);
        $this->assertSame('2023-02-18', $r['datum_od']);
    }

    public function test_textovy_rozsah_den_pomlcka_den_mesic(): void
    {
        // "9-10.6."
        $r = DateParser::parseText('9-10.6.', 2023);
        $this->assertSame('2023-06-09', $r['datum_od']);
        $this->assertSame('2023-06-10', $r['datum_do']);
    }

    public function test_textovy_rozsah_s_teckami(): void
    {
        // "5.-9.7."
        $r = DateParser::parseText('5.-9.7.', 2023);
        $this->assertSame('2023-07-05', $r['datum_od']);
        $this->assertSame('2023-07-09', $r['datum_do']);
    }

    public function test_rozsah_pres_dva_mesice(): void
    {
        // "26.11.-23.12"
        $r = DateParser::parseText('26.11.-23.12', 2023);
        $this->assertSame('2023-11-26', $r['datum_od']);
        $this->assertSame('2023-12-23', $r['datum_do']);
    }

    public function test_rozsah_s_explicitnim_rokem(): void
    {
        $r = DateParser::parseText('24.-26.3.2024', 2023);
        $this->assertSame('2024-03-24', $r['datum_od']);
        $this->assertSame('2024-03-26', $r['datum_do']);
    }

    public function test_prazdny_vstup(): void
    {
        $this->assertSame(['datum_od' => null, 'datum_do' => null], DateParser::parse(null, 2023));
        $this->assertSame(['datum_od' => null, 'datum_do' => null], DateParser::parse('', 2023));
    }

    public function test_jedno_datum_s_rokem(): void
    {
        $r = DateParser::parseText('17.-19. 5. 2024', 2023);
        $this->assertSame('2024-05-17', $r['datum_od']);
        $this->assertSame('2024-05-19', $r['datum_do']);
    }

    public function test_carbon_instance(): void
    {
        $d = Carbon::parse('2023-06-15');
        $r = DateParser::parse($d, 2023);
        $this->assertSame('2023-06-15', $r['datum_od']);
    }
}
