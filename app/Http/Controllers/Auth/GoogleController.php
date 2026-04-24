<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pozvanka;
use App\Models\Uzivatel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Přihlášení přes Google se nezdařilo. Zkuste to prosím znovu.');
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        // 1. Existuje uživatel s tímto google_id nebo e-mailem? → přihlásit
        $uzivatel = Uzivatel::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($uzivatel) {
            if (!$uzivatel->google_id) {
                $uzivatel->update(['google_id' => $googleId]);
            }
            if (!$uzivatel->hasVerifiedEmail()) {
                $uzivatel->markEmailAsVerified();
            }
            Auth::login($uzivatel, true);
            return redirect()->intended('/dashboard');
        }

        // 2. Neznámý e-mail → hledat platnou pozvánku
        $pozvanka = Pozvanka::where('email', $email)
            ->where('stav', 'cekajici')
            ->first();

        if (!$pozvanka || !$pozvanka->jePlatna()) {
            return redirect()->route('login')->with('error',
                'Přístup do Rajónu je možný pouze na pozvánku. E-mail ' . $email . ' není mezi pozvanými.');
        }

        // 3. Platná pozvánka → vytvořit uživatele rovnou z Google údajů a pozvánku přijmout
        $jmeno = $googleUser->user['given_name'] ?? $pozvanka->jmeno ?? '';
        $prijmeni = $googleUser->user['family_name'] ?? $pozvanka->prijmeni ?? '';

        $uzivatel = DB::transaction(function () use ($googleId, $email, $jmeno, $prijmeni, $pozvanka) {
            $uzivatel = Uzivatel::create([
                'jmeno' => $jmeno,
                'prijmeni' => $prijmeni,
                'email' => $email,
                'google_id' => $googleId,
                'heslo' => bcrypt(Str::random(32)),
                'email_overen_v' => now(),
                'role' => $pozvanka->role ?: 'fransizan',
                'region' => $pozvanka->region,
            ]);

            $pozvanka->update([
                'stav' => 'prijata',
                'prijata_v' => now(),
                'uzivatel_id' => $uzivatel->id,
            ]);

            return $uzivatel;
        });

        Auth::login($uzivatel, true);
        return redirect('/dashboard')->with('success', 'Vítejte v Rajónu!');
    }
}
