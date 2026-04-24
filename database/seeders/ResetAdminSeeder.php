<?php

namespace Database\Seeders;

use App\Models\Uzivatel;
use Illuminate\Database\Seeder;

/**
 * Jednorázový seeder — přepíše e-mail admin účtu na nový.
 * Heslo zůstává beze změny (uživatel se přihlašuje přes Google OAuth).
 *
 * Spuštění přes deploy-hook:
 *   ?token=...&seed=ResetAdminSeeder
 */
class ResetAdminSeeder extends Seeder
{
    private const NOVY_EMAIL = 'libor.sloupensky@seznam.cz';

    public function run(): void
    {
        $admin = Uzivatel::where('role', 'admin')->orderBy('id')->first();

        if (!$admin) {
            $this->command->error('Admin účet nenalezen — nic se nemění.');
            return;
        }

        $stary = $admin->email;
        if ($stary === self::NOVY_EMAIL) {
            $this->command->info("E-mail admina je už {$stary}, nic se nemění.");
            return;
        }

        $admin->email = self::NOVY_EMAIL;
        $admin->email_overen_v = now();
        $admin->save();

        $this->command->line('');
        $this->command->line('=== ADMIN E-MAIL ZMĚNĚN ===');
        $this->command->line("Starý : {$stary}");
        $this->command->line("Nový  : {$admin->email}");
        $this->command->line('Přihlášení: Google OAuth s účtem libor.sloupensky@seznam.cz');
        $this->command->line('===========================');
    }
}
