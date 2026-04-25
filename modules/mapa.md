# Mapa modul

## Stav: Implementováno (verze 1)

## Implementace
- Leaflet 1.9.4 + Mapy.cz tile server (`api.mapy.cz/v1/maptiles/basic/256/...`)
- API klíč z `config('services.mapycz.api_key')` (env `MAPYCZ_API_KEY`)
- Markery jako kroužky barevné podle `typ` akce (pout, food_festival, …)
- Popup: název, místo, datum
- Auto-fit bounds na rozsah dat (s padding + maxZoom)
- Fallback hláška pokud API klíč chybí
- Atribuce Mapy.cz (povinná)

## Datový vstup
`AkceController::mapa()` posílá akce s `gps_lat/gps_lng`, `stav='overena'`,
`datum_od >= now()`. Akce z XLSX importu mají `stav='navrh'` a žádné GPS,
zobrazí se až po geokódování + ručním schválení adminem.

## TODO (verze 2+)
- [ ] Marker clustery pro velký počet akcí (po geokódování)
- [ ] Filtrování na mapě (typ, termín, kraj)
- [ ] Geokódování adres (adresa/místo → GPS) přes Mapy.cz Geocoding API
- [ ] Možnost zobrazit i `stav='navrh'` (admin režim)
