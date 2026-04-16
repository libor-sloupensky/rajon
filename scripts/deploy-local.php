<?php

/**
 * Lokální deploy skript pro Rajón.
 * Nahraje soubory přes FTP (curl) z lokálního PC.
 *
 * Použití:
 *   php scripts/deploy-local.php          — smart deploy (jen změněné soubory)
 *   php scripts/deploy-local.php full     — kompletní deploy
 */

$appDir = '/tuptudu.cz/rajon';
$publicDir = '/tuptudu.cz/_sub/rajon';
$ftpHost = 'ftp.tuptudu.cz';
$ftpUser = 'multi_833363';
$ftpPass = 'BN5mHsYo';
$deployHookUrl = 'https://rajon.tuptudu.cz/deploy-hook.php?token=ARb1jyk9PdAE06mxnTAaL6CHEzCBlgF4wTzesltW&migrate';

$projectRoot = realpath(dirname(__DIR__));
$isFull = in_array('full', $argv ?? []);
$ftpBase = "ftp://{$ftpUser}:{$ftpPass}@{$ftpHost}";

echo "=== Rajón Deploy ===\n\n";

$uploaded = 0;
$failed = 0;

function ftpUpload(string $localFile, string $remotePath): bool
{
    global $ftpBase, $uploaded, $failed;

    // Vytvořit adresář
    $remoteDir = dirname($remotePath);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "{$GLOBALS['ftpBase']}/",
        CURLOPT_FTP_CREATE_MISSING_DIRS => true,
        CURLOPT_QUOTE => ["MKD $remoteDir"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);

    // Upload souboru
    $fp = fopen($localFile, 'r');
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "{$GLOBALS['ftpBase']}{$remotePath}",
        CURLOPT_UPLOAD => true,
        CURLOPT_INFILE => $fp,
        CURLOPT_INFILESIZE => filesize($localFile),
        CURLOPT_FTP_CREATE_MISSING_DIRS => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ]);
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if ($result !== false && empty($error)) {
        $uploaded++;
        return true;
    } else {
        $failed++;
        return false;
    }
}

function uploadDirectory(string $localDir, string $remoteDir, array $exclude = []): void
{
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($localDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($items as $item) {
        if ($item->isDir()) continue;

        $relativePath = substr($item->getPathname(), strlen($localDir) + 1);
        $relativePath = str_replace('\\', '/', $relativePath);

        foreach ($exclude as $ex) {
            if (str_starts_with($relativePath, $ex)) continue 2;
        }

        $remotePath = $remoteDir . '/' . $relativePath;

        if (ftpUpload($item->getPathname(), $remotePath)) {
            echo "  + $relativePath\n";
        } else {
            echo "  X FAILED: $relativePath\n";
        }
    }
}

if ($isFull) {
    echo "[1/3] FULL DEPLOY — app soubory → {$appDir}\n";
    $exclude = ['public', 'node_modules', '.git', '.claude', 'storage/logs', 'storage/framework/sessions', 'scripts', 'tests'];
    uploadDirectory($projectRoot, $appDir, $exclude);
    echo "\n";

    echo "[2/3] Public soubory → {$publicDir}\n";
    uploadDirectory($projectRoot . DIRECTORY_SEPARATOR . 'public', $publicDir);
    echo "\n";
} else {
    echo "[1/3] SMART DEPLOY — změněné soubory\n";
    $changedFiles = trim(shell_exec('cd ' . escapeshellarg($projectRoot) . ' && git diff --name-only HEAD~1 HEAD 2>&1'));

    if (empty($changedFiles)) {
        echo "  Žádné změny.\n";
    } else {
        // Vždy nahrát .env
        $envFile = $projectRoot . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($envFile)) {
            ftpUpload($envFile, $appDir . '/.env');
            echo "  + .env\n";
        }

        foreach (explode("\n", $changedFiles) as $file) {
            $file = trim($file);
            if (empty($file)) continue;

            $localPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);

            if (str_starts_with($file, 'public/')) {
                $remotePath = $publicDir . '/' . substr($file, 7);
            } else {
                $remotePath = $appDir . '/' . $file;
            }

            if (!file_exists($localPath)) {
                echo "  - Smazáno: $file\n";
                continue;
            }

            if (ftpUpload($localPath, $remotePath)) {
                echo "  + $file\n";
            } else {
                echo "  X FAILED: $file\n";
            }
        }
    }
    echo "\n";

    echo "[2/3] Autoloader\n";
    $autoload = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    if (file_exists($autoload)) {
        ftpUpload($autoload, $appDir . '/vendor/autoload.php');
        echo "  + vendor/autoload.php\n";
    }
    echo "\n";
}

echo "[3/3] Post-deploy hook...\n";
$result = @file_get_contents($deployHookUrl);
echo "  " . ($result ?: 'Hook nedostupný (doména nemusí být nastavená)') . "\n\n";

echo "=== Deploy: {$uploaded} nahráno, {$failed} selhalo ===\n";
