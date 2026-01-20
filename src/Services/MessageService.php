<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GroupRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\MessageRepository;
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
    public function sendMessage(int $groupId, int $userId, string $content): array
    {
        if ($this->groupRepository->findById($groupId) === null) {
            throw new \RuntimeException('Group not found', 404);
        }

        if (!$this->membershipRepository->isMember($groupId, $userId)) {
            throw new \RuntimeException('You must join the group before sending messages', 403);
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $messageId = $this->messageRepository->create($groupId, $userId, $content, $now);

        return [
            'id' => $messageId,
            'group_id' => $groupId,
            'user_id' => $userId,
            'content' => $content,
            'created_at' => $now,
        ];
    }

    /**
     * @return array<string,mixed>
     * @throws \RuntimeException
     */
    public function listMessages(int $groupId, int $userId, int $page, int $pageSize): array
    {
        if ($this->groupRepository->findById($groupId) === null) {
            throw new \RuntimeException('Group not found', 404);
        }

        if (!$this->membershipRepository->isMember($groupId, $userId)) {
            throw new \RuntimeException('You must join the group to view messages', 403);
        }

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
