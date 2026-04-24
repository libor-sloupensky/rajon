<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\Pozvanka;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Vlastní registrace — jen na pozvánku.
 * Fortify registration feature je vypnutý (viz config/fortify.php).
 */
class RegistraceController extends Controller
{
    /** GET /registrace?token=XXX */
    public function zobrazit(Request $request)
    {
        $pozvanka = null;

        if ($request->filled('token')) {
            $pozvanka = Pozvanka::where('token', $request->token)
                ->where('stav', 'cekajici')
                ->first();

            if ($pozvanka && !$pozvanka->jePlatna()) {
                $pozvanka = null;
            }
        }

        // Bez platné pozvánky ⇒ redirect na login s hláškou
        if (!$pozvanka) {
            return redirect()->route('login')->with('error',
                'Registrace je možná pouze na základě platné pozvánky. Kontaktujte administrátora.');
        }

        return view('auth.register', compact('pozvanka'));
    }

    /** POST /registrace */
    public function registrovat(Request $request, CreateNewUser $creator): RedirectResponse
    {
        $uzivatel = $creator->create($request->all());
        Auth::login($uzivatel);
        return redirect('/dashboard')->with('success', 'Vítejte v Rajónu!');
    }
}
