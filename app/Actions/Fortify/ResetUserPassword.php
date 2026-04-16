<?php

namespace App\Actions\Fortify;

use App\Models\Uzivatel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * @throws ValidationException
     */
    public function reset(Uzivatel $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'heslo' => Hash::make($input['password']),
        ])->save();
    }
}
