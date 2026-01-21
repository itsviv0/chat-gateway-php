<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Ramsey\Uuid\Uuid;

class MessageRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $groupId, string $userUuid, string $content, string $createdAt): string
    {
        $uuid = Uuid::uuid4()->toString();
        $stmt = $this->pdo->prepare(
            'INSERT INTO messages (uuid, group_id, user_id, content, created_at) ' .
            'VALUES (:uuid, :group_id, :user_id, :content, :created_at)'
        );
        $stmt->execute([
            'uuid' => $uuid,
            'group_id' => $groupId,
            'user_id' => $userUuid,
            'content' => $content,
            'created_at' => $createdAt,
        ]);

        return $uuid;
    }

    public function countByGroup(string $groupId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as total FROM messages WHERE group_id = :group_id');
        $stmt->execute(['group_id' => $groupId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByGroup(string $groupId, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.uuid, m.group_id, m.user_id, u.username, m.content, m.created_at ' .
            'FROM messages m ' .
            'JOIN users u ON u.uuid = m.user_id ' .
            'WHERE m.group_id = :group_id ' .
            'ORDER BY m.created_at ASC ' .
            'LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
