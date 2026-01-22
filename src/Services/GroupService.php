<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GroupRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\InvitationRepository;
use App\Utils\Validator;
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
    public function createGroup(string $name, ?string $description, bool $isPrivate, string $userUuid): array
    {
        // Validate and sanitize input
        $validation = Validator::validateGroupCreation([
            'name' => $name,
            'description' => $description,
            'is_private' => $isPrivate,
        ]);

        if (!$validation['valid']) {
            throw new \RuntimeException(Validator::formatErrors($validation['errors']), 400);
        }

        $sanitizedName = $validation['sanitized']['name'];
        $sanitizedDescription = $validation['sanitized']['description'];
        $sanitizedIsPrivate = $validation['sanitized']['is_private'];

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->pdo->beginTransaction();

        try {
            $groupId = $this->groupRepository->create($sanitizedName, $sanitizedDescription, $sanitizedIsPrivate, $userUuid, $now);
            $this->membershipRepository->addMember($groupId, $userUuid, 'admin', $now);
            $this->pdo->commit();

            return [
                'id' => $groupId,
                'name' => $sanitizedName,
                'description' => $sanitizedDescription,
                'is_private' => $sanitizedIsPrivate,
                'created_by' => $userUuid,
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
    public function joinGroup(string $groupId, string $userUuid, ?string $inviteToken): array
    {
        $group = $this->groupRepository->findById($groupId);
        if ($group === null) {
            throw new \RuntimeException('Group not found', 404);
        }

        $existingMembership = $this->membershipRepository->findMembership($groupId, $userUuid);
        if ($existingMembership !== null) {
            return [
                'message' => 'Already joined',
                'group_id' => $groupId,
                'user_uuid' => $userUuid,
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

            $this->membershipRepository->addMember($groupId, $userUuid, 'member', $now);
            $this->pdo->commit();

            return [
                'message' => 'Joined group',
                'group_id' => $groupId,
                'user_uuid' => $userUuid,
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
    public function createInvitation(string $groupId, string $userUuid, string $email, int $expiresInHours): array
    {
        // Validate and sanitize input
        $validation = Validator::validateInvitation([
            'email' => $email,
            'expires_in_hours' => $expiresInHours,
        ]);

        if (!$validation['valid']) {
            throw new \RuntimeException(Validator::formatErrors($validation['errors']), 400);
        }

        $sanitizedEmail = $validation['sanitized']['email'];
        $sanitizedExpiresInHours = $validation['sanitized']['expires_in_hours'];

        $group = $this->groupRepository->findById($groupId);
        if ($group === null) {
            throw new \RuntimeException('Group not found', 404);
        }

        if (!$this->membershipRepository->isAdmin($groupId, $userUuid)) {
            throw new \RuntimeException('Only group admins can invite users', 403);
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $expiresAt = (new DateTimeImmutable("+{$sanitizedExpiresInHours} hours"))->format('Y-m-d H:i:s');
        $token = bin2hex(random_bytes(16));

        $this->invitationRepository->create($groupId, $userUuid, $sanitizedEmail, $token, $now, $expiresAt);

        return [
            'group_id' => $groupId,
            'token' => $token,
            'email' => $sanitizedEmail,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Get all groups for a user
     * @return array<int,array<string,mixed>>
     */
    public function getGroupsForUser(string $userUuid): array
    {
        return $this->groupRepository->findGroupsForUser($userUuid);
    }

    /**
     * Get group details with member information
     * @return array<string,mixed>
     * @throws \RuntimeException
     */
    public function getGroupDetails(string $groupId): array
    {
        $group = $this->groupRepository->findById($groupId);
        if ($group === null) {
            throw new \RuntimeException('Group not found', 404);
        }

        $members = $this->groupRepository->getGroupMembers($groupId);

        return [
            'uuid' => $group['uuid'],
            'name' => $group['name'],
            'description' => $group['description'],
            'is_private' => $group['is_private'],
            'created_by' => $group['created_by'],
            'created_at' => $group['created_at'],
            'members' => $members,
            'member_count' => count($members),
        ];
    }
}
