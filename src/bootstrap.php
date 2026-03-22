<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ShopRepository.php';
require_once __DIR__ . '/AuthRepository.php';

use App\AuthRepository;
use App\Database;
use App\ShopRepository;

loadEnvironment(__DIR__ . '/../.env');
startSessionIfNeeded();

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

function authRepository(): AuthRepository
{
    static $repository = null;

    if ($repository instanceof AuthRepository) {
        return $repository;
    }

    $config = require __DIR__ . '/../config/database.php';
    $database = new Database($config);
    $repository = new AuthRepository($database->pdo());

    return $repository;
}

function currentUser(): ?array
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId) && !ctype_digit((string) $userId)) {
        return null;
    }

    return authRepository()->userById((int) $userId);
}

function loginUser(array $user): void
{
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['full_name'] = (string) $user['full_name'];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (session_id() !== '' || session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function requirePageAuth(array $roles = []): array
{
    $user = currentUser();

    if ($user === null) {
        header('Location: /index.php');
        exit;
    }

    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
        header('Location: ' . ($user['role'] === 'admin' ? '/admin.php' : '/user.php'));
        exit;
    }

    return $user;
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

function startSessionIfNeeded(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
