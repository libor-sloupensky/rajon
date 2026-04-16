# Auth modul

## Stav: Připraveno

## Implementace
- **Fortify:** registrace, přihlášení, reset hesla, ověření e-mailu
- **Socialite:** Google OAuth (redirect + callback)
- **Model:** `Uzivatel` (tabulka `uzivatele`)
- **Role:** `admin`, `fransizan`
- **České sloupce:** `jmeno`, `prijmeni`, `heslo`, `email_overen_v`, `vytvoreno`, `upraveno`

## Soubory
- `app/Models/Uzivatel.php`
- `app/Providers/FortifyServiceProvider.php`
- `app/Actions/Fortify/CreateNewUser.php`
- `app/Actions/Fortify/ResetUserPassword.php`
- `app/Http/Controllers/Auth/GoogleController.php`
- `app/Http/Middleware/JeAdmin.php`
- `app/Http/Middleware/AutoLoginLocal.php`
- `app/Notifications/OvereniEmailu.php`
- `app/Notifications/ResetHesla.php`
- `resources/views/auth/*`

## Konfigurace
- `config/fortify.php` — features: registration, resetPasswords, emailVerification
- `config/auth.php` — provider: Uzivatel model
- `config/services.php` — Google OAuth credentials
