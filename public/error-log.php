<?php

/**
 * Veřejný read-only endpoint pro error logy s token autorizací.
 * Účel: AI asistence — Claude Code si může načíst logy přes curl.
 *
 * URL:
 *   /error-log.php?token=MIGRATE_TOKEN              → seznam log souborů
 *   /error-log.php?token=...&soubor=laravel-X.log   → tail 500 kB
 *   /error-log.php?token=...&soubor=...&bytes=N     → tail N bajtů
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
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

$logsDir = $appDir . '/storage/logs';
if (!is_dir($logsDir)) {
    echo "No logs directory.\n";
    exit;
}

$soubor = $_GET['soubor'] ?? '';

// Validace cesty
if ($soubor !== '' && !preg_match('/^[\w\-\.]+\.log$/i', $soubor)) {
    http_response_code(400);
    echo "Invalid filename.\n";
    exit;
}

// Bez parametru soubor → seznam
if ($soubor === '') {
    echo "=== Log files ===\n\n";
    $files = glob($logsDir . '/*.log') ?: [];
    rsort($files);
    foreach ($files as $f) {
        echo sprintf(
            "%s   %10s   %s\n",
            basename($f),
            number_format(filesize($f), 0, '.', ','),
            date('Y-m-d H:i:s', filemtime($f))
        );
    }
    echo "\nUsage:\n";
    echo "  ?soubor=laravel-YYYY-MM-DD.log\n";
    echo "  ?soubor=...&bytes=500000\n";
    exit;
}

$cesta = $logsDir . '/' . $soubor;
if (!file_exists($cesta)) {
    http_response_code(404);
    echo "File not found: {$soubor}\n";
    exit;
}

$velikost = filesize($cesta);
$bytes = isset($_GET['bytes']) ? max(1, (int) $_GET['bytes']) : 500_000;
$bytes = min($bytes, $velikost);

$f = fopen($cesta, 'rb');
if (!$f) {
    http_response_code(500);
    echo "Cannot read file.\n";
    exit;
}

if ($velikost > $bytes) {
    fseek($f, $velikost - $bytes);
    fgets($f);  // dropnout useknutý fragment
    echo "=== TAIL " . number_format($bytes / 1024, 0) . " KB OF {$soubor} ({$velikost} B total) ===\n\n";
}
echo stream_get_contents($f);
fclose($f);
