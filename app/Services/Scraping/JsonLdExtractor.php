<?php

namespace App\Services\Scraping;

/**
 * Generický extrakor schema.org/Event z HTML.
 *
 * Mnoho moderních CMS (WordPress, Joomla, Drupal, custom Vue/React) renderuje
 * pro Google strukturovaná data v <script type="application/ld+json">.
 * Pokud najdeme Event, můžeme vyplnit pole BEZ AI volání → zdarma + spolehlivěji.
 *
 * Schema.org Event: https://schema.org/Event
 */
class JsonLdExtractor
{
    /**
     * Vytáhne první nalezený Event z JSON-LD.
     * Vrací stejné schéma jako AkceExtractor::extrahuj() — null pokud Event nenalezen.
     */
    public function extrahuj(string $html): ?array
    {
        $events = $this->vsechnyEventy($html);
        if (empty($events)) return null;

        // Vezmi první (nebo nejúplnější — preferuj ten s víc poli)
        usort($events, fn ($a, $b) => count($b) <=> count($a));
        return $this->normalizujEvent($events[0]);
    }

    /** Najdi všechny @type=Event v HTML JSON-LD blocích. */
    public function vsechnyEventy(string $html): array
    {
        $events = [];
        if (!preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.+?)<\/script>/is', $html, $m)) {
            return $events;
        }

        foreach ($m[1] as $jsonRaw) {
            $data = json_decode(trim($jsonRaw), true);
            if (!$data) continue;

            // JSON-LD může být objekt, pole, nebo @graph
            $items = $this->rozbalitItems($data);

            foreach ($items as $item) {
                if (!is_array($item)) continue;
                $typ = $item['@type'] ?? null;
                $jeEvent = $typ === 'Event'
                    || (is_array($typ) && in_array('Event', $typ))
                    || in_array($typ, ['Festival', 'BusinessEvent', 'ChildrensEvent',
                        'ComedyEvent', 'CourseInstance', 'DanceEvent', 'DeliveryEvent',
                        'EducationEvent', 'ExhibitionEvent', 'Festival', 'FoodEvent',
                        'LiteraryEvent', 'MusicEvent', 'PublicationEvent', 'SaleEvent',
                        'ScreeningEvent', 'SocialEvent', 'SportsEvent', 'TheaterEvent',
                        'VisualArtsEvent'], true);

                if ($jeEvent) {
                    $events[] = $item;
                }
            }
        }

        return $events;
    }

    /** Rozbal @graph nebo array do plochého seznamu. */
    protected function rozbalitItems(array $data): array
    {
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            return $data['@graph'];
        }
        // Pokud je vstup numerické pole, jde o list
        if (array_is_list($data)) {
            return $data;
        }
        return [$data];
    }

    /**
     * Převeď schema.org Event na náš formát (stejný jako z AI).
     * Vrací null pokud není dostatek dat.
     */
    protected function normalizujEvent(array $e): ?array
    {
        if (empty($e['name'])) return null;

        // Lokace (může být string, Place objekt, nebo seznam)
        $place = $this->prvniPlace($e['location'] ?? null);
        $adresaParts = $this->parseAdresu($place['address'] ?? null);

        // Organizator (Person/Organization)
        $org = $e['organizer'] ?? null;
        if (is_array($org) && !array_is_list($org)) {
            $orgNazev = $org['name'] ?? null;
            $orgEmail = $org['email'] ?? null;
            $orgTel = $org['telephone'] ?? null;
        } else {
            $orgNazev = is_string($org) ? $org : null;
            $orgEmail = null;
            $orgTel = null;
        }

        // GPS z geo
        $geo = $place['geo'] ?? null;
        $gpsLat = is_array($geo) ? ($geo['latitude'] ?? null) : null;
        $gpsLng = is_array($geo) ? ($geo['longitude'] ?? null) : null;

        // Datumy — ISO 8601 → YYYY-MM-DD
        $datumOd = $this->parseDatum($e['startDate'] ?? null);
        $datumDo = $this->parseDatum($e['endDate'] ?? null);

        return [
            'nazev' => $e['name'],
            'typ' => $this->odhadniTyp($e['@type'] ?? null, $e['name'] ?? ''),
            'datum_od' => $datumOd,
            'datum_do' => $datumDo,
            'cas' => $this->parseCas($e['startDate'] ?? null, $e['endDate'] ?? null),
            'misto' => $place['name'] ?? null,
            'adresa' => $adresaParts['ulice'],
            'mesto' => $adresaParts['mesto'],
            'psc' => $adresaParts['psc'],
            'okres' => null,                // schema.org nemá okres → vyplníme přes resolver
            'kraj' => $adresaParts['region'],
            'gps_lat' => $gpsLat ? (float) $gpsLat : null,
            'gps_lng' => $gpsLng ? (float) $gpsLng : null,
            'organizator' => $orgNazev,
            'kontakt_email' => $orgEmail,
            'kontakt_telefon' => $orgTel,
            'web_url' => $e['url'] ?? null,
            'vstupne' => $this->parseVstupne($e['offers'] ?? null),
            'popis' => isset($e['description']) ? mb_substr(strip_tags((string) $e['description']), 0, 300) : null,
            'rocnik' => null,
            'velikost_info' => null,
            'velikost_signaly' => [
                'navstevnost' => null,
                'pocet_stankaru' => null,
                'rocnik' => null,
                'plocha_m2' => null,
                'trvani_dny' => $this->spoctiTrvani($datumOd, $datumDo),
            ],
            '_zdroj_extrakce' => 'json_ld',
        ];
    }

    protected function prvniPlace(mixed $location): array
    {
        if (empty($location)) return [];
        if (is_string($location)) return ['name' => $location];
        if (is_array($location)) {
            // Pokud je list, vezmi první
            if (array_is_list($location)) {
                return is_array($location[0] ?? null) ? $location[0] : [];
            }
            return $location;
        }
        return [];
    }

    protected function parseAdresu(mixed $address): array
    {
        $defaults = ['ulice' => null, 'mesto' => null, 'psc' => null, 'region' => null];

        if (is_string($address)) {
            return ['ulice' => $address] + $defaults;
        }
        if (!is_array($address)) return $defaults;

        return [
            'ulice' => $address['streetAddress'] ?? null,
            'mesto' => $address['addressLocality'] ?? null,
            'psc' => $address['postalCode'] ?? null,
            'region' => $address['addressRegion'] ?? null,
        ];
    }

    protected function parseDatum(?string $iso): ?string
    {
        if (!$iso) return null;
        try {
            return (new \DateTime($iso))->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    protected function parseCas(?string $start, ?string $end): ?string
    {
        try {
            $startCas = $start ? (new \DateTime($start))->format('H:i') : null;
            $endCas = $end ? (new \DateTime($end))->format('H:i') : null;
        } catch (\Exception) {
            return null;
        }

        if ($startCas && $endCas && $startCas !== '00:00') {
            return "{$startCas}–{$endCas}";
        }
        if ($startCas && $startCas !== '00:00') {
            return $startCas;
        }
        return null;
    }

    protected function parseVstupne(mixed $offers): ?string
    {
        if (empty($offers)) return null;

        // offers může být objekt nebo list
        $offer = is_array($offers) && array_is_list($offers) ? ($offers[0] ?? null) : $offers;
        if (!is_array($offer)) return null;

        $price = $offer['price'] ?? null;
        $currency = $offer['priceCurrency'] ?? 'CZK';

        if ($price === null) return null;
        if ((float) $price === 0.0) return 'Zdarma';

        return $price . ' ' . $currency;
    }

    protected function spoctiTrvani(?string $od, ?string $do): int
    {
        if (!$od) return 1;
        try {
            $dOd = new \DateTime($od);
            $dDo = $do ? new \DateTime($do) : $dOd;
            return max(1, $dDo->diff($dOd)->days + 1);
        } catch (\Exception) {
            return 1;
        }
    }

    /** Odhad typu akce z @type a názvu. */
    protected function odhadniTyp(mixed $atType, string $nazev): string
    {
        $nazevLower = mb_strtolower($nazev);

        // Z @type přesné
        if (is_string($atType)) {
            $mapping = [
                'MusicEvent' => 'hudebni_festival',
                'Festival' => 'festival',
                'FoodEvent' => 'food_festival',
                'SocialEvent' => 'slavnosti',
                'TheaterEvent' => 'divadlo',
                'ScreeningEvent' => 'jine',
                'ExhibitionEvent' => 'vystava',
                'EducationEvent' => 'workshop',
                'SportsEvent' => 'sportovni',
                'BusinessEvent' => 'jine',
            ];
            if (isset($mapping[$atType])) return $mapping[$atType];
        }

        // Z názvu (klíčová slova)
        $keywords = [
            'pout' => ['pouť', 'pout', 'svatomar'],
            'hody' => ['hody', 'posvícení', 'posviceni'],
            'food_festival' => ['food', 'gastro', 'pivní', 'pivni festival'],
            'vinobrani' => ['vinobraní', 'vinobrani'],
            'dynobrani' => ['dýňobraní', 'dynobrani'],
            'jarmark' => ['jarmark'],
            'farmarske_trhy' => ['farmářské', 'farmarske trhy'],
            'vanocni_trhy' => ['vánoční trh', 'vanocni trh', 'advent'],
            'velikonocni_trhy' => ['velikonoční', 'velikonocni'],
            'slavnosti' => ['slavnosti', 'slavnost', 'dny mesta', 'dny města'],
            'festival' => ['festival'],
            'historicke_slavnosti' => ['historické', 'historicke', 'rytířské', 'rytirske', 'středověké'],
            'folklor' => ['folklor', 'národopisné', 'narodopisne', 'krojované'],
        ];
        foreach ($keywords as $typ => $klicy) {
            foreach ($klicy as $k) {
                if (str_contains($nazevLower, $k)) return $typ;
            }
        }

        return 'jine';
    }
}
