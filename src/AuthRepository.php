<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class AuthRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function login(string $username, string $password): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, full_name, username, password_hash, role
             FROM users
             WHERE username = :username'
        );
        $statement->execute([':username' => trim($username)]);
        $user = $statement->fetch();

        if ($user === false || !password_verify($password, (string) $user['password_hash'])) {
            throw new RuntimeException('Invalid username or password.');
        }

        return $this->sanitizeUser($user);
    }

    public function userById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, full_name, username, role
             FROM users
             WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $this->sanitizeUser($user);
    }

    private function sanitizeUser(array $user): array
    {
        unset($user['password_hash']);

        return $user;
    }
}
