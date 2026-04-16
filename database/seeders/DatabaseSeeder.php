<?php

namespace Database\Seeders;

use App\Models\Uzivatel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin uživatel
        Uzivatel::firstOrCreate(
            ['email' => 'libor@tuptudu.cz'],
            [
                'jmeno' => 'Libor',
                'prijmeni' => 'Sloupenský',
                'heslo' => Hash::make('heslo123'),
                'role' => 'admin',
                'email_overen_v' => now(),
            ]
        );

        // Testovací franšízant
        Uzivatel::firstOrCreate(
            ['email' => 'test@wormup.com'],
            [
                'jmeno' => 'Test',
                'prijmeni' => 'Franšízant',
                'heslo' => Hash::make('heslo123'),
                'role' => 'fransizan',
                'email_overen_v' => now(),
                'region' => 'Jihomoravský kraj',
            ]
        );
    }
}
