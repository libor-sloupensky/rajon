<?php

/**
 * Konfigurace scrapingu — trust ranking polí podle zdroje.
 *
 * Hodnota = důvěryhodnost zdroje pro dané pole (0-100).
 * Při merge: pokud nový zdroj má vyšší trust než původní → přepsat.
 * Manuální úprava adminem = trust 100 (nikdy se nepřepíše).
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Trust ranking podle zdroje × pole
    |--------------------------------------------------------------------------
    | Klíč = cms_typ zdroje (viz zdroje.cms_typ)
    | Hodnoty = důvěryhodnost 0-100 pro konkrétní pole
    */
    'trust' => [

        // Kudyznudy.cz — nejkvalitnější data celkově
        'kudyznudy' => [
            'gps_lat' => 90,
            'gps_lng' => 90,
            'adresa' => 85,
            'psc' => 85,
            'okres' => 90,
            'kraj' => 95,
            'kontakt_email' => 85,
            'kontakt_telefon' => 85,
            'web_url' => 80,
            'organizator' => 85,
            'popis' => 75,
            'datum_od' => 90,
            'datum_do' => 90,
            'typ' => 80,
            'vstupne' => 75,
            '*' => 70,  // default pro ostatní pole
        ],

        // Stankar.cz — WordPress + MEC, dobré pro čas/datumy
        'wordpress_mec' => [
            'cas' => 85,
            'datum_od' => 85,
            'datum_do' => 85,
            'organizator' => 60,
            'kontakt_telefon' => 60,
            'kontakt_email' => 50,
            'popis' => 65,
            'typ' => 75,
            '*' => 55,
        ],

        // Webtrziste.cz — unikátní je velikost (počet stánkařů)
        'webtrziste' => [
            'velikost_signaly' => 95,  // počet registrovaných stánkařů
            'vstupne' => 80,
            'typ' => 80,
            'datum_od' => 75,
            'datum_do' => 75,
            'popis' => 50,
            'organizator' => 50,
            'kontakt_email' => 20,  // jen po loginu
            '*' => 50,
        ],

        // Joomla městské weby — lokální zdroj, dobrý pro místa v jednom městě
        'joomla' => [
            'adresa' => 80,
            'organizator' => 75,
            'kontakt_email' => 70,
            'kontakt_telefon' => 70,
            'popis' => 65,
            '*' => 60,
        ],

        // WordPress obecně
        'wordpress' => [
            '*' => 55,
        ],

        // Custom PHP weby
        'custom' => [
            '*' => 50,
        ],

        // Speciální "zdroje"
        'manual' => ['*' => 100],       // admin úprava — nikdy nepřepsat

        // Web pořadatele akce — primární zdroj pravdy (nad všechny katalogy)
        // Aktivuje se buď flagem je_web_poradatele na zdroji, nebo detekcí domény akce.web_url
        'web_poradatele' => [
            'kontakt_email' => 98,
            'kontakt_telefon' => 98,
            'organizator' => 98,
            'web_url' => 100,
            'vstupne' => 95,
            'datum_od' => 95,
            'datum_do' => 95,
            'cas' => 95,
            'popis' => 90,
            'adresa' => 95,
            'velikost_info' => 90,
            '*' => 95,
        ],

        'excel' => [                     // historie franšízantů z Excelu
            'najem' => 100,
            'obrat' => 100,
            'velikost_info' => 90,
            '*' => 70,
        ],
        'email' => [                     // scraping e-mailu od organizátora
            'najem' => 95,
            'kontakt_email' => 95,
            'kontakt_telefon' => 85,
            '*' => 65,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Práh pro auto-propojení ročníků
    |--------------------------------------------------------------------------
    */
    'auto_propojeni_similarity' => 90,   // similarity >= 90% + stejný okres → automaticky propojit

    /*
    |--------------------------------------------------------------------------
    | Prahové hodnoty matchingu
    |--------------------------------------------------------------------------
    */
    'matching' => [
        'similarity_threshold' => 80,    // % podobnosti názvu (similar_text)
        'date_tolerance_days' => 3,      // ± dny tolerance pro datum
        'gps_radius_km' => 1.0,          // GPS proximita v km
    ],

    /*
    |--------------------------------------------------------------------------
    | Merge pravidla pro speciální pole
    |--------------------------------------------------------------------------
    */
    'merge' => [
        // Popis: když nový je výrazně delší (1.2×), přepsat
        'popis_prefer_longer_factor' => 1.2,

        // velikost_info: spojit z více zdrojů do jednoho textu
        'velikost_info_append' => true,

        // velikost_signaly: merge JSON (non-null values preserve, nové doplnit)
        'velikost_signaly_merge' => true,

        // Kolik posledních merge operací si pamatovat v merge_log
        'merge_log_max' => 20,
    ],

];
