<?php

/**
 * Deploy hook — post-deploy operace (cache clear, migrace, seedery).
 * Volá se z GitHub Actions po dokončení FTP deploye nebo ručně z prohlížeče.
 *
 * Struktura serveru:
 *   /tuptudu.cz/rajon/       — Laravel app
 *   /tuptudu.cz/_sub/rajon/  — public (tento soubor)
 *
 * URL parametry:
 *   ?token=...           — povinné, MIGRATE_TOKEN z .env
 *   &migrate             — spustí migrate --force
 *   &seed=Name1,Name2    — spustí db:seed --class=NameN
 */

$appDir = file_exists(__DIR__ . '/../../rajon/artisan')
    ? realpath(__DIR__ . '/../../rajon')
    : realpath(dirname(__DIR__));

$token = $_GET['token'] ?? '';
$expectedToken = '';

$envFile = $appDir . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/^MIGRATE_TOKEN=(.+)$/m', $envContent, $matches)) {
        $expectedToken = trim($matches[1]);
    }
}

if (empty($token) || $token !== $expectedToken) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
$results = [];

// OPcache reset
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results[] = '✓ OPcache cleared';
}

// Laravel bootstrap přímo v PHP-FPM (žádný shell_exec — Webglobe nemá `php` v $PATH)
require_once $appDir . '/vendor/autoload.php';
$app = require_once $appDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$runArtisan = function (string $command, array $params = []) use ($app) {
    $output = new Symfony\Component\Console\Output\BufferedOutput(
        Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL,
        false, // no decoration (text/plain)
    );
    $exitCode = Illuminate\Support\Facades\Artisan::call($command, $params, $output);
    $log = trim($output->fetch());
    $marker = $exitCode === 0 ? '✓' : '✗';
    return "{$marker} {$command} (exit={$exitCode})\n" . ($log !== '' ? $log . "\n" : '');
};

$results[] = $runArtisan('cache:clear');
$results[] = $runArtisan('config:clear');
$results[] = $runArtisan('route:clear');
$results[] = $runArtisan('view:clear');

if (isset($_GET['migrate'])) {
    $results[] = $runArtisan('migrate', ['--force' => true]);
}

if (isset($_GET['seed'])) {
    $seeders = (string) $_GET['seed'];
    foreach (explode(',', $seeders) as $seeder) {
        $seeder = trim($seeder);
        if ($seeder === '') continue;
        $results[] = $runArtisan('db:seed', ['--class' => $seeder, '--force' => true]);
    }
}

echo implode("\n", $results);
