<?php

/**
 * Deploy hook — post-deploy operace (cache clear, migrace).
 * Volá se z GitHub Actions po dokončení SFTP deploye.
 *
 * Struktura serveru:
 *   /tuptudu.cz/rajon/       — Laravel app
 *   /tuptudu.cz/_sub/rajon/  — public (tento soubor)
 */

// Na serveru: app je v ../../rajon/, lokálně: ../
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

$results = [];

// OPcache reset
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results[] = 'OPcache cleared';
}

$artisan = function (string $cmd) use ($appDir) {
    return shell_exec('cd ' . escapeshellarg($appDir) . ' && php artisan ' . $cmd . ' 2>&1');
};

$results[] = $artisan('cache:clear');
$results[] = $artisan('config:clear');
$results[] = $artisan('route:clear');
$results[] = $artisan('view:clear');

if (isset($_GET['migrate'])) {
    $results[] = $artisan('migrate --force');
}

if (isset($_GET['seed'])) {
    $seeders = $_GET['seed'];
    foreach (explode(',', $seeders) as $seeder) {
        $results[] = $artisan('db:seed --class=' . escapeshellarg(trim($seeder)) . ' --force');
    }
}

header('Content-Type: text/plain');
echo implode("\n", array_filter($results));
