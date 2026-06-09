<?php

namespace App\Http\Controllers;

/**
 * Informační stránky (pro franšízanty apod.).
 * Stránky jsou dostupné všem přihlášeným; odkazy jsou zatím jen v admin
 * sekci sidebaru, dokud je nepřesuneme do běžného menu.
 */
class InformaceController extends Controller
{
    public function fransizanti()
    {
        return view('informace.fransizanti');
    }

    public function faq()
    {
        return view('informace.faq');
    }

    public function jakProdavat()
    {
        return view('informace.jak-prodavat');
    }

    public function vybaveni()
    {
        return view('informace.vybaveni');
    }

    public function keStazeni()
    {
        $soubory = \App\Models\SouborKeStazeni::orderBy('poradi')->orderBy('id')->get();

        return view('informace.ke-stazeni', compact('soubory'));
    }

    /** Stažení souboru uloženého ve storage (nahraného adminem). */
    public function stahnoutSoubor(\App\Models\SouborKeStazeni $soubor)
    {
        if ($soubor->zdroj === 'public') {
            return redirect(asset('soubory/' . $soubor->cesta));
        }

        $cesta = storage_path('app/soubory/' . $soubor->cesta);
        abort_unless(is_file($cesta), 404);

        return response()->download($cesta, $soubor->download_nazev ?: $soubor->cesta);
    }
}
