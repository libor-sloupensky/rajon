<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Akce;
use App\Models\Zdroj;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AkceController extends Controller
{
    public function index(Request $request)
    {
        $query = Akce::query();

        if ($request->filled('stav')) {
            $query->where('stav', $request->stav);
        }

        $akce = $query->orderBy('vytvoreno', 'desc')->paginate(30);

        return view('admin.akce.index', compact('akce'));
    }

    public function create()
    {
        return view('admin.akce.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
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
            'kontakt_telefon' => ['nullable', 'string', 'max:20'],
            'web_url' => ['nullable', 'url', 'max:500'],
            'najem' => ['nullable', 'integer'],
            'obrat' => ['nullable', 'integer'],
            'poznamka' => ['nullable', 'string'],
            'stav' => ['required', 'string'],
        ]);

        $data['slug'] = Str::slug($data['nazev']);
        $data['zdroj_typ'] = 'manual';
        $data['uzivatel_id'] = $request->user()->id;

        // Zajistit unikátní slug
        $counter = 2;
        $originalSlug = $data['slug'];
        while (Akce::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter++;
        }

        Akce::create($data);

        return redirect()->route('admin.akce.index')->with('success', 'Akce vytvořena.');
    }

    public function edit(Akce $akce)
    {
        return view('admin.akce.edit', compact('akce'));
    }

    public function update(Request $request, Akce $akce)
    {
        $data = $request->validate([
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
            'kontakt_telefon' => ['nullable', 'string', 'max:20'],
            'web_url' => ['nullable', 'url', 'max:500'],
            'najem' => ['nullable', 'integer'],
            'obrat' => ['nullable', 'integer'],
            'poznamka' => ['nullable', 'string'],
            'stav' => ['required', 'string'],
        ]);

        $akce->update($data);

        return redirect()->route('admin.akce.index')->with('success', 'Akce aktualizována.');
    }

    public function destroy(Akce $akce)
    {
        $akce->delete();

        return redirect()->route('admin.akce.index')->with('success', 'Akce smazána.');
    }

    public function zdroje()
    {
        $zdroje = Zdroj::orderBy('vytvoreno', 'desc')->paginate(20);

        return view('admin.zdroje.index', compact('zdroje'));
    }
}
