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

            // --- Slovenská republika (8 samosprávných krajů) ---
            // Bratislavu a Košice držíme jako jeden okres (stejně jako Prahu),
            // aby se text "Bratislava" / "Košice" z AI navázal na okres.
            'Bratislavský kraj' => [
                'Bratislava', 'Malacky', 'Pezinok', 'Senec',
            ],
            'Trnavský kraj' => [
                'Dunajská Streda', 'Galanta', 'Hlohovec', 'Piešťany',
                'Senica', 'Skalica', 'Trnava',
            ],
            'Trenčiansky kraj' => [
                'Bánovce nad Bebravou', 'Ilava', 'Myjava', 'Nové Mesto nad Váhom',
                'Partizánske', 'Považská Bystrica', 'Prievidza', 'Púchov', 'Trenčín',
            ],
            'Nitriansky kraj' => [
                'Komárno', 'Levice', 'Nitra', 'Nové Zámky',
                'Šaľa', 'Topoľčany', 'Zlaté Moravce',
            ],
            'Žilinský kraj' => [
                'Bytča', 'Čadca', 'Dolný Kubín', 'Kysucké Nové Mesto',
                'Liptovský Mikuláš', 'Martin', 'Námestovo', 'Ružomberok',
                'Turčianske Teplice', 'Tvrdošín', 'Žilina',
            ],
            'Banskobystrický kraj' => [
                'Banská Bystrica', 'Banská Štiavnica', 'Brezno', 'Detva',
                'Krupina', 'Lučenec', 'Poltár', 'Revúca', 'Rimavská Sobota',
                'Veľký Krtíš', 'Zvolen', 'Žarnovica', 'Žiar nad Hronom',
            ],
            'Prešovský kraj' => [
                'Bardejov', 'Humenné', 'Kežmarok', 'Levoča', 'Medzilaborce',
                'Poprad', 'Prešov', 'Sabinov', 'Snina', 'Stará Ľubovňa',
                'Stropkov', 'Svidník', 'Vranov nad Topľou',
            ],
            'Košický kraj' => [
                'Košice', 'Košice-okolie', 'Gelnica', 'Michalovce',
                'Rožňava', 'Sobrance', 'Spišská Nová Ves', 'Trebišov',
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
