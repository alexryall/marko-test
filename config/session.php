<?php

declare(strict_types=1);

return [
    'driver' => 'file',
    'lifetime' => 120,
    'expire_on_close' => false,
    'path' => dirname(__DIR__) . '/storage/sessions',
    'cookie' => [
        'name' => 'marko_session',
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'gc_probability' => 2,
    'gc_divisor' => 100,
];
