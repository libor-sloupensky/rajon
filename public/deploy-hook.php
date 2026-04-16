<?php

/**
 * Deploy hook — post-deploy operace (cache clear, migrace).
 * Volá se z GitHub Actions po dokončení FTP deploye.
 */

$token = $_GET['token'] ?? '';
$expectedToken = trim(file_get_contents(__DIR__ . '/../.env') ? '' : '');

// Načti MIGRATE_TOKEN z .env
$envContent = file_get_contents(__DIR__ . '/../.env');
if (preg_match('/^MIGRATE_TOKEN=(.+)$/m', $envContent, $matches)) {
    $expectedToken = trim($matches[1]);
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

// Artisan cache:clear
$results[] = shell_exec('cd ' . escapeshellarg(dirname(__DIR__)) . ' && php artisan cache:clear 2>&1');
$results[] = shell_exec('cd ' . escapeshellarg(dirname(__DIR__)) . ' && php artisan config:clear 2>&1');
$results[] = shell_exec('cd ' . escapeshellarg(dirname(__DIR__)) . ' && php artisan route:clear 2>&1');
$results[] = shell_exec('cd ' . escapeshellarg(dirname(__DIR__)) . ' && php artisan view:clear 2>&1');

// Migrace
if (isset($_GET['migrate'])) {
    $results[] = shell_exec('cd ' . escapeshellarg(dirname(__DIR__)) . ' && php artisan migrate --force 2>&1');
}

header('Content-Type: text/plain');
echo implode("\n", array_filter($results));
