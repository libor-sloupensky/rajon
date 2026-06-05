<?php

namespace Database\Seeders;

use App\Models\Zdroj;
use Illuminate\Database\Seeder;

/**
 * Slovenský zdroj Folklorfest.sk — jarmoky, hody, slávnosti, festivaly.
 *
 * Specifika (zjištěno analýzou):
 *  - sitemap.xml je ARCHIV 2013–2016 → NEpoužívat (sitemap_url = null)
 *  - aktuální akce jsou na 7 kategoriích (listing-crawl přes url_pattern_list)
 *  - detaily mají custom schéma /{id}-slug/ → regex url_pattern_detail
 *
 * Lokálně ověřeno: paginator posbírá 440 aktuálních akcí (2026), 0 starých.
 */
class FolklorfestZdrojSeeder extends Seeder
{
    public function run(): void
    {
        Zdroj::updateOrCreate(
            ['url' => 'https://www.folklorfest.sk'],
            [
                'nazev' => 'Folklorfest.sk',
                'sitemap_url' => null,
                'cms_typ' => 'custom',
                'url_pattern_list' => '/jarmoky-trhy-a-hody/,/slavnosti-a-festivaly/,/dni-obce-a-mesta/,'
                    . '/ludove-zvyky-a-tradicie/,/vinobranie-a-vino/,/pivne-slavnosti-a-pivo/,/varenie-a-pecenie-dobr-t/',
                'url_pattern_detail' => '#/\d{2,}-#',
                'frekvence_hodin' => 24,
                'typ' => 'katalog',
                'stav' => 'aktivni',
                'poznamka' => 'SK: jarmoky/hody/slávnosti. Listing-crawl 7 kategorií (sitemap je archiv 2013–2016). '
                    . 'Detaily /{id}-slug/ přes regex pattern. Custom CMS, bez JSON-LD → AI extrakce.',
            ]
        );
    }
}
