<?php
/**
 * Laravel Global Cache Reset
 * --------------------------------------
 * Taruh file ini di root project Laravel (sejajar artisan).
 * Begitu diakses (web/CLI), langsung reset semua cache.
 * 
 * WARNING:
 * File ini sangat rawan jika dibiarkan publik!
 * Jangan taruh di server production tanpa proteksi.
 */

declare(strict_types=1);

$start = microtime(true);

try {
    if (!is_file(__DIR__ . '/../artisan')) {
        throw new RuntimeException('File artisan tidak ditemukan. Pastikan skrip ini di root project Laravel.');
    }

    require __DIR__ . '/../vendor/autoload.php';
    $app = require __DIR__ . '/../bootstrap/app.php';

    /** @var \Illuminate\Contracts\Console\Kernel $kernel */
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

    $commands = [
        'optimize:clear',
        'cache:clear',
        'config:clear',
        'route:clear',
        'view:clear',
        'event:clear',
    ];

    $results = [];
    foreach ($commands as $cmd) {
        $exitCode = $kernel->call($cmd);
        $results[] = [
            'command' => $cmd,
            'exit'    => $exitCode,
            'output'  => trim($kernel->output() ?? ''),
        ];
    }

    $time = round((microtime(true) - $start) * 1000);

    if (PHP_SAPI === 'cli') {
        echo "=== Laravel Cache Reset ===\n";
        foreach ($results as $r) {
            echo "- {$r['command']} [exit={$r['exit']}]\n";
            if ($r['output'] !== '') {
                echo "  > " . str_replace("\n", "\n  > ", $r['output']) . "\n";
            }
        }
        echo "Selesai dalam {$time} ms\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'ok'       => true,
            'message'  => 'Semua cache Laravel berhasil dibersihkan',
            'duration' => "{$time} ms",
            'results'  => $results,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "ERROR: {$e->getMessage()}\n");
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode([
            'ok'    => false,
            'error' => $e->getMessage(),
        ]);
    }
}
