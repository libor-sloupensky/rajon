# Rajón — Architektura

## Účel
Katalog akcí a festivalů (poutě, slavnosti, food festivaly, vinobraní...) pro franšízanty WormUP. Centrální nástroj pro vyhledávání, správu a rezervaci akcí.

## Tech Stack
- **Backend:** Laravel 13, PHP 8.4
- **Frontend:** Tailwind CSS v4, Alpine.js, Blade
- **AI:** Anthropic Claude API (Haiku) — scraping, extrakce dat z webů a e-mailů
- **Mapy:** Mapy.cz REST API
- **DB:** MySQL (produkce: localhost na Webglobe)
- **Deploy:** GitHub Actions + lftp → ftp.tuptudu.cz
- **Doména:** rajon.tuptudu.cz

## Moduly

### Katalog akcí (`akce`)
- Centrální databáze akcí ze všech zdrojů
- Filtrování: typ, kraj, okres, datum, fulltext
- Detail akce s kontaktními údaji a mapou
- Rezervace/přihlášení na akci

### Mapa (`mapa`)
- Zobrazení akcí na Mapy.cz
- Filtrování dle termínu a typu
- API endpoint pro JSON data

### Scraping (`scraping`)
- AI zpracování webových stránek → extrakce akcí
- Zpracování e-mailů z festivals@wormup.com
- Import z Excel souborů (historická data)
- Zdroje: katalogy, weby měst, individuální weby

### Auth (`auth`)
- Laravel Fortify + Socialite (Google OAuth)
- Role: admin, franšízant (fransizan)
- České pojmenování: uzivatele, heslo, jmeno...

### Deploy (`deploy`)
- GitHub Actions → build → lftp smart deploy
- deploy-hook.php: OPcache reset, cache clear, migrace

### Admin (`admin`)
- CRUD akcí, správa zdrojů, přehled uživatelů
- Middleware: JeAdmin

## Zdroje dat (3 typy)
1. **Historické Excel soubory** — akce z minulosti (obrat, nájem)
2. **E-maily** — festivals@wormup.com (nabídky od organizátorů)
3. **Webové stránky** — katalogy akcí + weby měst/festivalů

## Uživatelský workflow
1. Franšízant nastaví region
2. Vyhledá akce na mapě/v katalogu
3. Kontaktuje organizátora
4. Přihlásí se na akci (rezervace)
5. Může přidávat nové zdroje (URL → AI scraping)
