<?php

namespace App\Http\Controllers;

use App\Models\Akce;
use App\Models\Rezervace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Datum od / do — overlap akce s rozsahem
        if ($request->filled('datum_od')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('datum_do')
                  ->orWhere('datum_do', '>=', $request->datum_od);
            });
        }
        if ($request->filled('datum_do')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('datum_od')
                  ->orWhere('datum_od', '<=', $request->datum_do);
            });
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

        $akce = $query->orderBy('datum_od')->paginate(30)->withQueryString();

        return view('akce.index', compact('akce'));
    }

    public function show(Akce $akce)
    {
        $uzivatel = Auth::user();
        $rezervace = null;

        if ($uzivatel) {
            $rezervace = Rezervace::where('akce_id', $akce->id)
                ->where('uzivatel_id', $uzivatel->id)
                ->first();
        }

        return view('akce.show', compact('akce', 'rezervace'));
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

        return redirect()->route('akce.show', $akce)->with('success', 'Akce vytvořena.');
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

        if ($existujici) {
            return back()->with('info', 'Na tuto akci jste již přihlášeni.');
        }

        Rezervace::create([
            'akce_id' => $akce->id,
            'uzivatel_id' => $uzivatel->id,
            'stav' => 'zajimam_se',
            'poznamka' => $request->input('poznamka'),
        ]);

        return back()->with('success', 'Akce byla přidána do vašeho kalendáře.');
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
