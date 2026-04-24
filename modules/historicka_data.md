# Historická data (XLS import)

## Stav: Implementováno — XLSX parser lokálně, data-seeder na produkci

**Rozhodnutí:** strategie **B** (kanonická akce + ročníkový výkaz).
XLSX se parsují **lokálně**, výsledek je JSON v repu, produkce běží přes
seeder (ne přes parser na serveru — zdrojové soubory na server nepatří).

## Pipeline

```
XLSX v temporary/ (necommitnuto)
          ↓ lokálně: php artisan excel:import temporary --export=...
database/data/historicka_data.json (commit)
          ↓ GitHub Actions deploy
server: ?migrate=1&seed=ImportHistorickychDatSeeder
          ↓ transakce
akce (1021) + akce_vykazy (529)
```

## Použití

### Lokálně — regenerace JSON
```bash
php artisan excel:import temporary --export=database/data/historicka_data.json
```

### Diagnostika (bez ukládání, bez JSON)
```bash
php artisan excel:import temporary --dry-run
php artisan excel:import temporary --dry-run -v           # top 30 duplicit
php artisan excel:import temporary --dry-run --soubor="Měsíční přehled.xlsx"
```

### Produkce
```
GET https://rajon.tuptudu.cz/deploy-hook.php?token=XXX&migrate=1&seed=ImportHistorickychDatSeeder
```

## Výsledek posledního exportu (2026-04-24)

- **1 021** kanonických akcí
- **529** ročníkových výkazů (po deduplikaci `(akce, rok)`)
- **0** orphan výkazů (každý ukazuje na existující akci)

Zbývající duplicity (3–16 matchů pro generické názvy „Farmářské trhy" apod.)
admin může ex-post sjednotit v admin UI.

## Implementace

### Třídy v `app/Services/ExcelImport/`
| Třída | Odpovědnost |
|---|---|
| `ExcelImportCommand` (`app/Console/Commands/`) | Artisan entry point |
| `FileSpec` | Pořadí + strategie per soubor |
| `FileImporter` | Orchestrace importu, 8 strategií |
| `XlsxReader` | Wrapper kolem openspout (eager-load všech sheetů) |
| `HeaderMapper` | Mapa `nazev/Akce/název` → `nazev` apod. |
| `DateParser` | DateTime + textové rozsahy, doplnění roku |
| `MoneyParser` | Extrakce CZK z volného textu |
| `PoznamkyParser` | `"2023: Prodej X Kč, Nájem Y"` → mikro-výkaz |
| `NazevNormalizer` | Normalizace názvu pro fuzzy matching |
| `AkceHistoricMatcher` | Hledání existující kanonické akce (bez data) |
| `ImportStats` | Počítadla |
| `ExportCollector` | Sběr do JSON (akce + výkazy s externím klíčem) |

### Seeder
| | |
|---|---|
| `database/seeders/ImportHistorickychDatSeeder.php` | Načte JSON → DB (idempotentně: match podle slug nebo (nazev, misto)) |
| `database/data/historicka_data.json` | Vygenerovaný export (committnuto) |

### Matcher — klíčové pravidlo
Match prochází jen když obě strany mají/nemají místo. Pokud obě mají, musí
být totožné nebo jedno substring druhého. **Nikdy** nematchne
„Farmářské trhy" (bez místa) s „Farmářské trhy Pardubice".

### DB
- Migrace `2026_04_24_000002_create_akce_vykazy_table.php`
- Migrace `2026_04_24_000003_akce_typ_pridat_sportovni.php` — enum rozšíření
- Model `App\Models\AkceVykaz`, relace `Akce::vykazy()`

## Balíček
`openspout/openspout ^5.6` — PHP 8.4 ZTS nemá ext-gd, kvůli tomu se nepoužilo
phpspreadsheet. Openspout je streamovací čtečka, bez obrazových závislostí.

## Účel
V `temporary/` je 9 XLSX souborů se 4 lety provozu WormUP na akcích
(2022 – 2025). Data jsou ručně udržovaná v různých tabulkách a šablonách, takže:

- **Stejná akce se objevuje opakovaně** napříč roky (plán N, výkaz N, plán N+1).
- **Schéma sloupců se v čase mění** — z „kdo pujde" (2022) přes „trzba cca"
  (2023) k finančnímu výkazu (Tržba / Nájem) v Měsíčním přehledu (2024–2025).
- **Poznámky obsahují strukturovaná data** — `"2023: Prodej 55509 Kč, Nájem 20%"`
  — která patří do samostatné tabulky ročníkových výkazů.

**Cíl:** jedním artisan commandem naparsovat XLSX a nasypat do DB — **bez**
admin UI. Zajímají nás **jen akce a jejich ročníkové obraty/nájmy**, ne
brigádníci / mzda / POS / stav přihlášky.

---

## 1. Inventura zdrojových souborů

| Soubor | Záznamů | Období | Co obsahuje | Přímá hodnota |
|---|---:|---|---|---|
| `Analýza festivaly.xlsx` | 28 | 2023 | tržba/den, počet dní, FB reach | výkaz 2023 (obrat) |
| `Festivaly 2022.xlsx` | 277 | 2022 (3–12) | plán + kontakty + nájem | seznam akcí 2022 |
| `Festivaly 2023.xlsx` | 218 | 2023 | plán + kontakty + nájem | seznam akcí 2023 |
| `Festivaly 2024 (1.část).xlsx` | 25 | Q1 2024 | jednoduchý list kontaktů | seznam akcí 2024 |
| `Festivaly 2024 (2.část) - Trhy Aleš.xlsx` | 45 | 2024 | 4 sheety (vína, food, beer, jarmarky) s **ročníkem a návštěvností** | akce 2024 + signály velikosti |
| `Měsíční přehled.xlsx` | 212 | 10/2024–12/2025 | **finanční výkaz**: Datum, Akce, Tržba, Nájem (Mzda/Jméno ignorujeme) | výkazy 2024–2025 |
| `Poutě 2025.xlsx` | 144 | 2025 + pochody | poutě (typ=`pout`) a pochody (typ=`sportovni_akce`) s krajem, návštěvností, nájmem | seznam poutí/pochodů 2025 |
| `Příprava 2024 výkaz 2023.xlsx` | 578 | 2023 výkaz + 2024 plán | hybrid — do května výkaz 2023 (PRODÁNO, obrat, nájem), od června plán 2024 s `Poznámky z minulých let` ve formě `"2023: Prodej X Kč, Nájem Y"` | **nejbohatší zdroj** výkazů 2023 + plán 2024 |
| `Příprava 2025 výkaz 2024 v1..xlsx` | 313 | 2025 plán | plán 2025 s `tržba 2024`, `tržba 2025`, `Návštěvnost`, `Poznámky` | plán 2025 + mikro-výkazy 2024 |
| **CELKEM** | **~1 840** | 2022–2025 | | |

Unikátních akcí po deduplikaci napříč roky odhaduji **≈ 400–600**.

## 2. Překryvy mezi soubory

Tytéž akce se typicky vyskytují ve 3–5 souborech:

```
Čokofestival Hradec Králové
  ├─ Festivaly 2022.xlsx              → plán + kontakty
  ├─ Festivaly 2023.xlsx              → plán + kontakty
  ├─ Příprava 2024 výkaz 2023.xlsx    → výkaz 2023 (obrat)
  ├─ Festivaly 2024 (1.část).xlsx     → plán 2024 (kontakt Chris)
  ├─ Měsíční přehled.xlsx             → tržba 2024 (Tržba/Nájem)
  └─ Příprava 2025 výkaz 2024 v1.     → poznámky + plán 2025
```

## 3. Sloupce, které z Excelů přebíráme

**Zachováváme:** název, datum, místo, kraj, organizátor, e-mail, telefon, web,
nájem, tržba, poznámky, návštěvnost, ročník.

**Zahazujeme** (uživatel rozhodl): POS, PRODÁNO (ks), výplata brigádníka, mzda,
brigádník / Jméno franšízanta, čas na stánku, stav přihlášky.

### Mapa originálních názvů → pole v DB

| Originální sloupce | DB pole |
|---|---|
| `nazev`, `Akce`, `název`, `Akce ` | `akce.nazev` |
| `termin`, `datum`, `Datum` | `akce.datum_od`, `akce.datum_do` (nejbližší ročník) + `akce_vykazy.datum_od/do` (archiv) |
| `misto`, `místo`, `Místo`, `Kde se koná`, `Město` | `akce.misto` |
| `kraj`, `Kraj` | `akce.kraj` |
| `organizator` | `akce.organizator` |
| `mail` | `akce.kontakt_email` |
| `mobil` | `akce.kontakt_telefon` |
| `web`, `Odkaz na akci` | `akce.web_url` |
| `cena najmu`, `nájem`, `Nájem`, `nájem na akci` | `akce_vykazy.najem` (CZK) |
| `obrat`, `PRODÁNO`, `tržba`, `Tržba`, `prodej` | `akce_vykazy.trzba` (CZK) |
| `Návštěvnost`, `Účast` | `akce.velikost_signaly.navstevnost` |
| `Ročník` | `akce.velikost_signaly.rocnik` |
| `Čas konání` | `akce.velikost_signaly.otevreno` |
| `poznamky`, `Poznámky`, `Poznámka` | `akce_vykazy.poznamka` (za daný ročník) |
| `Poznámky z minulých let` | **parsovat** `"2023: Prodej X, Nájem Y"` → vlastní `akce_vykazy` row roku N-1 |

## 4. Problémy v datech (parser s nimi musí počítat)

1. **Inkonzistentní formát data.** Většinou `YYYY-MM-DD 00:00:00`, ale časté
   textové rozsahy: `"9-10.6."`, `"5.-9.7."`, `"26.11.-23.12"`. Parser umí
   oba + dopočítá rok ze jména sheetu nebo souboru.
2. **Excel datetime s nesmyslným rokem.** `Festivaly 2023 → Únor → 2022-02-18`
   — rok bereme z kontextu souboru (2023), ne z buňky.
3. **Slepené texty.** `"24.-26. 2. 2023 Brno (Nová Zbrojovka) - BSF W"` — celý
   řádek v jedné buňce; pre-parsing do `datum + místo + název`.
4. **Duplicitní/posunuté sloupce.** `Festivaly 2022 → Červenec` má 2× `organizator`
   a druhý set hodnot — při čtení rozpoznat kollizi názvů.
5. **Různá jména hlaviček pro tentýž atribut** — `misto` / `místo` / `Místo`,
   `nazev` / `Akce` / `Akce ` (trailing space). Řeší mapovací tabulka v § 3.
6. **Tržba v textu.** `"tržba 2024 - cca 40 000,-"`, `"tisíce"`, `"desetitisíce"`.
   Parser vytáhne číselnou částku, pokud lze; jinak jde celý text do `poznamka`.
7. **Nesjednocené názvy.** „Čokofestival" vs „Čoko festival" vs „Chris Cokofest"
   → fuzzy match (logiku máme z commitu 1a2cd33 — lev. normalizace + shoda místa).
8. **Sheet `Vánoční akce`** (Festivaly 2023, 13 řádků volného textu) — přeskočit
   import, ručně přepsat nebo nechat stranou.

## 5. DB struktura — návrh (čeká na rozhodnutí A/B)

### Otevřená otázka — propojení ročníků

Jak uložit „Čokofestival 2022" a „Čokofestival 2023" ve stejné tabulce:

- **A:** v `akce` každý ročník samostatný řádek, vazba přes
  `propojena_s_akci_id` na master. Katalog má duplikáty (každá akce N× podle
  ročníků). Dnešní `akce` je na to připravená.
- **B (doporučeno):** v `akce` jeden řádek **kanonické akce** (bez termínu),
  všechny ročníky žijí v nové tabulce `akce_vykazy`. Katalog je čistý —
  1 karta na akci s historií 2022–2025 ve výpisu.

Návrh níže počítá s **B**.

### Nová tabulka `akce_vykazy`

Ročníkový archiv konkrétní akce — jak dopadla tržba a nájem.

```php
Schema::create('akce_vykazy', function (Blueprint $table) {
    $table->id();
    $table->foreignId('akce_id')->constrained('akce')->cascadeOnDelete();
    $table->unsignedSmallInteger('rok');                      // 2022–2025
    $table->date('datum_od')->nullable();                     // reálný termín ročníku
    $table->date('datum_do')->nullable();
    $table->unsignedInteger('trzba')->nullable();             // CZK
    $table->unsignedInteger('najem')->nullable();             // CZK
    $table->text('poznamka')->nullable();                     // volný text z originálu
    $table->string('zdroj_excel')->nullable();                // "Měsíční přehled.xlsx / Říjen 2024"
    $table->timestamp('vytvoreno')->nullable();
    $table->timestamp('upraveno')->nullable();

    $table->unique(['akce_id', 'rok']);
    $table->index('rok');
});
```

### Úpravy `akce`

- Ponechat strukturu, jen interpretovat: `datum_od/do` = **nejbližší budoucí
  termín** (default: termín nejnovějšího ročníku z `akce_vykazy`).
- Rozšířit enum `akce.typ` o **`sportovni_akce`** (pochody, běhy, cyklo,
  padesátky z Poutě 2025 / sheet Pochody).

### Enum `akce.typ` po úpravě
`pout, food_festival, slavnosti, vinobrani, dynobrani, farmarske_trhy,
vanocni_trhy, jarmark, festival, sportovni_akce, jiny`

## 6. Import pipeline — přímý skript, bez admin UI

`php artisan excel:import temporary/` udělá v jednom průchodu pro každý XLSX:

1. **Parse** — najít hlavičku sheetu, namapovat sloupce dle § 3, naparsovat
   datum (datetime + textové rozsahy), doplnit rok z kontextu souboru/sheetu.
2. **Dedup + match** — pro každý řádek fuzzy-match proti existujícím `akce`
   (commit 1a2cd33). `score >= 0.85` → použít existující `akce_id`.
   `score < 0.85` → vytvořit novou `akce`.
3. **Zapsat `akce_vykazy`** — `(akce_id, rok)` unique; při konfliktu mergovat
   (prázdná pole doplnit, konflikty zalogovat do `poznamka`).
4. **Speciální pravidla:**
   - `Poznámky z minulých let = "2023: Prodej X Kč, Nájem Y"` → extra
     `akce_vykazy` row rok=2023.
   - Sheet `Pochody` (Poutě 2025) → `akce.typ = sportovni_akce`.
   - `Festivaly 2024 (2.část) Aleš` → `velikost_signaly.rocnik` +
     `velikost_signaly.navstevnost`.

### Pořadí zpracování (ovlivňuje kvalitu matche — nejpřesnější první)

1. `Měsíční přehled.xlsx` — čistá kostra `akce_vykazy` 10/2024–12/2025.
2. `Příprava 2024 výkaz 2023.xlsx` — `akce_vykazy` 2023 + nové akce 2024.
3. `Příprava 2025 výkaz 2024 v1..xlsx` — plán 2025 + mikro-výkazy 2024.
4. `Analýza festivaly.xlsx` — doplnění 2023 výkazů (FB reach do poznámky).
5. `Poutě 2025.xlsx` — `typ=pout` / `typ=sportovni_akce`.
6. `Festivaly 2024 (2.část) - Trhy Aleš.xlsx` — ročník + návštěvnost.
7. `Festivaly 2024 (1.část).xlsx`, `Festivaly 2023.xlsx`, `Festivaly 2022.xlsx`
   — doplnit kontakty na akcích, které už existují z kroků 1–6.

### Command options
- `--dry-run` — jen vypíše co by udělal (zákl. workflow CLAUDE.md).
- `--soubor=...` — import jednoho souboru (pro ladění).
- `--od-kroku=N` — pokračovat v pořadí od kroku N (po selhání).

## 7. Co projekt NEbude dělat

- ❌ Žádné admin UI pro import (rozhodnutí uživatele).
- ❌ Ignorujeme: brigádníci, mzda, výplata, POS, prodáno ks, čas na stánku,
  stav přihlášky.
- ❌ `Vánoční akce` sheet (Festivaly 2023) — volný text, přeskočit.

## 8. Pomocné artefakty (v `temporary/`)

- `_explore.py` — dump prvních 8 řádků každého sheetu všech souborů.
- `_count.py` — spočítá reálné neprázdné datové řádky per sheet.
- `_structure.txt`, `_counts.txt` — výstupy (UTF-8).

Po úspěšném importu mohou být smazány.
