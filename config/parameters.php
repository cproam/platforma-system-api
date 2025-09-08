<?php declare(strict_types=1);

return [
    'db' => [
        // SQLite file path; adjust as needed
        'path' => dirname(__DIR__) . '/var/data.sqlite',
    ],
    'jwt' => [
        'secret' => 'dev-secret-change-me',
        'ttl' => 3600,
        'alg' => 'HS256',
    ],
];
