<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class MessageRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(int $groupId, int $userId, string $content, string $createdAt): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO messages (group_id, user_id, content, created_at) ' .
            'VALUES (:group_id, :user_id, :content, :created_at)'
        );
        $stmt->execute([
            'group_id' => $groupId,
            'user_id' => $userId,
            'content' => $content,
            'created_at' => $createdAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function countByGroup(int $groupId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as total FROM messages WHERE group_id = :group_id');
        $stmt->execute(['group_id' => $groupId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByGroup(int $groupId, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.group_id, m.user_id, u.username, m.content, m.created_at ' .
            'FROM messages m ' .
            'JOIN users u ON u.id = m.user_id ' .
            'WHERE m.group_id = :group_id ' .
            'ORDER BY m.created_at ASC ' .
            'LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
