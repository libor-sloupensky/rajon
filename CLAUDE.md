# Rajón — Pravidla pro Claude Code

## Jazyk
- Commit messages, komentáře v kódu a komunikace: **česky**
- Ceny a náklady uvádět v **CZK**

## Workflow — obecný vývoj
- Před úpravou kódu vždy přečíst aktuální stav souboru
- Preferovat úpravu existujících souborů před vytvářením nových
- Push jen funkční kód — testovat lokálně (`php artisan test`, dry-run)

## Workflow — scrapery a práce s externími weby
1. **Před psaním regexu** vždy stáhnout reálný HTML a analyzovat strukturu
2. **HTML vzorky** ukládat do `tests/fixtures/` jako referenci
3. **PHPUnit testy** spustit po každé změně parseru
4. **Lokální dry-run test** před push
5. **Netvrdím "hotovo"** dokud neukážu výsledek testu/dry-runu

## AI prompty — pravidla pro system prompty
- Každý system prompt MUSÍ začínat jasnou **definicí role** AI
- Role musí odpovídat kontextu: scraping = analytik, zpracování = extrahující agent
- Prompt musí specifikovat **úroveň detailu** a **formát odpovědi** (JSON)

## Technické konvence
- Framework: Laravel 13 (nejnovější), Tailwind CSS v4, Alpine.js, Blade
- České timestamps: `vytvoreno`/`upraveno` (CREATED_AT/UPDATED_AT)
- Blade: `@@context`/`@@type`/`@@id` v JSON-LD (Laravel má @context direktivu)
- Route binding: slug pro veřejné routy, `:id` pro admin routy
- `env()` nefunguje v route closures na produkci → používat `config()`
- Inline `style=""` pro barvy, které Tailwind v4 negeneruje

## Deploy
- GitHub Actions + lftp (smart deploy — jen změněné soubory)
- deploy-hook.php pro post-deploy operace (OPcache, cache clear, migrace)
- Server: gve08.vas-server.cz (Webglobe, stejný jako TupTuDu)
- Doména: rajon.tuptudu.cz
- DB: MySQL na localhost
- `SESSION_LIFETIME=10080` (7 dní)

## Testování
- PHPUnit: `php artisan test` (lokálně)
- Fixtures: `tests/fixtures/` — HTML vzorky z cílových webů
- DB lokálně nedostupná (hosting blokuje vzdálené připojení) → dry-run mode

## Správa projektu — modulární CONTEXT.md systém

### Struktura
- `modules/ARCHITECTURE.md` — celkový přehled projektu, tech stack, moduly
- `modules/{nazev}.md` — stav konkrétního modulu

### Pravidla spolupráce
- Na začátku práce na modulu přečíst příslušný `modules/{nazev}.md`
- Na konci sezení nebo na výzvu **"aktualizuj kontext"**: aktualizovat příslušný modul
- Nikdy nedělat změny v rozporu s `modules/ARCHITECTURE.md` bez upozornění

### Moduly
| Modul | Soubor | Popis |
|-------|--------|-------|
| auth | `modules/auth.md` | Přihlašování, Google OAuth, role |
| akce | `modules/akce.md` | Katalog akcí, filtrování, detail |
| mapa | `modules/mapa.md` | Mapy.cz integrace, zobrazení akcí |
| scraping | `modules/scraping.md` | AI scraping zdrojů, parsery |
| email | `modules/email.md` | IMAP zpracování, notifikace |
| deploy | `modules/deploy.md` | GitHub Actions, FTP, deploy-hook |
| admin | `modules/admin.md` | Administrace akcí, zdrojů, uživatelů |
