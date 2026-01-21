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
    ): string
    {
        $uuid = $this->generateUuid();
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

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return implode('-', [
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6)),
        ]);
    }
}
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return implode('-', [
        bin2hex(substr($data, 0, 4)),
        bin2hex(substr($data, 4, 2)),
        bin2hex(substr($data, 6, 2)),
        bin2hex(substr($data, 8, 2)),
        bin2hex(substr($data, 10, 6)),
    ]);
}
