<?php

/**
 * Deploy extractor — rozbalí nahraný archiv na serveru.
 *
 * Nahrazuje pomalý per-soubor FTP přenos: GitHub Actions nahraje JEDEN .tar.gz
 * (jedno TCP spojení = rychlé a minimální expozice firewallu Webglobe) a tento
 * endpoint ho rozbalí přímo na serveru přes PharData.
 *
 * Struktura serveru:
 *   /tuptudu.cz/rajon/          — Laravel app (cíl pro target=app)
 *   /tuptudu.cz/_sub/rajon/     — public (cíl pro target=public, zde leží tento soubor)
 *   /tuptudu.cz/rajon/_deploy/  — sem se nahrávají archivy a delete-listy
 *
 * URL parametry:
 *   ?token=...        — povinné, MIGRATE_TOKEN z .env
 *   &target=app|public
 *
 * Vstupní soubory v _deploy/:
 *   app.tar.gz / public.tar.gz   — archiv ke rozbalení
 *   delete-app.txt / delete-public.txt — volitelně, relativní cesty ke smazání (1/řádek)
 *
 * Po úspěšném rozbalení archiv i delete-list smaže.
 */

$publicDir = __DIR__;
$appDir = file_exists(__DIR__ . '/../../rajon/.env')
    ? realpath(__DIR__ . '/../../rajon')
    : realpath(dirname(__DIR__));

$token = $_GET['token'] ?? '';
$expectedToken = '';
$envFile = $appDir . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/^MIGRATE_TOKEN=(.+)$/m', $envContent, $m)) {
        $expectedToken = trim($m[1]);
    }
}
if (empty($token) || empty($expectedToken) || $token !== $expectedToken) {
    http_response_code(403);
    die('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(280);
@ini_set('memory_limit', '512M');

$target = $_GET['target'] ?? '';
if (!in_array($target, ['app', 'public'], true)) {
    http_response_code(400);
    die("Chybný target (app|public)\n");
}

$destDir = $target === 'public' ? $publicDir : $appDir;
$deployDir = $appDir . '/_deploy';
$archive = $deployDir . "/{$target}.tar.gz";
$deleteList = $deployDir . "/delete-{$target}.txt";

echo "=== extract target={$target} ===\n";
echo "dest: {$destDir}\n";

$start = microtime(true);

// 1) Smazat soubory uvedené v delete-listu (smart deploy odstranil soubory v gitu)
if (file_exists($deleteList)) {
    $lines = file($deleteList, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $smazano = 0;
    foreach ($lines as $rel) {
        $rel = trim($rel);
        if ($rel === '' || str_contains($rel, '..')) continue; // ochrana proti path traversal
        $path = $destDir . '/' . ltrim($rel, '/');
        if (is_file($path) && @unlink($path)) {
            $smazano++;
            echo "  - smazáno: {$rel}\n";
        }
    }
    echo "Smazáno souborů: {$smazano}\n";
    @unlink($deleteList);
}

// 2) Rozbalit archiv (pokud existuje — delete-only deploy ho mít nemusí)
if (file_exists($archive)) {
    try {
        $phar = new PharData($archive);
        $pocet = $phar->count();
        $phar->extractTo($destDir, null, true); // přepsat existující
        unset($phar);
        echo "Rozbaleno souborů: {$pocet}\n";
        @unlink($archive);
        // PharData drží zámek; uvolnit z cache
        if (class_exists('Phar')) {
            @Phar::unlinkArchive($archive);
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        echo "✗ CHYBA rozbalení: " . get_class($e) . ': ' . $e->getMessage() . "\n";
        exit;
    }
} else {
    echo "Archiv {$target}.tar.gz neexistuje — jen delete fáze.\n";
}

echo "=== hotovo za " . round(microtime(true) - $start, 2) . "s ===\n";
