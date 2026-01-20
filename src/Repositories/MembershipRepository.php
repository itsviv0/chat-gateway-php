<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class MembershipRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findMembership(int $groupId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT role FROM group_members WHERE group_id = :group_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['group_id' => $groupId, 'user_id' => $userId]);
        $membership = $stmt->fetch();

        return $membership !== false ? $membership : null;
    }

    public function isMember(int $groupId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM group_members WHERE group_id = :group_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['group_id' => $groupId, 'user_id' => $userId]);

        return $stmt->fetchColumn() !== false;
    }

    public function isAdmin(int $groupId, int $userId): bool
    {
        $membership = $this->findMembership($groupId, $userId);
        return $membership !== null && $membership['role'] === 'admin';
    }

    public function addMember(int $groupId, int $userId, string $role, string $joinedAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO group_members (group_id, user_id, role, joined_at) ' .
            'VALUES (:group_id, :user_id, :role, :joined_at)'
        );
        $stmt->execute([
            'group_id' => $groupId,
            'user_id' => $userId,
            'role' => $role,
            'joined_at' => $joinedAt,
        ]);
    }
}
