<?php

/**
 * Cron endpoint — pravidelný scraping všech aktivních zdrojů.
 *
 * URL:
 *   https://rajon.tuptudu.cz/cron.php?token=CRON_TOKEN
 *
 * Volitelné parametry:
 *   &zdroj=N       — jen jeden zdroj
 *   &limit=N       — max URL per zdroj (default 50)
 *   &dry           — jen vrátit plán, nespustit scraping
 *
 * Doporučená frekvence cronu: jednou za hodinu nebo dvě.
 *   0 *   * * *  curl -s "https://rajon.tuptudu.cz/cron.php?token=..."
 *
 * Token se ověřuje proti CRON_TOKEN v .env.
 */

$appDir = file_exists(__DIR__ . '/../../rajon/artisan')
    ? realpath(__DIR__ . '/../../rajon')
    : realpath(dirname(__DIR__));

$token = $_GET['token'] ?? '';
$expectedToken = '';
$envFile = $appDir . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/^CRON_TOKEN=(.+)$/m', $envContent, $matches)) {
        $expectedToken = trim($matches[1]);
    }
}

if (empty($token) || empty($expectedToken) || $token !== $expectedToken) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');
@set_time_limit(280);   // pod nginx 300s timeout
@ini_set('memory_limit', '512M');
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
while (ob_get_level() > 0) { ob_end_flush(); }
@ob_implicit_flush(true);

$emit = function (string $line): void {
    echo $line . "\n";
    @flush();
};

$emit('… cron start ' . date('Y-m-d H:i:s'));

require_once $appDir . '/vendor/autoload.php';
$app = require_once $appDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $zdrojId = isset($_GET['zdroj']) ? (int) $_GET['zdroj'] : null;
    $limit = max(10, min(200, (int) ($_GET['limit'] ?? 50)));
    $dry = isset($_GET['dry']);

    $zdroje = \App\Models\Zdroj::query()
        ->where('stav', 'aktivni')
        ->when($zdrojId, fn ($q) => $q->where('id', $zdrojId))
        ->get();

    if ($zdroje->isEmpty()) {
        $emit('Žádné aktivní zdroje k scrapingu');
        exit;
    }

    $emit("Najdeno {$zdroje->count()} zdrojů, limit {$limit} URL na zdroj");

    if ($dry) {
        foreach ($zdroje as $z) {
            $emit("  [dry] Zdroj {$z->id}: {$z->nazev} (poslední: {$z->posledni_scraping})");
        }
        exit;
    }

    $pipeline = app(\App\Services\Scraping\ScrapingPipeline::class);
    foreach ($zdroje as $z) {
        // Skip zdroje, jejichž frekvence_hodin ještě neuplynula
        if ($z->posledni_scraping && $z->frekvence_hodin) {
            $hoursAgo = now()->diffInHours($z->posledni_scraping, false);
            if (abs($hoursAgo) < $z->frekvence_hodin) {
                $emit("  Skip [{$z->id}] {$z->nazev} (poslední před " . abs($hoursAgo) . " h, frekvence {$z->frekvence_hodin} h)");
                continue;
            }
        }

        $emit("  → [{$z->id}] {$z->nazev}…");
        $start = microtime(true);
        try {
            $log = $pipeline->scrapujZdroj($z, $limit);
            $cas = round(microtime(true) - $start, 1);
            $emit("    ✓ stav={$log->stav} nove={$log->pocet_novych} aktualizace={$log->pocet_aktualizovanych}"
                . " preskoceno={$log->pocet_preskocenych} chyby={$log->pocet_chyb} ({$cas}s)");
        } catch (\Throwable $e) {
            $emit("    ✗ EXCEPTION: " . get_class($e) . ': ' . $e->getMessage());
        }
    }

    $emit('… cron done ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 1) . 's, mem '
        . round(memory_get_peak_usage(true) / 1048576, 1) . ' MB');
} catch (\Throwable $e) {
    $emit('✗ FATAL: ' . get_class($e) . ': ' . $e->getMessage()
        . "\n  at " . $e->getFile() . ':' . $e->getLine());
}
