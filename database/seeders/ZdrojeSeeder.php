<?php

namespace Database\Seeders;

use App\Models\Zdroj;
use App\Services\Scraping\AuthenticatedHttp;
use Illuminate\Database\Seeder;

class ZdrojeSeeder extends Seeder
{
    public function run(): void
    {
        $zdroje = [
            [
                'nazev' => 'Kudy z nudy',
                'url' => 'https://www.kudyznudy.cz',
                'robots_url' => 'https://www.kudyznudy.cz/robots.txt',
                'sitemap_url' => 'https://www.kudyznudy.cz/sitemap.xml',
                'cms_typ' => 'kudyznudy',
                'url_pattern_list' => '/kalendar-akci',
                'url_pattern_detail' => '/akce/{slug}',
                'typ' => 'katalog',
                'stav' => 'aktivni',
                'frekvence_hodin' => 168,
                'vyzaduje_login' => false,
                'poznamka' => 'Největší turistický portál ČR. Má GPS a kontakty. Sitemap se všemi URL akcí.',
            ],
            [
                'nazev' => 'Stánkař',
                'url' => 'https://stankar.cz',
                'robots_url' => 'https://stankar.cz/robots.txt',
                'sitemap_url' => 'https://stankar.cz/wp-sitemap-posts-mec-events-1.xml',
                'cms_typ' => 'wordpress_mec',
                'url_pattern_list' => '/events/',
                'url_pattern_detail' => '/events/{slug}/',
                'typ' => 'katalog',
                'stav' => 'aktivni',
                'frekvence_hodin' => 168,
                'vyzaduje_login' => false,
                'poznamka' => 'Specializovaný portál pro stánkaře. WordPress + Modern Events Calendar plugin.',
            ],
            [
                'nazev' => 'Webtržiště',
                'url' => 'https://www.webtrziste.cz',
                'robots_url' => 'https://www.webtrziste.cz/robots.txt',
                'sitemap_url' => null,
                'cms_typ' => 'webtrziste',
                'url_pattern_list' => '/trhy/akce/',
                'url_pattern_detail' => '/trhy/akce/program.php?id={id}',
                'typ' => 'katalog',
                'stav' => 'aktivni',
                'frekvence_hodin' => 168,
                'vyzaduje_login' => true,
                'login_url' => 'https://www.webtrziste.cz/trhy/prihlaseni/prihlaseni.php',
                'login_credentials' => AuthenticatedHttp::zasifrujCredentials([
                    'login' => 'wormup',
                    'heslo' => '1416',
                ]),
                'poznamka' => 'Custom PHP. ~387 akcí. Filtruje podle kraje. Unikátní: počet registrovaných stánkařů. Login povolen — extrahuje i e-mail a telefon organizátora.',
            ],
        ];

        foreach ($zdroje as $data) {
            Zdroj::updateOrCreate(
                ['url' => $data['url']],
                $data
            );
        }
    }
}
