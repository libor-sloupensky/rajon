# Scraping modul

## Stav: Základ připraven

## Služby
- `AkceScraperService` — stahování HTML z webů
- `AkceAiService` — AI extrakce akcí z HTML a e-mailů (Anthropic Claude Haiku)

## AI extrakce
- Vstup: URL → stažení HTML → strip tags → AI zpracování
- Výstup: JSON pole akcí s poli (nazev, typ, datum_od, datum_do, misto, organizator...)
- Model: claude-haiku-4-5-20251001 (konfigurovatelný přes .env)

## E-mail zpracování
- Vstup: předmět + tělo e-mailu
- Výstup: JSON objekt jedné akce

## TODO
- [ ] IMAP klient pro festivals@wormup.com
- [ ] Excel import (historická data)
- [ ] Parsery pro konkrétní katalogy (Akce v ČR, Festivaly.cz atd.)
- [ ] Cron job pro pravidelný scraping zdrojů
- [ ] Batch zpracování (Anthropic Batches API — 50% sleva)
