# Akce modul

## Stav: Připraveno

## Tabulka `akce`
- `nazev`, `slug` (unique), `typ` (enum: pout, food_festival, slavnosti, vinobrani, dynobrani, farmarske_trhy, vanocni_trhy, jarmark, festival, jiny)
- `datum_od`, `datum_do`, `misto`, `adresa`, `gps_lat`, `gps_lng`
- `okres`, `kraj`, `organizator`, `kontakt_email`, `kontakt_telefon`, `web_url`
- `zdroj_url`, `zdroj_typ` (scraping/email/excel/manual)
- `najem` (CZK), `obrat` (CZK), `poznamka`
- `stav` (navrh/overena/zrusena)

## Tabulka `rezervace`
- `akce_id`, `uzivatel_id`, `stav` (zajimam_se/prihlasena/potvrzena/zrusena)
- Unique constraint: [akce_id, uzivatel_id]

## Tabulka `zdroje`
- `nazev`, `url`, `typ` (katalog/web_mesta/email/excel/manual)
- `stav`, `posledni_scraping`, `pocet_akci`

## Controllery
- `AkceController` — index, show, mapa, mapaJson, rezervovat
- `Admin\AkceController` — CRUD, zdroje

## Views
- `akce/index.blade.php` — katalog s filtry
- `akce/show.blade.php` — detail akce
- `akce/mapa.blade.php` — mapové zobrazení
