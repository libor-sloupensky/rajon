<?php

namespace App\Http\Controllers;

use App\Models\Akce;
use App\Models\Rezervace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $uzivatel = Auth::user();

        $nadchazejiciAkce = Akce::where('datum_od', '>=', now())
            ->where('stav', 'overena')
            ->orderBy('datum_od')
            ->take(10)
            ->get();

        $mojeRezervace = Rezervace::where('uzivatel_id', $uzivatel->id)
            ->with('akce')
            ->whereHas('akce', fn ($q) => $q->where('datum_od', '>=', now()))
            ->orderBy('vytvoreno', 'desc')
            ->get();

        return view('dashboard.index', compact('nadchazejiciAkce', 'mojeRezervace'));
    }
}
