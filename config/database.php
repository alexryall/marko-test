<?php

declare(strict_types=1);

return [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => (int) env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'marko_shop'),
    'username' => env('DB_USERNAME', 'marko'),
    'password' => env('DB_PASSWORD', 'marko'),
];
