<?php

namespace App\Actions\Fortify;

use App\Models\Pozvanka;
use App\Models\Uzivatel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * @throws ValidationException
     */
    public function create(array $input): Uzivatel
    {
        // Registrace jen s platným tokenem pozvánky
        $pozvanka = null;
        if (!empty($input['token'])) {
            $pozvanka = Pozvanka::where('token', $input['token'])
                ->where('stav', 'cekajici')
                ->first();
        }

        if (!$pozvanka || !$pozvanka->jePlatna()) {
            throw ValidationException::withMessages([
                'token' => 'Registrace je možná pouze na základě platné pozvánky. Kontaktujte administrátora.',
            ]);
        }

        Validator::make($input, [
            'jmeno' => ['required', 'string', 'max:255'],
            'prijmeni' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('uzivatele', 'email')],
            'telefon' => ['nullable', 'string', 'max:20'],
            'mesto' => ['required', 'string', 'max:100'],
            'psc' => ['nullable', 'string', 'max:10'],
            'password' => $this->passwordRules(),
        ])->validate();

        // Geokódovat adresu (město + PSČ) přes Mapy.cz — vyžadováno
        $geokoder = app(\App\Services\Geokoder::class);
        $gps = $geokoder->geokodujAdresuUzivatele($input['mesto'], $input['psc'] ?? null);

        if (!$gps) {
            throw ValidationException::withMessages([
                'mesto' => 'Tuto obec nelze najít. Zkuste přidat PSČ pro upřesnění (např. obce se stejným názvem).',
            ]);
        }

        return DB::transaction(function () use ($input, $pozvanka, $gps) {
            $uzivatel = Uzivatel::create([
                'jmeno' => $input['jmeno'],
                'prijmeni' => $input['prijmeni'],
                'email' => $input['email'],
                'telefon' => $input['telefon'] ?? null,
                'heslo' => Hash::make($input['password']),
                'role' => $pozvanka->role ?: 'fransizan',
                'region' => $pozvanka->region,
                'mesto' => $input['mesto'],
                'psc' => $input['psc'] ?? null,
                'gps_lat' => $gps['gps_lat'],
                'gps_lng' => $gps['gps_lng'],
                'email_overen_v' => now(),  // z pozvánky = e-mail ověřen
            ]);

            $pozvanka->update([
                'stav' => 'prijata',
                'prijata_v' => now(),
                'uzivatel_id' => $uzivatel->id,
            ]);

            return $uzivatel;
        });
    }
}
