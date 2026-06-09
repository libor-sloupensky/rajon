<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SouborKeStazeni;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SouboryController extends Controller
{
    /** Nahrát nový soubor (uloží do storage/app/soubory). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nazev' => ['required', 'string', 'max:255'],
            'popis' => ['nullable', 'string', 'max:255'],
            'soubor' => ['required', 'file', 'max:30720'], // 30 MB
        ]);

        $file = $request->file('soubor');
        $ext = strtolower($file->getClientOriginalExtension());
        $velikost = $file->getSize();

        // Bezpečný název na disku (ASCII slug + krátký hash proti kolizím)
        $zaklad = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'soubor';
        $cesta = $zaklad . '-' . substr(md5(uniqid('', true)), 0, 8) . ($ext ? ".{$ext}" : '');

        $file->storeAs('soubory', $cesta); // → storage/app/soubory/$cesta

        SouborKeStazeni::create([
            'nazev' => $data['nazev'],
            'popis' => $data['popis'] ?? null,
            'zdroj' => 'storage',
            'cesta' => $cesta,
            'download_nazev' => $file->getClientOriginalName(),
            'typ' => strtoupper($ext) ?: null,
            'velikost' => $velikost,
            'poradi' => (int) SouborKeStazeni::max('poradi') + 1,
        ]);

        return back()->with('success', 'Soubor nahrán.');
    }

    /** Přejmenovat (změnit zobrazovaný název a popis). */
    public function update(Request $request, SouborKeStazeni $soubor)
    {
        $data = $request->validate([
            'nazev' => ['required', 'string', 'max:255'],
            'popis' => ['nullable', 'string', 'max:255'],
        ]);

        $soubor->update($data);

        return back()->with('success', 'Soubor přejmenován.');
    }

    /** Smazat (u storage souboru i fyzicky). */
    public function destroy(SouborKeStazeni $soubor)
    {
        if ($soubor->zdroj === 'storage') {
            $cesta = storage_path('app/soubory/' . $soubor->cesta);
            if (is_file($cesta)) {
                @unlink($cesta);
            }
        }

        $soubor->delete();

        return back()->with('success', 'Soubor smazán.');
    }
}
