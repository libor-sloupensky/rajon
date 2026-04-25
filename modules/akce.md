# Akce modul

## Stav: Sjednoceno (katalog + správa = jeden modul)

## Tabulka `akce`
- `nazev`, `slug` (unique), `typ` (enum: pout, food_festival, slavnosti, mestske_slavnosti, obrani, vinobrani, dynobrani, farmarske_trhy, vanocni_trhy, velikonocni_trhy, jarmark, festival, sportovni_akce, koncert, divadlo, vystava, workshop, jiny)
- `datum_od`, `datum_do`, `misto`, `adresa`, `gps_lat`, `gps_lng`
- `okres`, `kraj`, `organizator`, `kontakt_email`, `kontakt_telefon`, `web_url`
- `zdroj_url`, `zdroj_typ` (scraping/email/excel/manual)
- `najem` (CZK), `obrat` (CZK), `poznamka`, `admin_komentar`
- `stav` (navrh/overena/zrusena)
- `pole_manualni` (JSON) — auto-lock zamčená pole
- `pole_zdroje` (JSON) — kdo nastavil které pole
- `konflikty`, `merge_log`, `navrh_propojeni` (JSON)
- `velikost_skore`, `velikost_stav`, `velikost_info`, `velikost_signaly`

## Sjednocení (2026-04-25)
Katalog akcí (veřejný) a Správa akcí (admin) jsou sloučené:
- Jediná routa `/akce` (mimo JeAdmin middleware) — každý přihlášený uživatel
  může akce vytvářet, editovat (auto-lock změněných polí), odemykat pole.
- `Admin\AkceController` byl smazán.
- Smazat akci smí jen admin (tlačítko Smazat zobrazené jen adminům).

## Routy (mimo `/admin`)
- `GET /akce` → AkceController@index
- `GET /akce/nova` → AkceController@create
- `POST /akce` → AkceController@store
- `GET /akce/{slug}` → AkceController@show
- `GET /akce/{id}/upravit` → AkceController@edit
- `PUT /akce/{id}` → AkceController@update
- `POST /akce/{id}/odemknout-pole` → AkceController@odemknoutPole
- `DELETE /akce/{id}` → AkceController@destroy (admin)
- `POST /akce/{id}/rezervovat` → AkceController@rezervovat
- `GET /mapa`, `GET /api/akce-mapa`

## Filtry v indexu
- `hledat` (nazev / misto / adresa / organizator)
- `typ`, `kraj`
- `datum_od`, `datum_do` (overlap s rozsahem akce)
- `mesic`, `rok` (zpětná kompatibilita)
- `stav` (jen pro adminy)
- `vse=1` — zobrazit i minulé akce (default = jen budoucí)
- `vse_stavy=1` — zobrazit i zrušené

## Tabulka `rezervace`
- `akce_id`, `uzivatel_id`, `stav` (zajimam_se/prihlasena/potvrzena/zrusena)
- Unique constraint: [akce_id, uzivatel_id]

## Tabulka `zdroje`
- `nazev`, `url`, `typ` (katalog/web_mesta/email/excel/manual)
- `stav`, `posledni_scraping`, `pocet_akci`

## Controllery
- `AkceController` — index, create, store, show, edit, update,
  odemknoutPole, destroy, mapa, mapaJson, rezervovat, pridatZdroj

## Views
- `akce/index.blade.php` — katalog + správa s filtry (datum, kraj, hledání)
- `akce/show.blade.php` — detail akce
- `akce/create.blade.php` — formulář pro novou akci
- `akce/edit.blade.php` — editace s indikátory 🔒/⚠️/🔗 a auto-lock
- `akce/mapa.blade.php` — mapové zobrazení
