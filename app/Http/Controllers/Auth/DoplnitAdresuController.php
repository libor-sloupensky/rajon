<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Geokoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoplnitAdresuController extends Controller
{
    public function zobrazit()
    {
        return view('auth.doplnit-adresu');
    }

    public function ulozit(Request $request, Geokoder $geokoder)
    {
        $data = $request->validate([
            'mesto' => ['required', 'string', 'max:100'],
            'psc' => ['required', 'string', 'regex:/^\d{3}\s?\d{2}$/'],
        ]);

        $gps = $geokoder->geokodujAdresuUzivatele($data['mesto'], $data['psc'] ?? null);
        if (!$gps) {
            return back()->withErrors(['mesto' => 'Tuto obec nelze najít. Zkuste přidat PSČ.'])
                         ->withInput();
        }

        Auth::user()->update([
            'mesto' => $data['mesto'],
            'psc' => $data['psc'] ?? null,
            'gps_lat' => $gps['gps_lat'],
            'gps_lng' => $gps['gps_lng'],
        ]);

        return redirect('/dashboard')->with('success', 'Adresa uložena.');
    }
}
