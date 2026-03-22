<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ShopRepository.php';

use App\Database;
use App\ShopRepository;

loadEnvironment(__DIR__ . '/../.env');

function repository(): ShopRepository
{
    static $repository = null;

    if ($repository instanceof ShopRepository) {
        return $repository;
    }

    $config = require __DIR__ . '/../config/database.php';

    $database = new Database($config);
    $repository = new ShopRepository($database->pdo());

    return $repository;
}

function loadEnvironment(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        putenv(sprintf('%s=%s', $key, $value));
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
