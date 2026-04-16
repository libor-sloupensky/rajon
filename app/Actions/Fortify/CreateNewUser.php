<?php

namespace App\Actions\Fortify;

use App\Models\Uzivatel;
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
        Validator::make($input, [
            'jmeno' => ['required', 'string', 'max:255'],
            'prijmeni' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('uzivatele', 'email')],
            'telefon' => ['nullable', 'string', 'max:20'],
            'password' => $this->passwordRules(),
        ])->validate();

        return Uzivatel::create([
            'jmeno' => $input['jmeno'],
            'prijmeni' => $input['prijmeni'],
            'email' => $input['email'],
            'telefon' => $input['telefon'] ?? null,
            'heslo' => Hash::make($input['password']),
            'role' => 'fransizan',
        ]);
    }
}
