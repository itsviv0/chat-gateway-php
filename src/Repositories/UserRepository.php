<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT uuid, username, email, created_at FROM users WHERE uuid = :uuid LIMIT 1'
        );
        $stmt->execute(['uuid' => $uuid]);
        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT uuid, username, email, password_hash, created_at FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT uuid, username, email, password_hash, created_at FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }
}
