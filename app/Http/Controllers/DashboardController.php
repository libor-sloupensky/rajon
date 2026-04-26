<?php

namespace App\Http\Controllers;

use App\Models\Rezervace;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $uzivatel = Auth::user();

        $mojeRezervace = Rezervace::where('uzivatel_id', $uzivatel->id)
            ->with('akce')
            ->whereHas('akce', fn ($q) => $q->where('datum_od', '>=', now()))
            ->orderBy('vytvoreno', 'desc')
            ->get();

        return view('dashboard.index', compact('mojeRezervace'));
    }
}
