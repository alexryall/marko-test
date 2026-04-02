<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Register app module autoloaders (not covered by Composer)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\Swoole\\';
    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = __DIR__ . '/../app/swoole/src/' . $relative . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

use App\Swoole\Server\SwooleServer;

$port = 8000;
$workers = 4;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--port=')) {
        $port = (int) substr($arg, 7);
    } elseif (str_starts_with($arg, '--workers=')) {
        $workers = (int) substr($arg, 10);
    }
}

$server = new SwooleServer(
    basePath: dirname(__DIR__),
    host: '0.0.0.0',
    port: $port,
    workers: $workers,
);

$server->start();
