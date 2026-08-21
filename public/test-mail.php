<?php

/**
 * Test odchozí pošty — ověří, že aplikace dokáže odeslat e-mail.
 *
 * URL:
 *   /test-mail.php?token=MIGRATE_TOKEN&to=nekdo@example.cz
 *
 * Vypíše použitý transport a odesílatele, takže je hned vidět, jestli běží
 * php_mail (hosting blokuje odchozí SMTP) a z jaké adresy se odesílá.
 * Případnou výjimku vypíše celou — bez toho se chyba odesílání pozná těžko,
 * protože Laravel ji u obnovy hesla spolkne a uživateli ukáže hlášku o úspěchu.
 */

$appDir = realpath(dirname(__DIR__));

$token = $_GET['token'] ?? '';
$expected = '';

if (is_readable($appDir . '/.env')) {
    if (preg_match('/^MIGRATE_TOKEN=(.+)$/m', file_get_contents($appDir . '/.env'), $m)) {
        $expected = trim($m[1]);
    }
}

if ($token === '' || $expected === '' || !hash_equals($expected, $token)) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$komu = $_GET['to'] ?? '';

if (!filter_var($komu, FILTER_VALIDATE_EMAIL)) {
    exit("Zadej cílovou adresu: ?token=...&to=nekdo@example.cz\n");
}

require $appDir . '/vendor/autoload.php';
$app = require $appDir . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'Mailer:     ' . config('mail.default') . "\n";
echo 'Transport:  ' . config('mail.mailers.' . config('mail.default') . '.transport') . "\n";
echo 'Odesílatel: ' . config('mail.from.address') . ' (' . config('mail.from.name') . ")\n";
echo 'Příjemce:   ' . $komu . "\n\n";

try {
    Illuminate\Support\Facades\Mail::raw(
        'Testovací zpráva z Rajónu — ověření odchozí pošty po přesunu hostingu.',
        fn ($zprava) => $zprava->to($komu)->subject('Rajón — test odchozí pošty')
    );

    echo "Odesláno bez chyby.\n";
} catch (Throwable $e) {
    echo 'CHYBA: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
    echo '  ' . $e->getFile() . ':' . $e->getLine() . "\n";
}
