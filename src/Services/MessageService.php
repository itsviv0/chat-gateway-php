<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GroupRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\MessageRepository;
use App\Utils\Validator;
use DateTimeImmutable;

class MessageService
{
    public function __construct(
        private GroupRepository $groupRepository,
        private MembershipRepository $membershipRepository,
        private MessageRepository $messageRepository
    ) {
    }

    /**
     * @return array<string,mixed>
     * @throws \RuntimeException
     */
    public function sendMessage(string $groupId, string $userUuid, string $content): array
    {
        // Validate and sanitize input
        $validation = Validator::validateMessage(['content' => $content]);

        if (!$validation['valid']) {
            throw new \RuntimeException(Validator::formatErrors($validation['errors']), 400);
        }

        $sanitizedContent = $validation['sanitized']['content'];

        if ($this->groupRepository->findById($groupId) === null) {
            throw new \RuntimeException('Group not found', 404);
        }

        if (!$this->membershipRepository->isMember($groupId, $userUuid)) {
            throw new \RuntimeException('You must join the group before sending messages', 403);
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $messageId = $this->messageRepository->create($groupId, $userUuid, $sanitizedContent, $now);

        return [
            'id' => $messageId,
            'group_id' => $groupId,
            'user_uuid' => $userUuid,
            'content' => $sanitizedContent,
            'created_at' => $now,
        ];
    }

    /**
     * @return array<string,mixed>
     * @throws \RuntimeException
     */
    public function listMessages(string $groupId, string $userUuid, int $page, int $pageSize): array
    {
        if ($this->groupRepository->findById($groupId) === null) {
            throw new \RuntimeException('Group not found', 404);
        }

        if (!$this->membershipRepository->isMember($groupId, $userUuid)) {
            throw new \RuntimeException('You must join the group to view messages', 403);
        }

        $page = max(1, $page);
        $pageSize = max(1, min($pageSize, 100));
        $offset = ($page - 1) * $pageSize;

        $total = $this->messageRepository->countByGroup($groupId);
        $messages = $this->messageRepository->findByGroup($groupId, $pageSize, $offset);

        return [
            'data' => $messages,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
            ],
        ];
    }
}
