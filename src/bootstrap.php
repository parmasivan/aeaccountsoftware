<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ShopRepository.php';

use App\Database;
use App\ShopRepository;

function repository(): ShopRepository
{
    static $repository = null;

    if ($repository instanceof ShopRepository) {
        return $repository;
    }

    $database = new Database(__DIR__ . '/../storage/data.sqlite');
    $repository = new ShopRepository($database->pdo());

    return $repository;
}
