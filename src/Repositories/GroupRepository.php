<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class GroupRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $groupId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, description, is_private, created_by, created_at FROM groups WHERE id = :id'
        );
        $stmt->execute(['id' => $groupId]);
        $group = $stmt->fetch();

        return $group !== false ? $group : null;
    }

    public function create(string $name, ?string $description, bool $isPrivate, int $createdBy, string $createdAt): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO groups (name, description, is_private, created_by, created_at) ' .
            'VALUES (:name, :description, :is_private, :created_by, :created_at)'
        );
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'is_private' => $isPrivate ? 1 : 0,
            'created_by' => $createdBy,
            'created_at' => $createdAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
