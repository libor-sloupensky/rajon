<?php

/**
 * Veřejný endpoint pro AI — poslední scraping_log záznamy s chybami.
 * /scraping-log.php?token=MIGRATE_TOKEN              → poslední 5 logů
 * /scraping-log.php?token=...&id=N                   → konkrétní log
 */

$appDir = file_exists(__DIR__ . '/../../rajon/artisan')
    ? realpath(__DIR__ . '/../../rajon')
    : realpath(dirname(__DIR__));

$token = $_GET['token'] ?? '';
$expectedToken = '';
$envFile = $appDir . '/.env';
if (file_exists($envFile)) {
    if (preg_match('/^MIGRATE_TOKEN=(.+)$/m', file_get_contents($envFile), $m)) {
        $expectedToken = trim($m[1]);
    }
}

if ($token !== $expectedToken) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

require $appDir . '/vendor/autoload.php';
$app = require $appDir . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($id) {
    $log = \App\Models\ScrapingLog::with('zdroj')->find($id);
    if (!$log) { echo "Log #$id not found\n"; exit; }

    echo "=== Scraping log #{$log->id} ===\n";
    echo "Zdroj:     {$log->zdroj?->nazev}\n";
    echo "Stav:      {$log->stav}\n";
    echo "Limit:     {$log->limit_pouzity}\n";
    echo "Začátek:   {$log->zacatek}\n";
    echo "Konec:     {$log->konec}\n";
    echo "Nalezeno:  {$log->pocet_nalezenych}\n";
    echo "Nové:      {$log->pocet_novych}\n";
    echo "Aktual.:   {$log->pocet_aktualizovanych}\n";
    echo "Skip:      {$log->pocet_preskocenych}\n";
    echo "Chyby:     {$log->pocet_chyb}\n";
    echo "\n=== Statistiky ===\n";
    echo json_encode($log->statistiky, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "\n=== Chyby detail ===\n";
    echo $log->chyby_detail ?? '(žádné)';
    exit;
}

// Seznam posledních
$logy = \App\Models\ScrapingLog::with('zdroj')
    ->orderByDesc('id')
    ->take(10)
    ->get();

echo "=== Posledních " . $logy->count() . " scraping_log záznamů ===\n\n";
foreach ($logy as $log) {
    echo sprintf(
        "#%d  %s  %-15s  found=%d new=%d upd=%d skip=%d ERR=%d  [%s]\n",
        $log->id,
        $log->zacatek?->format('Y-m-d H:i'),
        $log->zdroj?->nazev ?? '?',
        $log->pocet_nalezenych,
        $log->pocet_novych,
        $log->pocet_aktualizovanych,
        $log->pocet_preskocenych,
        $log->pocet_chyb,
        $log->stav,
    );
}

echo "\nUsage:\n";
echo "  ?id=N    detail jednoho logu (včetně chyby_detail)\n";
