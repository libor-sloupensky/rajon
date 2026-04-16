<?php

/**
 * Jednorázový migrační skript.
 * Spustit: http://rajon.tuptudu.cz/migrate.php?token=TOKEN
 * Po úspěšné migraci SMAZAT!
 */

$token = $_GET['token'] ?? '';
if ($token !== 'ARb1jyk9PdAE06mxnTAaL6CHEzCBlgF4wTzesltW') {
    http_response_code(403);
    die('Forbidden');
}

// Najdi app path
$appPath = file_exists(__DIR__ . '/../../rajon/bootstrap/app.php')
    ? __DIR__ . '/../../rajon'
    : dirname(__DIR__);

require $appPath . '/vendor/autoload.php';
$app = require_once $appPath . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

echo "=== Rajón Migration ===\n\n";

try {
    // Migrace
    echo "Running migrations...\n";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();

    // Seed
    echo "\nRunning seeder...\n";
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();

    // Cache clear
    echo "\nClearing cache...\n";
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "Done.\n";

    // Vypnout debug
    echo "\nDisabling debug mode...\n";
    $envFile = $appPath . '/.env';
    $env = file_get_contents($envFile);
    $env = str_replace('APP_DEBUG=true', 'APP_DEBUG=false', $env);
    file_put_contents($envFile, $env);
    echo "APP_DEBUG=false\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "\n=== Done ===\n";
echo "\n⚠️  SMAŽ TENTO SOUBOR po migraci!\n";
