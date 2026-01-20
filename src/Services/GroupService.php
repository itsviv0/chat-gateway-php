<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GroupRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\InvitationRepository;
use DateTimeImmutable;
use PDO;
use PDOException;

class GroupService
{
    public function __construct(
        private PDO $pdo,
        private GroupRepository $groupRepository,
        private MembershipRepository $membershipRepository,
        private InvitationRepository $invitationRepository
    ) {
    }

    /**
     * @return array<string,mixed>
     * @throws PDOException
     */
    public function createGroup(string $name, ?string $description, bool $isPrivate, int $userId): array
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->pdo->beginTransaction();

        try {
            $groupId = $this->groupRepository->create($name, $description, $isPrivate, $userId, $now);
            $this->membershipRepository->addMember($groupId, $userId, 'admin', $now);
            $this->pdo->commit();

            return [
                'id' => $groupId,
                'name' => $name,
                'description' => $description,
                'is_private' => $isPrivate,
                'created_by' => $userId,
                'created_at' => $now,
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return array<string,mixed>
     * @throws \RuntimeException
     * @throws PDOException
     */
    public function joinGroup(int $groupId, int $userId, ?string $inviteToken): array
    {
        $group = $this->groupRepository->findById($groupId);
        if ($group === null) {
            throw new \RuntimeException('Group not found', 404);
        }

        $existingMembership = $this->membershipRepository->findMembership($groupId, $userId);
        if ($existingMembership !== null) {
            return [
                'message' => 'Already joined',
                'group_id' => $groupId,
                'user_id' => $userId,
            ];
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->beginTransaction();

        try {
            if ((bool) $group['is_private']) {
                if ($inviteToken === null || $inviteToken === '') {
                    throw new \RuntimeException('Invitation token required for private groups', 403);
                }

                $invitation = $this->invitationRepository->findPendingInvitation($groupId, $inviteToken);
                if ($invitation === null) {
                    throw new \RuntimeException('Invalid invitation token', 403);
                }

                if (strtotime($invitation['expires_at']) < time()) {
                    throw new \RuntimeException('Invitation token has expired', 403);
                }

                $this->invitationRepository->markAsAccepted((int) $invitation['id']);
            }

            $this->membershipRepository->addMember($groupId, $userId, 'member', $now);
            $this->pdo->commit();

            return [
                'message' => 'Joined group',
                'group_id' => $groupId,
                'user_id' => $userId,
            ];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return array<string,mixed>
     * @throws \RuntimeException
     * @throws PDOException
     */
    public function createInvitation(int $groupId, int $userId, string $email, int $expiresInHours): array
    {
        $group = $this->groupRepository->findById($groupId);
        if ($group === null) {
            throw new \RuntimeException('Group not found', 404);
        }

        if (!$this->membershipRepository->isAdmin($groupId, $userId)) {
            throw new \RuntimeException('Only group admins can invite users', 403);
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $expiresAt = (new DateTimeImmutable("+{$expiresInHours} hours"))->format('Y-m-d H:i:s');
        $token = bin2hex(random_bytes(16));

        $this->invitationRepository->create($groupId, $userId, $email, $token, $now, $expiresAt);

        return [
            'group_id' => $groupId,
            'token' => $token,
            'email' => $email,
            'expires_at' => $expiresAt,
        ];
    }
}
