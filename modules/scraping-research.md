# Scraping research — analýza zdrojů akcí

**Datum:** 2026-04-16
**Cíl:** Navrhnout pipeline pro automatickou extrakci akcí (pouti, festivaly, slavnosti, vinobraní, jarmarky, food festivaly) z českých webů.

## Analyzované zdroje

### 1. kudyznudy.cz (CzechTourism)
**Typ:** Velký státní turistický portál
**Kvalita dat:** VELMI VYSOKÁ

| Aspekt | Hodnota |
|--------|---------|
| URL vzor (detail) | `/akce/{slug}` |
| Sitemap | `/sitemap.xml` → 3 sub-sitemapy |
| Sitemap typ akcí | Rozlišeny `/akce/` vs `/aktivity/` |
| Kategorie | 14 typů (festivaly, koncerty, lidová řemesla, gastro, historické...) |
| Počet akcí | ~9000 v sitemap (odhad) |
| Filtry | Datum, lokalita (14 krajů), 7 speciálních filtrů |
| JSON-LD | ❌ Nemá |
| API | ❌ Veřejné není |

**Pole v detailu akce:** název, typ, datum od/do, čas, místo, adresa, obec, kraj, **GPS**, organizátor, **e-mail**, **telefon**, **web**, popis, **cena**, časová náročnost, cílová skupina, počasí (indoor/outdoor), tagy, obrázky.

**Příklad dat (Řípská pouť 2026):**
- GPS: `50.4003481, 14.2978074`
- Organizátor: Obecní úřad Krabčice
- E-mail: info@krabcice.cz, Tel: +420 416 845 093

### 2. stankar.cz
**Typ:** Specializovaný portál pro stánkaře (perfektní match pro WormUP)
**Kvalita dat:** DOBRÁ

| Aspekt | Hodnota |
|--------|---------|
| CMS | WordPress + Modern Events Calendar (MEC) plugin |
| URL vzor | `/events/{slug-year}/` + `?occurrence=YYYY-MM-DD` |
| Sitemap | `/wp-sitemap.xml` → `wp-sitemap-posts-mec-events-1.xml` |
| robots.txt | ✅ existuje, blokuje jen wp-admin/woocommerce |
| JSON-LD | ❌ Nemá (MEC obvykle má — stojí za verifikaci v HTML) |

**Pole:** název, datum od/do, čas (často rozepsaný po dnech), adresa, telefon, e-mail, popis, kategorie.
**Chybí:** GPS, cena stánku (klíčové pro WormUP!), dlouhodobá historie.

### 3. ceskevylety.cz
**Typ:** Starší PHP katalog slavností/festivalů
**Kvalita dat:** STŘEDNÍ

| Aspekt | Hodnota |
|--------|---------|
| URL vzor | `/slavnosti.php?kod=XXXX_nazev` |
| Počet akcí | ~400-500 |
| Systém | Custom PHP |
| JSON-LD | ❌ |

**Pole:** název, měsíc konání, místo, odkaz. **Omezené.**

### 4. webtrziste.cz (PŘIDÁNO)
**Typ:** Kombinovaný portál pro stánkaře (katalog + registrace stánkařů na akce)
**Kvalita dat:** DOBRÁ pro WormUP use-case

| Aspekt | Hodnota |
|--------|---------|
| URL vzor (detail) | `/trhy/akce/program.php?id=NNNN` |
| Systém | Custom PHP |
| Počet akcí | 387 total |
| Kategorie | Jarmark, Slavnost, Bitva, Divadlo, Advent, Folklor, Koncert, Ostatní |
| Filtry | Kraj (mapa ČR), měsíc, typ |
| Sitemap | ❌ Nemá |
| Robots.txt | ✅ Blokuje /registrace, /prihlaseni, /shop |
| Login pro detail | Email/telefon organizátora vyžaduje přihlášení |

**Unikátní pole (oproti konkurenci):**
- **Počet registrovaných stánkařů** na akci — signál, že jde o stánkařskou akci
- **Vstupné pro návštěvníky** (zdarma / částka)
- **Fotky z minulých ročníků** (pro AI klasifikaci velikosti akce)
- **Historie akce** (kolikrát se konala)

**Pole:** název, datum od/do, místo (kolonáda/náměstí/...), město + PSČ, kraj, kategorie, popis, vstupné, kontaktní osoba (jméno), počet registrovaných stánkařů, galerie.
**Chybí (bez přihlášení):** e-mail, telefon, cena stánku.

### Další:
- **kdykde.cz** — vrací 403, asi vyžaduje cookie/JS (přeskakujeme)
- **goout.net** — komerční, URL `/cs/{nazev}/{kod}/`, primárně vstupenky
- **farmarsketrziste.cz** — specializace na pražské farmářské trhy

## Městské kalendáře (druhá úroveň)

### akce.nmnm.cz (Nové Město na Moravě)
- WordPress, kategoriální URL `/kategorie-akci/{typ}/`
- RSS feed dostupný (`/feed/`)
- Data načítána přes JS (WebFetch nedostal obsah)

### litomerice.cz/kalendar-akci
- **Joomla** CMS
- URL: `/vypis-akci/details/{YYYY-MM-DD}/{id}-{slug}`
- Základní pole: název, datum, místo, obrázek

### Shrnutí
Každé město má vlastní systém (WordPress, Joomla, custom). **Nelze univerzalizovat selektory** — potřebujeme AI na mapování struktury.

## Klíčová pozorování

1. **Sitemap.xml je zásadní** — dává kompletní seznam URL akcí bez potřeby crawlování
2. **JSON-LD chybí** na všech českých webech — musíme parsovat HTML přes AI
3. **Duplicita napříč katalogy** — stejná Řípská pouť je na kudyznudy.cz i stankar.cz → potřebujeme deduplikaci
4. **Nejpodrobnější data:** kudyznudy.cz (má GPS, kontakty)
5. **Nejrelevantnější pro WormUP:** stankar.cz (stánkařské akce, ale chybí cena stánku)
6. **Kvalita kontaktů:** variabilní — kudyznudy má e-mail/tel, stankar má telefon přes Visit Plzeň (ne přímo organizátora)

## Navrhovaná scraping pipeline

```
[1. URL vstup od uživatele]
       ↓
[2. Zjištění typu zdroje]
    • GET robots.txt → najdi Sitemap:
    • GET sitemap.xml → získej URL seznam
    • GET hlavní stránka → AI určí typ (katalog / detail / městský)
       ↓
[3. Určení CMS]
    • Detekce WordPress/MEC, Joomla, custom
    • Uložení do zdroje: cms_typ, sitemap_url, url_pattern
       ↓
[4. Listování akcí]
    • Pokud sitemap → použít všechny /events/ nebo /akce/ URL
    • Jinak → AI analyzuje HTML listu, vytáhne odkazy na detaily
       ↓
[5. Extrakce detailu]
    • AI z HTML detailu extrahuje strukturovaná data
    • Uloží i surový HTML pro diff (detekce změn)
       ↓
[6. Deduplikace]
    • Hash (nazev + datum_od + obec)
    • Pokud existuje → UPDATE
    • Jinak → INSERT
    • Vždy vytvořit záznam v akce_zdroje (many-to-many)
       ↓
[7. Druhá úroveň]
    • Pokud detail obsahuje 'web akce' (vlastní stránka festivalu nebo město)
    • Nabídnout uživateli přidat jako nový zdroj
    • Automaticky scraping pokud je městský kalendář
```

## Navrhované rozšíření DB

### Tabulka `zdroje` (rozšířit)

```sql
ALTER TABLE zdroje ADD COLUMN robots_url VARCHAR(500);
ALTER TABLE zdroje ADD COLUMN sitemap_url VARCHAR(500);
ALTER TABLE zdroje ADD COLUMN cms_typ VARCHAR(50); -- wordpress_mec, joomla, custom, kudyznudy
ALTER TABLE zdroje ADD COLUMN url_pattern_akce VARCHAR(200); -- např. /akce/{slug}
ALTER TABLE zdroje ADD COLUMN url_pattern_list VARCHAR(200);
ALTER TABLE zdroje ADD COLUMN struktura JSON; -- CSS selectors/xpath/JSON-LD paths
ALTER TABLE zdroje ADD COLUMN posledni_chyby TEXT;
ALTER TABLE zdroje ADD COLUMN frekvence_hodin INT DEFAULT 168; -- týdenně
```

### Tabulka `akce` (rozšířit)

```sql
ALTER TABLE akce ADD COLUMN externi_hash VARCHAR(64); -- hash obsahu pro detekci změn
ALTER TABLE akce ADD COLUMN duplicitni_s_id BIGINT NULL;
ALTER TABLE akce ADD INDEX idx_dedup (nazev(50), datum_od, misto(50));
```

### Nová tabulka `akce_zdroje` (M2M)

```sql
CREATE TABLE akce_zdroje (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    akce_id BIGINT NOT NULL,
    zdroj_id BIGINT NOT NULL,
    url VARCHAR(500) NOT NULL,
    externi_id VARCHAR(200),
    surova_data JSON, -- raw extracted data
    posledni_ziskani TIMESTAMP,
    vytvoreno TIMESTAMP,
    upraveno TIMESTAMP,
    FOREIGN KEY (akce_id) REFERENCES akce(id) ON DELETE CASCADE,
    FOREIGN KEY (zdroj_id) REFERENCES zdroje(id) ON DELETE CASCADE,
    UNIQUE KEY (zdroj_id, url)
);
```

### Nová tabulka `scraping_log`

```sql
CREATE TABLE scraping_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    zdroj_id BIGINT NOT NULL,
    zacatek TIMESTAMP,
    konec TIMESTAMP,
    stav ENUM('probiha', 'uspech', 'chyba', 'castecne'),
    pocet_nalezenych INT DEFAULT 0,
    pocet_novych INT DEFAULT 0,
    pocet_aktualizovanych INT DEFAULT 0,
    pocet_chyb INT DEFAULT 0,
    chyby_detail TEXT,
    vytvoreno TIMESTAMP,
    FOREIGN KEY (zdroj_id) REFERENCES zdroje(id) ON DELETE CASCADE
);
```

## UI návrh — stránka "Přidat zdroj"

```
┌─────────────────────────────────────────┐
│  Přidat zdroj akcí                      │
├─────────────────────────────────────────┤
│  URL zdroje:                            │
│  [ https://www.example.cz/akce        ] │
│                                         │
│  [ Analyzovat ]                         │
├─────────────────────────────────────────┤
│  🤖 AI analýza:                         │
│  ✓ Robots.txt nalezen                   │
│  ✓ Sitemap: /sitemap.xml (342 URL)      │
│  ✓ CMS: WordPress + MEC                 │
│  ✓ Typ: Katalog akcí                    │
│  ✓ URL vzor: /events/{slug}             │
│                                         │
│  Nalezeno 342 akcí, 87 v budoucnosti.  │
│                                         │
│  [ Uložit zdroj a spustit scraping ]    │
└─────────────────────────────────────────┘
```

## Výběr zdrojů pro první implementaci

**Fáze 1 (nejdřív):** kudyznudy.cz
- Největší, nejkvalitnější data
- Má sitemap → jednoduchý crawl
- GPS + kontakty

**Fáze 2:** stankar.cz
- Specializace na stánky (WormUP use-case)
- WordPress + MEC → standardizovaný plugin

**Fáze 3:** městské kalendáře
- Jeden za druhým, podle ROI
- Každý vyžaduje individuální AI mapping

## Rozhodnutí (dle uživatele 2026-04-16)

1. **Historie:** od roku 2019 (stankar má, webtrziste má fotky)
2. **Obrázky:** jen URL, nestahovat lokálně
3. **Ročníky:** jednoduše jedna entita = jeden ročník. Možnost propojit dvě akce do jedné (tabulka `akce_propojeni` nebo `duplicitni_s_id`)
4. **Automatizace:** zatím na vyžádání (later cron podle ceny AI)
5. **Regionální filtr:** **VÝCHODNÍ ČR** — jen Vysočina + Královéhradecký + Pardubický + Olomoucký + Moravskoslezský + Zlínský + Jihomoravský kraj (7 krajů)
6. **Velikost akce:** jen akce s >1000 návštěvníků (outdoor, veřejné), NE klubové/halové

## Regionální filtr

Scrapovat jen akce ve 7 krajích východní ČR:

| Kraj | Kód |
|------|-----|
| Kraj Vysočina | VY |
| Královéhradecký kraj | KH |
| Pardubický kraj | PA |
| Olomoucký kraj | OL |
| Moravskoslezský kraj | MS |
| Zlínský kraj | ZL |
| Jihomoravský kraj | JM |

**Implementace filtru:**
- Před zápisem do DB kontrolovat `kraj` pole
- Akce mimo tyto kraje → ignorovat (do logu jen počet)
- Akce bez kraje → nechat AI doplnit z GPS nebo adresy přes Mapy.cz reverse geocoding

## Typologie akcí — ANO / NE

### Skupina A — NAŠE (velké, outdoor, veřejné, >1000 návštěvníků)

- **Pouť / svatá pouť** (patron svatý, tradiční)
- **Hody / posvícení**
- **Dny města / městské slavnosti**
- **Food festival** (pivní, vinný, gastronomický)
- **Vinobraní** (svatováclavské, znojemské, pálavské...)
- **Dýňobraní**
- **Jarmark** (historický, zemědělský, řemeslný — velký)
- **Farmářské trhy** (jen velké veřejné, NE malé pravidelné)
- **Historické slavnosti** (bitvy, středověké, rytířské)
- **Hudební festival** (outdoor, vícedenní)
- **Folklorní slavnosti** (krojované)
- **Vánoční trhy** (na náměstí, ne v obchoďáku)
- **Velikonoční trhy** (venkovní)
- **Tradiční slavnosti obce** (X. ročník obecních slavností)

### Skupina B — NE (malé, komerční, indoor)

- Koncert v sále / klubu
- Divadlo
- Výstava v galerii
- Workshop / kurz
- Sportovní akce v hale
- Kino
- Taneční večery / plesy
- Svatby, firemní akce
- Přednášky, komentované prohlídky
- Expozice (dlouhodobé)

### Šedá zóna (AI rozhodne podle kontextu)

- Rodinné dny (v lunaparku = ANO, v klubu = NE)
- Zahradnické trhy (velké venkovní = ANO)
- Advent (velký = ANO, v kostele = NE)

### Detekce velikosti akce (bez explicitního údaje)

**Explicitní signály (pokud v HTML):**
- Počet návštěvníků / návštěvnost (výjimečně uvedeno)
- Počet registrovaných stánkařů (webtrziste!)
- Počet ročníků (X. ročník = tradice)
- Plocha v m²

**Heuristiky (AI klasifikace):**
- Lokace: náměstí, park, kolonáda, pole, letiště, areál = outdoor velká
- Lokace: sál, kino, klub, hala, galerie, kostel = indoor malá
- Trvání: 2+ dny → střední, 3+ dny → velká
- Kategorie portálu: "pouť", "hody", "slavnosti", "vinobraní" → velká
- Anonym "X. ročník" → etablovaná tradice
- Velikost města + akce celoměstská → větší

**AI scoring (0-100):**
```
velikost_skore = 0
if has_keyword(["pouť", "hody", "slavnosti", "vinobraní", "festival", "jarmark"]): +30
if location_outdoor(): +20
if duration >= 2 days: +10
if duration >= 3 days: +15 (total 25 za 3+ dny)
if has_registered_stankari >= 15: +25
if x_rocnik >= 5: +10
```

Akce s `velikost_skore >= 50` → ANO.
Akce se score 40–49 → **šedá zóna** (admin potvrdí manuálně).
Akce < 40 → NE.

### Textové info o velikosti (AI free-form)

AI prompt navíc vyzývá:
> "Pokud najdeš jakoukoli informaci o velikosti akce (počet návštěvníků v předchozích ročnících, počet stánkařů, plocha, rozsah), shrň to do 1-2 vět do pole `velikost_info`. Pokud najdeš konkrétní čísla, naplň i `velikost_signaly` (JSON)."

**Pole v DB:**
- `velikost_skore` INT (0-100) — pro filtrování
- `velikost_info` TEXT — volný text AI ("~5000 návštěvníků v roce 2024, 40+ stánků, 25. ročník")
- `velikost_signaly` JSON — strukturovaná data {navstevnost: 5000, pocet_stankaru: 40, rocnik: 25, plocha_m2: null}
- `velikost_stav` ENUM('ano', 'ne', 'nejasna') — výsledek filtrování

## Co dokážeme zjistit (pole a spolehlivost)

| Pole | Kde získat | Spolehlivost | Poznámka |
|------|------------|--------------|----------|
| Datum od/do | Všude | ⭐⭐⭐⭐⭐ | |
| Čas (otevírací hodiny) | Někde | ⭐⭐⭐ | Stankar má po dnech |
| Adresa (ulice + město) | Všude | ⭐⭐⭐⭐⭐ | |
| Kraj/okres | Všude (někdy doplnit) | ⭐⭐⭐⭐ | Reverse geocoding přes Mapy.cz |
| GPS souřadnice | Kudyznudy ano, jinde odvodit | ⭐⭐⭐ | Fallback: geocode adresy |
| Název akce | Všude | ⭐⭐⭐⭐⭐ | |
| Typ akce (kategorie) | Všude | ⭐⭐⭐⭐ | Normalizace napříč zdroji |
| Organizátor (název) | Kudyznudy, webtrziste | ⭐⭐⭐ | |
| E-mail organizátora | Kudyznudy | ⭐⭐ | Webtrziste až po loginu |
| Telefon | Kudyznudy, stankar | ⭐⭐⭐ | |
| Web organizátora/akce | Většinou | ⭐⭐⭐⭐ | |
| Popis akce | Všude | ⭐⭐⭐⭐ | |
| Vstupné (pro návštěvníka) | Někdy | ⭐⭐ | "zdarma" / částka |
| **Cena stánku (nájem)** | **Téměř nikde veřejně** | ⭐ | Webtrziste má po loginu; jinak přes e-mail organizátorovi |
| **Počet návštěvníků** | **Téměř nikde** | ⭐ | Odhad AI z kontextu + velikost obce |
| **Počet registrovaných stánkařů** | **Webtrziste** | ⭐⭐⭐⭐ | Signál skutečné velikosti |
| Plocha k dispozici | Skoro nikde | ⭐ | |
| Minulé ročníky | Webtrziste (foto), stankar (sitemap 2018+) | ⭐⭐⭐ | |
| Obrázky (URL) | Všude | ⭐⭐⭐⭐ | |

### Kritický insight

**Cena stánku a počet návštěvníků jsou klíčové pro WormUP rozhodování, ale NEJSOU v veřejných katalozích.** Pro získání:

1. **Krátkodobě:** Využít historické **Excel soubory** franšízantů (mají obrat + nájem z minulých let)
2. **Střednědobě:** **Login na webtrziste** — má skutečné údaje, ale vyžaduje manuální přihlášení
3. **Dlouhodobě:** **E-mail bot** — automaticky píše organizátorům přes festivals@wormup.com a parsuje odpovědi (AI extrakce)
4. **Alternativa:** **Odhad AI** na základě: velikost města × typ akce × počet ročníků × počet registrovaných stánkařů

## Návrh pipeline (po specifikaci)

```
[1. URL vstup]
    ↓
[2. Region filter předem] — pokud zdroj umí filtrovat podle kraje, použít
    ↓
[3. Sitemap / listing → seznam URL akcí]
    ↓
[4. Pro každou URL:]
    a) Fetch HTML
    b) AI extrakce strukturovaných dat
    c) Region check (7 krajů) → pokud ne, SKIP
    d) Velikost check (AI scoring >= 50) → pokud ne, SKIP (nebo označit flag=male)
    e) Deduplikace (nazev + datum_od + obec)
    f) INSERT / UPDATE
    g) Uložit raw data do akce_zdroje
    ↓
[5. Post-processing]
    - Geocoding adresy → GPS (pokud chybí)
    - Normalizace typu akce
    - Odhad počtu návštěvníků (AI)
    ↓
[6. Log do scraping_log]
```

## Další kroky

1. Rozšířit migrace: `zdroje` pole (sitemap, cms, struktura, velikost_threshold), `akce` (externi_hash, propojeni), nové `akce_zdroje`, `scraping_log`
2. Vytvořit seeder krajů (7 pro východní ČR)
3. Implementovat `ZdrojAnalyzer` service (detekce CMS, sitemap, struktura)
4. Implementovat `AkceExtractor` service (AI extrakce detail + klasifikace)
5. Implementovat `RegionFilter` (filtr 7 krajů)
6. Implementovat `VelikostKlasifikator` (AI scoring)
7. Admin UI pro přidání zdroje + test extrakce
8. Přidat první 3 zdroje: kudyznudy.cz (filtrovat kraj), stankar.cz, webtrziste.cz

## Merge strategie (aktualizace 2026-04-24)

### Problém
Stejná akce může existovat ve více zdrojích (kudyznudy + stankar + webtrziste) s různou kvalitou dat.
Další komplikace: admin může pole upravit ručně — scraping to nesmí přepsat.

### Řešení — 3 vrstvy

**1. Fuzzy matching (`AkceMatcher`):**
- Strategie A: slug exact (`pout-u-sv-jiri-2026`)
- Strategie B: název + datum ± 3 dny + město LIKE
- Strategie C: similar_text ≥ 80 % + datum ± 3 dny + GPS < 1 km

**2. Field-level merge (`AkceMerger`):**
Priorita pravidel (shora dolů):
- `pole_manualni` → NIKDY nepřepsat
- Prázdné pole → DOPLNIT
- Popis: delší verze (1.2×) → PŘEPSAT
- Klíčové pole (datum, GPS, místo) konflikt → ULOŽIT DO `konflikty`, admin rozhodne
- Trust ranking: vyšší trust zdroje → PŘEPSAT
- `velikost_info` → APPEND z více zdrojů (`[kudyznudy] ... [stankar] ...`)
- `velikost_signaly` → MERGE JSON
- `velikost_skore` → vyšší vyhrává

**3. Trust ranking (`config/scraping.php`):**
```
kudyznudy: GPS=90, kontakt=85, popis=75
wordpress_mec (stankar): čas=85, popis=65, kontakt=55
webtrziste: velikost_signaly=95, vstupne=80, kontakt=20
manual: * = 100  (admin vyhraje vždy)
excel: najem=100, obrat=100
```

### Metadata sledovaná na akci

- `pole_manualni` — JSON: `{kontakt_email: "2026-04-24T10:30"}` — pole zamčená adminem
- `pole_zdroje` — JSON: `{gps_lat: "kudyznudy"}` — zdroj hodnoty
- `konflikty` — JSON: seznam konfliktních rozhodnutí, admin rozhoduje
- `merge_log` — JSON: posledních 20 merge operací (audit trail)
- `navrh_propojeni` — JSON: AI navrhuje ročníkové propojení ("Řípská pouť 2025")

### Auto-lock v admin UI

- Když admin edituje pole → auto-přidá do `pole_manualni` s timestampem
- Pole s visacím zámkem 🔒 v UI, tlačítko "Odemknout" vrátí kontrolu scrapingu
- Zobrazení: "ze zdroje: kudyznudy" u každého pole

### Ročníkové propojení

- `AkceMatcher::navrhniPropojeniRocniku()` hledá podobné akce z jiných let
- Uloží do `akce.navrh_propojeni` (JSON)
- Admin v UI vidí banner: "🔗 AI navrhuje propojení s: Řípská pouť 2025 (podobnost 92%)"
- Admin kliknutím propojí přes `propojena_s_akci_id`
