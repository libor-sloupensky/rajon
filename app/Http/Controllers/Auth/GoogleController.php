<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Uzivatel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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
        $googleUser = Socialite::driver('google')->user();

        $uzivatel = Uzivatel::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $uzivatel) {
            $jmeno = $googleUser->user['given_name'] ?? '';
            $prijmeni = $googleUser->user['family_name'] ?? '';

            $uzivatel = Uzivatel::create([
                'jmeno' => $jmeno,
                'prijmeni' => $prijmeni,
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'heslo' => bcrypt(Str::random(32)),
                'email_overen_v' => now(),
                'role' => 'fransizan',
            ]);
        } else {
            if (! $uzivatel->google_id) {
                $uzivatel->update(['google_id' => $googleUser->getId()]);
            }
            if (! $uzivatel->hasVerifiedEmail()) {
                $uzivatel->markEmailAsVerified();
            }
        }

        Auth::login($uzivatel, true);

        return redirect()->intended('/dashboard');
    }
}
