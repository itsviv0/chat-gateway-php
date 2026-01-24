<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Ramsey\Uuid\Uuid;

class GroupRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(string $groupId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT uuid, name, description, is_private, created_by, created_at FROM groups WHERE uuid = :uuid'
        );
        $stmt->execute(['uuid' => $groupId]);
        $group = $stmt->fetch();

        return $group !== false ? $group : null;
    }

    public function create(
        string $name,
        ?string $description,
        bool $isPrivate,
        string $createdByUuid,
        string $createdAt
    ): string {
        $uuid = Uuid::uuid4()->toString();
        $stmt = $this->pdo->prepare(
            'INSERT INTO groups (uuid, name, description, is_private, created_by, created_at) ' .
            'VALUES (:uuid, :name, :description, :is_private, :created_by, :created_at)'
        );
        $stmt->execute([
            'uuid' => $uuid,
            'name' => $name,
            'description' => $description,
            'is_private' => $isPrivate ? 1 : 0,
            'created_by' => $createdByUuid,
            'created_at' => $createdAt,
        ]);

        return $uuid;
    }

    /**
     * Get all groups for a user (public groups + groups user is member of)
     * @return array<int,array<string,mixed>>
     */
    public function findGroupsForUser(string $userUuid): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT g.uuid, g.name, g.description, g.is_private, g.created_by, g.created_at ' .
            'FROM groups g ' .
            'LEFT JOIN group_members gm ON g.uuid = gm.group_id AND gm.user_id = :user_uuid ' .
            'WHERE g.is_private = 0 OR gm.user_id = :user_uuid ' .
            'ORDER BY g.created_at DESC'
        );
        $stmt->execute(['user_uuid' => $userUuid]);

        return $stmt->fetchAll();
    }

    /**
     * Get members of a group
     * @return array<int,array<string,mixed>>
     */
    public function getGroupMembers(string $groupId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.uuid, u.username, u.email, gm.role, gm.joined_at ' .
            'FROM group_members gm ' .
            'JOIN users u ON gm.user_id = u.uuid ' .
            'WHERE gm.group_id = :group_id ' .
            'ORDER BY gm.joined_at ASC'
        );
        $stmt->execute(['group_id' => $groupId]);

        return $stmt->fetchAll();
    }
}
