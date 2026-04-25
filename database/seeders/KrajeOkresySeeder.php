<?php

namespace Database\Seeders;

use App\Models\Kraj;
use App\Models\Okres;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KrajeOkresySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Hlavní město Praha' => ['Praha'],
            'Středočeský kraj' => [
                'Benešov', 'Beroun', 'Kladno', 'Kolín', 'Kutná Hora',
                'Mělník', 'Mladá Boleslav', 'Nymburk', 'Praha-východ',
                'Praha-západ', 'Příbram', 'Rakovník',
            ],
            'Jihočeský kraj' => [
                'České Budějovice', 'Český Krumlov', 'Jindřichův Hradec',
                'Písek', 'Prachatice', 'Strakonice', 'Tábor',
            ],
            'Plzeňský kraj' => [
                'Domažlice', 'Klatovy', 'Plzeň-město', 'Plzeň-jih',
                'Plzeň-sever', 'Rokycany', 'Tachov',
            ],
            'Karlovarský kraj' => [
                'Cheb', 'Karlovy Vary', 'Sokolov',
            ],
            'Ústecký kraj' => [
                'Děčín', 'Chomutov', 'Litoměřice', 'Louny',
                'Most', 'Teplice', 'Ústí nad Labem',
            ],
            'Liberecký kraj' => [
                'Česká Lípa', 'Jablonec nad Nisou', 'Liberec', 'Semily',
            ],
            'Královéhradecký kraj' => [
                'Hradec Králové', 'Jičín', 'Náchod', 'Rychnov nad Kněžnou', 'Trutnov',
            ],
            'Pardubický kraj' => [
                'Chrudim', 'Pardubice', 'Svitavy', 'Ústí nad Orlicí',
            ],
            'Kraj Vysočina' => [
                'Havlíčkův Brod', 'Jihlava', 'Pelhřimov', 'Třebíč', 'Žďár nad Sázavou',
            ],
            'Jihomoravský kraj' => [
                'Blansko', 'Břeclav', 'Brno-město', 'Brno-venkov',
                'Hodonín', 'Vyškov', 'Znojmo',
            ],
            'Olomoucký kraj' => [
                'Jeseník', 'Olomouc', 'Prostějov', 'Přerov', 'Šumperk',
            ],
            'Zlínský kraj' => [
                'Kroměříž', 'Uherské Hradiště', 'Vsetín', 'Zlín',
            ],
            'Moravskoslezský kraj' => [
                'Bruntál', 'Frýdek-Místek', 'Karviná', 'Nový Jičín',
                'Opava', 'Ostrava-město',
            ],
        ];

        foreach ($data as $krajNazev => $okresy) {
            $kraj = Kraj::updateOrCreate(
                ['nazev' => $krajNazev],
                ['slug' => Str::slug($krajNazev)]
            );

            foreach ($okresy as $okresNazev) {
                Okres::updateOrCreate(
                    ['slug' => Str::slug($okresNazev)],
                    [
                        'kraj_id' => $kraj->id,
                        'nazev' => $okresNazev,
                    ]
                );
            }
        }
    }
}
