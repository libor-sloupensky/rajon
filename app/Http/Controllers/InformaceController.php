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
        return view('informace.ke-stazeni');
    }
}
