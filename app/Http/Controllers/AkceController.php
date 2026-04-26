<?php

namespace App\Http\Controllers;

use App\Models\Akce;
use App\Models\AkceUzivatel;
use App\Models\Rezervace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AkceController extends Controller
{
    /** Sjednocený katalog + správa. Každý přihlášený smí editovat. */
    public function index(Request $request)
    {
        $query = Akce::query();

        // Defaultně skryjeme zrušené (pokud admin vyloženě nechce)
        if ($request->get('stav') !== 'zrusena' && !$request->boolean('vse_stavy')) {
            $query->where('stav', '!=', 'zrusena');
        }

        if ($request->filled('stav')) {
            $query->where('stav', $request->stav);
        }

        if ($request->filled('hledat')) {
            $h = '%' . $request->hledat . '%';
            $query->where(function ($q) use ($h) {
                $q->where('nazev', 'like', $h)
                  ->orWhere('misto', 'like', $h)
                  ->orWhere('adresa', 'like', $h)
                  ->orWhere('organizator', 'like', $h);
            });
        }

        if ($request->filled('typ')) {
            $query->where('typ', $request->typ);
        }

        // Filtr podle původu — z webu (scraping/manual) vs z XLS (excel)
        if ($request->filled('zdroj_typ')) {
            if ($request->zdroj_typ === 'web') {
                $query->whereIn('zdroj_typ', ['scraping', 'manual'])
                      ->orWhereNull('zdroj_typ');
            } elseif ($request->zdroj_typ === 'excel') {
                $query->where('zdroj_typ', 'excel');
            }
        }

        if ($request->filled('kraj')) {
            $query->where('kraj', 'like', '%' . $request->kraj . '%');
        }

        // Datum od / do — overlap: akce zasahuje aspoň jedním dnem do rozsahu filtru.
        // Pokud akce nemá datum_do, bere se jako jednodenní (datum_od).
        // Akce bez datum_od se při filtraci nezobrazí.
        if ($request->filled('datum_od')) {
            $query->whereNotNull('datum_od')->where(function ($q) use ($request) {
                $q->whereDate('datum_do', '>=', $request->datum_od)
                  ->orWhere(function ($qq) use ($request) {
                      $qq->whereNull('datum_do')
                         ->whereDate('datum_od', '>=', $request->datum_od);
                  });
            });
        }
        if ($request->filled('datum_do')) {
            $query->whereNotNull('datum_od')->whereDate('datum_od', '<=', $request->datum_do);
        }

        // Měsíc/rok (zachováváme zpětnou kompatibilitu URL parametrů)
        if ($request->filled('mesic')) {
            $query->whereMonth('datum_od', $request->mesic);
        }
        if ($request->filled('rok')) {
            $query->whereYear('datum_od', $request->rok);
        }

        // Defaultně jen budoucí akce, lze přepnout ?vse=1
        if (!$request->boolean('vse') && !$request->filled('datum_od') && !$request->filled('datum_do')) {
            $query->where(function ($q) {
                $q->whereNull('datum_od')
                  ->orWhere('datum_od', '>=', now()->startOfDay());
            });
        }

        // Filtr "moje rezervované"
        $uzivatelId = Auth::id();
        if ($uzivatelId && $request->boolean('moje_rezervovane')) {
            $query->whereHas('rezervace', function ($q) use ($uzivatelId) {
                $q->where('uzivatel_id', $uzivatelId)
                  ->where('stav', '!=', 'zrusena');
            });
        }

        // Order: per-user palec ovlivňuje řazení (nahoru, null, stred, dolu).
        if ($uzivatelId) {
            $query->leftJoin('akce_uzivatel as au_filter', function ($j) use ($uzivatelId) {
                $j->on('au_filter.akce_id', '=', 'akce.id')
                  ->where('au_filter.uzivatel_id', '=', $uzivatelId);
            })
            ->select('akce.*', 'au_filter.palec as muj_palec', 'au_filter.osobni_poznamka as moje_poznamka')
            ->orderByRaw("FIELD(au_filter.palec, 'nahoru', NULL, 'stred', 'dolu')")
            ->orderBy('akce.datum_od');
        } else {
            $query->orderBy('datum_od');
        }

        // Eager load rezervace s uživateli (pro zobrazení "Rezervováno: Jan Novák")
        $query->with(['rezervace' => fn ($q) => $q->where('stav', '!=', 'zrusena')->with('uzivatel:id,jmeno,prijmeni')]);

        $akce = $query->paginate(30)->withQueryString();

        return view('akce.index', compact('akce'));
    }

    /** Uloží osobní palec hodnocení (per-user). Pokud je akce rezervovaná, palec je uzamčen na 'nahoru'. */
    public function palec(Request $request, Akce $akce)
    {
        $request->validate(['palec' => ['nullable', 'in:nahoru,stred,dolu']]);
        $uzivatelId = Auth::id();
        if (!$uzivatelId) abort(401);

        // Pokud má uživatel aktivní rezervaci, palec je uzamčen na 'nahoru'
        $jeRezervovano = Rezervace::where('akce_id', $akce->id)
            ->where('uzivatel_id', $uzivatelId)
            ->where('stav', '!=', 'zrusena')
            ->exists();

        if ($jeRezervovano) {
            return response()->json([
                'ok' => false,
                'palec' => 'nahoru',
                'duvod' => 'Akce je rezervovaná — palec uzamčen na nahoru. Pro změnu nejprve zrušte rezervaci.',
            ], 422);
        }

        AkceUzivatel::updateOrCreate(
            ['akce_id' => $akce->id, 'uzivatel_id' => $uzivatelId],
            ['palec' => $request->input('palec')],
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'palec' => $request->input('palec')]);
        }
        return back();
    }

    /** Uloží osobní poznámku (per-user). */
    public function poznamka(Request $request, Akce $akce)
    {
        $request->validate(['poznamka' => ['nullable', 'string', 'max:1000']]);
        $uzivatelId = Auth::id();
        if (!$uzivatelId) abort(401);

        AkceUzivatel::updateOrCreate(
            ['akce_id' => $akce->id, 'uzivatel_id' => $uzivatelId],
            ['osobni_poznamka' => $request->input('poznamka')],
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'poznamka' => $request->input('poznamka')]);
        }
        return back();
    }

    /** Inline update jednoho pole akce (společné pro všechny uživatele). */
    public function inlineUpdate(Request $request, Akce $akce)
    {
        $povolenaPole = ['nazev', 'typ', 'datum_od', 'datum_do', 'misto', 'adresa',
                         'okres', 'kraj', 'organizator', 'kontakt_email', 'kontakt_telefon',
                         'web_url', 'vstupne', 'popis', 'najem', 'obrat', 'stav'];

        $pole = $request->input('pole');
        if (!in_array($pole, $povolenaPole, true)) {
            return response()->json(['error' => 'Neplatné pole'], 422);
        }

        $rules = [
            'nazev' => ['required', 'string', 'max:255'],
            'typ' => ['required', 'string'],
            'popis' => ['nullable', 'string'],
            'datum_od' => ['nullable', 'date'],
            'datum_do' => ['nullable', 'date', 'after_or_equal:datum_od'],
            'misto' => ['nullable', 'string', 'max:255'],
            'adresa' => ['nullable', 'string', 'max:255'],
            'okres' => ['nullable', 'string', 'max:100'],
            'kraj' => ['nullable', 'string', 'max:100'],
            'organizator' => ['nullable', 'string', 'max:255'],
            'kontakt_email' => ['nullable', 'email', 'max:255'],
            'kontakt_telefon' => ['nullable', 'string', 'max:50'],
            'web_url' => ['nullable', 'url', 'max:500'],
            'vstupne' => ['nullable', 'string', 'max:100'],
            'najem' => ['nullable', 'integer'],
            'obrat' => ['nullable', 'integer'],
            'stav' => ['required', 'string', 'in:navrh,overena,zrusena'],
        ];

        $data = $request->validate(['hodnota' => $rules[$pole] ?? ['nullable', 'string']]);
        $novaHodnota = $data['hodnota'] ?? null;

        // Auto-lock: pole se označí jako manuálně upravené
        $manualni = $akce->pole_manualni ?? [];
        $zdroje = $akce->pole_zdroje ?? [];
        if ((string) $akce->$pole !== (string) $novaHodnota) {
            $manualni[$pole] = now()->toIso8601String();
            $zdroje[$pole] = 'manual';
        }

        $akce->update([
            $pole => $novaHodnota,
            'pole_manualni' => $manualni,
            'pole_zdroje' => $zdroje,
        ]);

        return response()->json(['ok' => true, 'pole' => $pole, 'hodnota' => $novaHodnota]);
    }

    public function create()
    {
        return view('akce.create');
    }

    public function store(Request $request)
    {
        $data = $this->validovat($request);

        $data['slug'] = $this->vytvorUnikatniSlug($data['nazev']);
        $data['zdroj_typ'] = 'manual';
        $data['uzivatel_id'] = $request->user()->id;

        // Manuální vytvoření — všechna vyplněná pole jsou trust 100 (nepřepisuje se)
        $manualni = [];
        $zdroje = [];
        foreach ($data as $pole => $hodnota) {
            if ($hodnota !== null && $hodnota !== '' && !in_array($pole, ['slug', 'zdroj_typ', 'uzivatel_id'], true)) {
                $manualni[$pole] = now()->toIso8601String();
                $zdroje[$pole] = 'manual';
            }
        }
        $data['pole_manualni'] = $manualni;
        $data['pole_zdroje'] = $zdroje;

        $akce = Akce::create($data);

        return redirect()->route('akce.index')->with('success', 'Akce vytvořena.');
    }

    public function edit(Akce $akce)
    {
        return view('akce.edit', compact('akce'));
    }

    public function update(Request $request, Akce $akce)
    {
        $data = $this->validovat($request, true);

        // Auto-lock: pole, které uživatel změnil, označíme jako manuální
        $manualni = $akce->pole_manualni ?? [];
        $zdroje = $akce->pole_zdroje ?? [];
        foreach ($data as $pole => $novaHodnota) {
            if ((string) $akce->$pole !== (string) $novaHodnota) {
                $manualni[$pole] = now()->toIso8601String();
                $zdroje[$pole] = 'manual';
            }
        }
        $data['pole_manualni'] = $manualni;
        $data['pole_zdroje'] = $zdroje;

        $akce->update($data);

        return redirect()->route('akce.edit', $akce)->with('success', 'Akce aktualizována. Upravená pole jsou zamčena proti přepisu scrapingem.');
    }

    /** Odemknout pole — scraping ho zase bude moci aktualizovat. */
    public function odemknoutPole(Request $request, Akce $akce)
    {
        $request->validate(['pole' => ['required', 'string']]);
        $akce->odemknoutPole($request->pole);
        $akce->save();

        return back()->with('success', "Pole {$request->pole} odemknuto.");
    }

    public function destroy(Akce $akce)
    {
        $akce->delete();

        return redirect()->route('akce.index')->with('success', 'Akce smazána.');
    }

    public function mapa(Request $request)
    {
        $akce = Akce::where('stav', '!=', 'zrusena')
            ->whereNotNull('gps_lat')
            ->whereNotNull('gps_lng')
            ->where('datum_od', '>=', now())
            ->get(['id', 'nazev', 'typ', 'datum_od', 'datum_do', 'misto', 'gps_lat', 'gps_lng']);

        return view('akce.mapa', compact('akce'));
    }

    public function mapaJson(Request $request)
    {
        $akce = Akce::where('stav', '!=', 'zrusena')
            ->whereNotNull('gps_lat')
            ->whereNotNull('gps_lng')
            ->where('datum_od', '>=', now())
            ->get(['id', 'nazev', 'typ', 'datum_od', 'datum_do', 'misto', 'gps_lat', 'gps_lng', 'slug']);

        return response()->json($akce);
    }

    public function rezervovat(Request $request, Akce $akce)
    {
        $uzivatel = Auth::user();

        $existujici = Rezervace::where('akce_id', $akce->id)
            ->where('uzivatel_id', $uzivatel->id)
            ->first();

        if ($existujici && $existujici->stav !== 'zrusena') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => true, 'info' => 'Již rezervováno']);
            }
            return back()->with('info', 'Na tuto akci jste již přihlášeni.');
        }

        if ($existujici) {
            // Obnovit zrušenou rezervaci
            $existujici->update(['stav' => 'zajimam_se', 'poznamka' => $request->input('poznamka')]);
        } else {
            Rezervace::create([
                'akce_id' => $akce->id,
                'uzivatel_id' => $uzivatel->id,
                'stav' => 'zajimam_se',
                'poznamka' => $request->input('poznamka'),
            ]);
        }

        // Auto: palec=nahoru (uzamčeno, dokud akce zůstává rezervovaná)
        AkceUzivatel::updateOrCreate(
            ['akce_id' => $akce->id, 'uzivatel_id' => $uzivatel->id],
            ['palec' => 'nahoru'],
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'rezervovano' => true]);
        }
        return back()->with('success', 'Akce byla přidána do vašeho kalendáře.');
    }

    /** Zrušit rezervaci. */
    public function zrusitRezervaci(Request $request, Akce $akce)
    {
        $uzivatel = Auth::user();
        Rezervace::where('akce_id', $akce->id)
            ->where('uzivatel_id', $uzivatel->id)
            ->update(['stav' => 'zrusena']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'rezervovano' => false]);
        }
        return back()->with('success', 'Rezervace zrušena.');
    }

    public function pridatZdroj(Request $request)
    {
        $request->validate([
            'url' => ['required', 'url', 'max:500'],
        ]);

        return view('akce.zpracovani-zdroje', ['url' => $request->url]);
    }

    /** Společná validace pro store/update. */
    private function validovat(Request $request, bool $update = false): array
    {
        return $request->validate([
            'nazev' => ['required', 'string', 'max:255'],
            'typ' => ['required', 'string'],
            'popis' => ['nullable', 'string'],
            'datum_od' => ['nullable', 'date'],
            'datum_do' => ['nullable', 'date', 'after_or_equal:datum_od'],
            'misto' => ['nullable', 'string', 'max:255'],
            'adresa' => ['nullable', 'string', 'max:255'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_lng' => ['nullable', 'numeric'],
            'okres' => ['nullable', 'string', 'max:100'],
            'kraj' => ['nullable', 'string', 'max:100'],
            'organizator' => ['nullable', 'string', 'max:255'],
            'kontakt_email' => ['nullable', 'email', 'max:255'],
            'kontakt_telefon' => ['nullable', 'string', 'max:50'],
            'web_url' => ['nullable', 'url', 'max:500'],
            'najem' => ['nullable', 'integer'],
            'obrat' => ['nullable', 'integer'],
            'poznamka' => ['nullable', 'string'],
            'admin_komentar' => ['nullable', 'string'],
            'stav' => ['required', 'string', 'in:navrh,overena,zrusena'],
        ]);
    }

    private function vytvorUnikatniSlug(string $nazev): string
    {
        $slug = Str::slug($nazev);
        $original = $slug;
        $i = 2;
        while (Akce::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
