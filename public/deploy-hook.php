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
header('X-Accel-Buffering: no'); // nginx: vypnout buffering pro real-time stream
@set_time_limit(300);
@ini_set('memory_limit', '512M');
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
while (ob_get_level() > 0) { ob_end_flush(); }
@ob_implicit_flush(true);

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

// Streamovaný výstup — klient vidí progress okamžitě, nginx/proxy neaplikuje timeout
$emit = function (string $line): void {
    echo $line . "\n";
    @flush();
};

$emit('… start, memory_limit=' . ini_get('memory_limit')
    . ', time_limit=' . ini_get('max_execution_time') . 's');

try {
    // OPcache reset
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $emit('✓ OPcache cleared');
    }

    // Laravel bootstrap přímo v PHP-FPM (žádný shell_exec — Webglobe nemá `php` v $PATH)
    require_once $appDir . '/vendor/autoload.php';
    $app = require_once $appDir . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $runArtisan = function (string $command, array $params = []) use ($emit) {
        $emit("→ {$command} starting…");
        // StreamOutput píše rovnou do stdout — uvidíme progress real-time
        $stream = fopen('php://output', 'w');
        $output = new Symfony\Component\Console\Output\StreamOutput($stream);
        try {
            $exitCode = Illuminate\Support\Facades\Artisan::call($command, $params, $output);
            @flush();
            return "✓ {$command} (exit={$exitCode}) [" . round(memory_get_peak_usage(true) / 1048576, 1) . " MB]";
        } catch (\Throwable $e) {
            return "✗ {$command} EXCEPTION: " . get_class($e) . ': ' . $e->getMessage()
                . "\n  at " . $e->getFile() . ':' . $e->getLine();
        }
    };

    $emit($runArtisan('cache:clear'));
    $emit($runArtisan('config:clear'));
    $emit($runArtisan('route:clear'));
    $emit($runArtisan('view:clear'));

    if (isset($_GET['migrate'])) {
        $emit($runArtisan('migrate', ['--force' => true]));
    }

    if (isset($_GET['seed'])) {
        $seeders = (string) $_GET['seed'];
        foreach (explode(',', $seeders) as $seeder) {
            $seeder = trim($seeder);
            if ($seeder === '') continue;
            $emit($runArtisan('db:seed', ['--class' => $seeder, '--force' => true]));
        }
    }
    $emit('… done in ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2) . 's');
} catch (\Throwable $e) {
    $emit('✗ FATAL: ' . get_class($e) . ': ' . $e->getMessage()
        . "\n  at " . $e->getFile() . ':' . $e->getLine()
        . "\n\nTrace:\n" . $e->getTraceAsString());
}
