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
}
