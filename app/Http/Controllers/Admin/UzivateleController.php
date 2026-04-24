<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pozvanka;
use App\Models\Uzivatel;
use App\Notifications\PozvankaNotifikace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class UzivateleController extends Controller
{
    public function index()
    {
        $uzivatele = Uzivatel::orderBy('vytvoreno', 'desc')->paginate(30);

        // Pozvánky: aktivní (čekající) + poslední přijaté/zrušené (10)
        $pozvankyAktivni = Pozvanka::with('pozval')
            ->where('stav', 'cekajici')
            ->orderBy('vytvoreno', 'desc')
            ->get();

        $pozvankyHistorie = Pozvanka::with('pozval', 'uzivatel')
            ->whereIn('stav', ['prijata', 'zrusena', 'expirovana'])
            ->orderBy('vytvoreno', 'desc')
            ->take(10)
            ->get();

        return view('admin.uzivatele.index', compact('uzivatele', 'pozvankyAktivni', 'pozvankyHistorie'));
    }

    /** Rychlé vytvoření pozvánky z formuláře na stránce /admin/uzivatele. */
    public function pozvat(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'jmeno' => ['nullable', 'string', 'max:255'],
            'prijmeni' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,fransizan'],
            'region' => ['nullable', 'string', 'max:100'],
            'platnost_dni' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        // Zrušit předchozí nevyřízené pozvánky na stejný e-mail
        Pozvanka::where('email', $data['email'])
            ->where('stav', 'cekajici')
            ->update(['stav' => 'zrusena']);

        $pozvanka = Pozvanka::create([
            'token' => Pozvanka::generujToken(),
            'email' => $data['email'],
            'jmeno' => $data['jmeno'] ?? null,
            'prijmeni' => $data['prijmeni'] ?? null,
            'role' => $data['role'],
            'region' => $data['region'] ?? null,
            'stav' => 'cekajici',
            'plati_do' => now()->addDays((int) $data['platnost_dni']),
            'pozval_uzivatel_id' => $request->user()->id,
        ]);

        try {
            Notification::route('mail', $data['email'])
                ->notify(new PozvankaNotifikace($pozvanka));
            return back()->with('success', 'Pozvánka odeslána na ' . $data['email']);
        } catch (\Exception $e) {
            return back()->with('info', 'Pozvánka vytvořena, ale e-mail se nepodařilo odeslat. Odkaz: ' . $pozvanka->url());
        }
    }

    public function resendPozvanku(Pozvanka $pozvanka)
    {
        if ($pozvanka->stav !== 'cekajici') {
            return back()->with('error', 'Lze odeslat pouze čekající pozvánky.');
        }

        try {
            Notification::route('mail', $pozvanka->email)
                ->notify(new PozvankaNotifikace($pozvanka));
            return back()->with('success', 'Pozvánka znovu odeslána.');
        } catch (\Exception $e) {
            return back()->with('error', 'E-mail se nepodařilo odeslat: ' . $e->getMessage());
        }
    }

    public function zrusitPozvanku(Pozvanka $pozvanka)
    {
        $pozvanka->update(['stav' => 'zrusena']);
        return back()->with('success', 'Pozvánka zrušena.');
    }
}
