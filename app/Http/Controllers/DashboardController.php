<?php

namespace App\Http\Controllers;

use App\Models\Rezervace;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $uzivatelId = Auth::id();
        $dnes = now()->startOfDay();

        // Společný základ — moje aktivní rezervace s nezrušenými akcemi
        $baseQuery = Rezervace::query()
            ->where('rezervace.uzivatel_id', $uzivatelId)
            ->where('rezervace.stav', '!=', 'zrusena')
            ->join('akce', 'akce.id', '=', 'rezervace.akce_id')
            ->where('akce.stav', '!=', 'zrusena')
            ->whereNotNull('akce.datum_od')
            ->select('rezervace.*');

        // Budoucí — datum_od >= dnes, řazeno od nejbližších
        $budouci = (clone $baseQuery)
            ->where('akce.datum_od', '>=', $dnes)
            ->orderBy('akce.datum_od', 'asc')
            ->with('akce')
            ->get();

        // Uplynulé — datum_od < dnes, řazeno od nejnovějších (= nejbližších v minulosti)
        $uplynule = (clone $baseQuery)
            ->where('akce.datum_od', '<', $dnes)
            ->orderBy('akce.datum_od', 'desc')
            ->with('akce')
            ->get();

        return view('dashboard.index', compact('budouci', 'uplynule'));
    }
}
