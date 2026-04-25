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
            'datum_od' => 70,
            'datum_do' => 70,
            'web_url' => 70,
            'popis' => 60,
            'organizator' => 60,
            '*' => 55,
        ],

        // Drupal obecně
        'drupal' => [
            'datum_od' => 70,
            'datum_do' => 70,
            'popis' => 60,
            '*' => 55,
        ],

        // JSON-LD schema.org/Event extrakce — strukturovaná data, vyšší trust
        // (CMS to označí, ale data jsou předdefinovaná Googlem schema.org)
        'json_ld' => [
            'datum_od' => 90,
            'datum_do' => 90,
            'gps_lat' => 95,
            'gps_lng' => 95,
            'adresa' => 90,
            'organizator' => 80,
            'kontakt_email' => 80,
            'web_url' => 90,
            '*' => 75,
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
    | Ceník Anthropic API (per 1M tokenů, USD)
    |--------------------------------------------------------------------------
    | Aktualizovat při změně ceníku. Hodnoty: input, output, cache_write, cache_read.
    | Pro výpočet ceny per AI call.
    */
    'cenik' => [
        'claude-haiku-4-5-20251001' => [
            'input' => 1.0,
            'output' => 5.0,
            'cache_write' => 1.25,
            'cache_read' => 0.10,
        ],
        'claude-sonnet-4-6' => [
            'input' => 3.0,
            'output' => 15.0,
            'cache_write' => 3.75,
            'cache_read' => 0.30,
        ],
        // Default pro neznámé modely (Haiku-level)
        'default' => [
            'input' => 1.0,
            'output' => 5.0,
            'cache_write' => 1.25,
            'cache_read' => 0.10,
        ],
    ],

    /** Kurz USD/CZK pro zobrazení v UI. */
    'kurz_usd_czk' => 24.0,

    /*
    |--------------------------------------------------------------------------
    | Ignorované typy akcí
    |--------------------------------------------------------------------------
    | Akce s těmito typy se při scrapingu vůbec neukládají (skip after AI extrakce).
    | Existující akce s těmito typy mají stav='zrusena' (skryté z katalogu).
    | Důvod: nejsou cílem pro WormUP stánkaře (indoor, malé, neveřejné).
    */
    'ignorovane_typy' => [
        'divadlo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Adaptivní refresh interval podle blízkosti akce
    |--------------------------------------------------------------------------
    | Klíč = max počet dní od dnes do datum_od. Hodnota = jak často kontrolovat (dny).
    */
    'refresh_interval' => [
        3 => 1,      // do 3 dnů → každý den
        14 => 3,     // do 14 dnů → každé 3 dny
        60 => 7,     // do 60 dnů → týdně
        365 => 30,   // do 365 dnů → měsíčně
        // nad 365 dnů → vůbec ne (vrací 0 = vždy)
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
