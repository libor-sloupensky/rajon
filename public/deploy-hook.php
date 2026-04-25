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
@set_time_limit(300);
@ini_set('memory_limit', '512M');

// Lov fatal errorů (OOM, timeout, parse error) — bez tohoto Webglobe vrátí 500 HTML
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR], true)) {
        if (! headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo "\n\n✗ FATAL: [{$err['type']}] {$err['message']}\n  at {$err['file']}:{$err['line']}\n";
        echo 'memory: ' . round(memory_get_peak_usage(true) / 1048576, 1) . " MB / limit "
            . ini_get('memory_limit') . "\n";
        echo 'time: ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2) . "s / limit "
            . ini_get('max_execution_time') . "s\n";
    }
});

$results = [];
$results[] = '… start, memory_limit=' . ini_get('memory_limit')
    . ', time_limit=' . ini_get('max_execution_time') . 's';

try {
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

    $runArtisan = function (string $command, array $params = []) {
        $output = new Symfony\Component\Console\Output\BufferedOutput();
        try {
            $exitCode = Illuminate\Support\Facades\Artisan::call($command, $params, $output);
            $log = trim($output->fetch());
            $marker = $exitCode === 0 ? '✓' : '✗';
            return "{$marker} {$command} (exit={$exitCode})\n" . ($log !== '' ? $log . "\n" : '');
        } catch (\Throwable $e) {
            return "✗ {$command} EXCEPTION: " . get_class($e) . ': ' . $e->getMessage()
                . "\n  at " . $e->getFile() . ':' . $e->getLine() . "\n";
        }
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
} catch (\Throwable $e) {
    $results[] = '✗ FATAL: ' . get_class($e) . ': ' . $e->getMessage()
        . "\n  at " . $e->getFile() . ':' . $e->getLine()
        . "\n\nTrace:\n" . $e->getTraceAsString();
}

echo implode("\n", $results);
