<?php

declare(strict_types=1);

return [
    'driver' => getenv('DB_CONNECTION') ?: 'sqlite',
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'ae_accounts',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'sqlite_path' => __DIR__ . '/../storage/data.sqlite',
];
