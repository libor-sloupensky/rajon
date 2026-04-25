# Scraping modul

## Stav: Generická pipeline (verze 2)

Pipeline je navržená tak, aby **přidání nového katalogu / kalendáře akcí
NEVYŽADOVALO úpravu kódu** — stačí vyplnit záznam v tabulce `zdroje`.

## Architektura

```
┌─────────────────────────────────────────────────────────────────┐
│ ZdrojAnalyzer — analýza nového zdroje (jednorázově při přidání) │
│   - GET robots.txt → sitemap odkazy                             │
│   - Auto-detekce sitemap (/sitemap.xml, /wp-sitemap.xml)        │
│   - Detekce CMS (wordpress_mec, joomla, drupal, custom)         │
│   - Auto-detekce url_pattern_detail z URLs ve sitemapu          │
│   - Detekce JSON-LD Event v HTML                                │
└────────────────────────────┬────────────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│ ScrapingPipeline::scrapujZdroj() — opakovaný běh                │
│                                                                 │
│ 1. ziskejUrls()                                                 │
│    a. Pokud zdroj má sitemap_url → seznam URL ze sitemap        │
│    b. Jinak → ListingPaginator (auto detekce paginace)          │
│    c. Poslední fallback → odkazy z hlavní stránky               │
│                                                                 │
│ 2. predFiltrujUrls()  (šetří AI tokeny — bez fetch)             │
│    - URL s rokem < aktuální v slugu (např. "vinobrani-2018")    │
│    - URL co už máme v DB s datum_do < dnes                      │
│                                                                 │
│ 3. Pro každou URL:                                              │
│    a. fetchHtml()                                               │
│    b. AkceExtractor::extrahuj():                                │
│       - JsonLdExtractor — pokud má schema.org/Event → použij    │
│       - Fallback AI (Anthropic Claude Haiku) s seznam okresů    │
│    c. Filtr datum_do < dnes → preskoceny                        │
│    d. LokalizaceResolver — text kraj/okres → DB FK              │
│    e. Region filter (7 krajů východní ČR)                       │
│    f. Velikostní scoring (>= 50 = velká, < 40 = malá)           │
│    g. AkceMatcher — fuzzy match na existující akci              │
│    h. AkceMerger — field-level merge s trust ranking            │
│                                                                 │
│ 4. Konflikty mezi zdroji + web pořadatele jako tiebreaker       │
│ 5. Auto-propojení ročníků při similarity ≥ 90 %                 │
│ 6. Log do scraping_log se statistikami                          │
└─────────────────────────────────────────────────────────────────┘
```

## Jak přidat nový katalog akcí

### 1) Otevřít admin UI a vytvořit zdroj

Cesta: `/admin/scraping/new` → zadat URL → klik na **Analyzovat**.

Systém automaticky vyplní:
- `cms_typ` (wordpress_mec / joomla / drupal / custom / kudyznudy / webtrziste)
- `sitemap_url` (z robots.txt nebo standardních cest)
- `url_pattern_detail` (auto-detekce z URL ve sitemapu)
- `pocet_url_v_sitemap`
- `ma_jsonld_event` (true → AI calls budou minimální)

### 2) Uživatel vyplní jen:
- `nazev` (zobrazení)
- `frekvence_hodin` (default 168 = týdně)
- `vyzaduje_login` (pokud detail vyžaduje přihlášení — pak skipnout)
- `je_web_poradatele` (pokud zdroj je oficiální web jednoho organizátora — vyšší trust)
- `poznamka` (volitelné)

### 3) Spustit Test (10) → ověřit že se akce stahují
Po úspěšném testu spustit **Plný scraping**.

## Co když zdroj nemá sitemap?

`ListingPaginator` automaticky:
1. Začne na `url_pattern_list` (nebo hlavní URL zdroje)
2. Hledá v HTML odkazy odpovídající `url_pattern_detail`
3. Detekuje paginaci (`?page=N`, `?strana=N`, `/page/N/`, `rel="next"`)
4. Iteruje stránky až do limitu (max 100 stránek, max 5000 URL)

## Co když má detail JSON-LD schema.org/Event?

`JsonLdExtractor` tato strukturovaná data parsuje **bez AI volání** — zdarma a přesněji. Trust ranking pro `json_ld` je vyšší než pro AI extrakci.

Je-li JSON-LD kompletní (název + datum + místo) → AI se nevolá. Jinak fallback na AI.

## Trust ranking (per CMS)

Definováno v `config/scraping.php`. Defaulty:
- `kudyznudy` — vysoký trust (90 GPS, 85 kontakt)
- `wordpress_mec` (Stánkař) — střední (čas+datum 85, popis 65)
- `webtrziste` — nízký (kontakt 20 — jen po loginu), velikost_signaly 95
- `joomla` — střední (kontakt 70, popis 65)
- `wordpress` — generic 55
- `drupal` — generic 55
- `json_ld` — vyšší (90 datum, 95 GPS)
- `custom` — generic 50
- `web_poradatele` — nejvyšší trust scrapping (95+, jen `manual` 100)
- `excel` — historie franšízantů, najem/obrat 100
- `manual` — admin úprava (zámek pole)

Při neznámém CMS se použije `custom` (50). Pro nový katalog stačí přidat
záznam — žádný kód.

## Soubory

```
app/Services/Scraping/
  ZdrojAnalyzer.php         — analýza zdroje (robots/sitemap/CMS/pattern)
  JsonLdExtractor.php       — schema.org/Event z HTML (bez AI)
  ListingPaginator.php      — generický crawler s paginací
  AkceExtractor.php         — JSON-LD priorita + AI fallback
  AkceMatcher.php           — fuzzy matching na existující akci
  AkceMerger.php            — field-level merge s trust ranking
  LokalizaceResolver.php    — text kraj/okres → DB FK
  ScrapingPipeline.php      — orchestrace
config/scraping.php          — trust ranking + thresholds
```

## Příklad: nový zdroj "Akce v ČR"

1. Admin: `/admin/scraping/new` → URL `https://www.akcevcr.cz`
2. Klik **Analyzovat**:
   - sitemap nalezen: `https://www.akcevcr.cz/sitemap.xml`
   - CMS: `wordpress`
   - URL pattern: `/akce/`
   - JSON-LD Event: ano (bonus!)
3. Klik **Uložit zdroj**
4. Klik **Test (10)** — uvidíš statistiky kolik akcí bylo extrahováno přes JSON-LD vs AI
5. Klik **Plný scraping** — spustí pro všechny URL ze sitemap

**Žádný kód nebyl napsán.**

## Aktuální zdroje (stav 2026-04-25)

| Zdroj | URL | CMS | Sitemap | JSON-LD | Pozn. |
|-------|-----|-----|---------|---------|-------|
| Kudy z nudy | kudyznudy.cz | kudyznudy | ✓ 13 015 | ne | nejkvalitnější data |
| Stánkař | stankar.cz | wordpress_mec | ✓ 1 674 | nutné ověřit | WordPress + MEC plugin |
| Webtržiště | webtrziste.cz | webtrziste | ne | ne | Custom PHP, paginator |

## TODO

- [ ] IMAP klient pro festivals@wormup.com (nový generický modul EmailExtractor)
- [ ] Excel import (historická data) — řeší modul `historicka_data`
- [ ] Cron job pro pravidelný scraping (zatím manuální spuštění)
- [ ] Batch zpracování (Anthropic Batches API — 50% sleva)
- [ ] Custom listing crawler pro Webtržiště (paginator už je generický, jen otestovat)
