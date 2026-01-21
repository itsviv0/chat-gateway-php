<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class InvitationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findPendingInvitation(string $groupId, string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, expires_at 
            FROM invitations 
            WHERE group_id = :group_id AND token = :token AND status = :status LIMIT 1'
        );
        $stmt->execute([
            'group_id' => $groupId,
            'token' => $token,
            'status' => 'pending',
        ]);
        $invitation = $stmt->fetch();

        return $invitation !== false ? $invitation : null;
    }

    public function create(
        string $groupId,
        string $inviterUuid,
        string $email,
        string $token,
        string $createdAt,
        string $expiresAt
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO invitations (group_id, inviter_id, email, token, status, created_at, expires_at) ' .
            'VALUES (:group_id, :inviter_id, :email, :token, :status, :created_at, :expires_at)'
        );
        $stmt->execute([
            'group_id' => $groupId,
            'inviter_id' => $inviterUuid,
            'email' => $email,
            'token' => $token,
            'status' => 'pending',
            'created_at' => $createdAt,
            'expires_at' => $expiresAt,
        ]);
    }

    public function markAsAccepted(int $invitationId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE invitations SET status = :status WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'accepted',
            'id' => $invitationId,
        ]);
    }
}
