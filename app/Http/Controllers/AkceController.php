<?php

namespace App\Http\Controllers;

use App\Models\Akce;
use App\Models\Rezervace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AkceController extends Controller
{
    public function index(Request $request)
    {
        // Zobrazujeme všechny akce kromě zrušených
        $query = Akce::where('stav', '!=', 'zrusena');

        if ($request->filled('hledat')) {
            $query->where('nazev', 'like', '%' . $request->hledat . '%');
        }

        if ($request->filled('typ')) {
            $query->where('typ', $request->typ);
        }

        if ($request->filled('kraj')) {
            $query->where('kraj', $request->kraj);
        }

        if ($request->filled('mesic')) {
            $query->whereMonth('datum_od', $request->mesic);
        }

        if ($request->filled('rok')) {
            $query->whereYear('datum_od', $request->rok);
        }

        $akce = $query->orderBy('datum_od')->paginate(20);

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
}
