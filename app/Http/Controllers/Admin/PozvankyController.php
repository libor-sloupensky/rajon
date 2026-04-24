<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pozvanka;
use App\Notifications\PozvankaNotifikace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PozvankyController extends Controller
{
    public function index()
    {
        $pozvanky = Pozvanka::with('pozval', 'uzivatel')
            ->orderBy('vytvoreno', 'desc')
            ->paginate(30);

        return view('admin.pozvanky.index', compact('pozvanky'));
    }

    public function create()
    {
        return view('admin.pozvanky.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'jmeno' => ['nullable', 'string', 'max:255'],
            'prijmeni' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,fransizan'],
            'region' => ['nullable', 'string', 'max:100'],
            'poznamka' => ['nullable', 'string'],
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
            'poznamka' => $data['poznamka'] ?? null,
        ]);

        // Odeslat e-mail
        try {
            Notification::route('mail', $data['email'])
                ->notify(new PozvankaNotifikace($pozvanka));
            $message = 'Pozvánka odeslána na ' . $data['email'];
        } catch (\Exception $e) {
            $message = 'Pozvánka vytvořena, ale e-mail se nepodařilo odeslat. Odkaz: ' . $pozvanka->url();
        }

        return redirect()->route('admin.pozvanky.index')->with('success', $message);
    }

    public function resend(Pozvanka $pozvanka)
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

    public function destroy(Pozvanka $pozvanka)
    {
        $pozvanka->update(['stav' => 'zrusena']);
        return back()->with('success', 'Pozvánka zrušena.');
    }
}
