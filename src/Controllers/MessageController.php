<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MessageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MessageController extends BaseController
{
    public function __construct(private MessageService $messageService)
    {
    }

    /**
     * @param array<string,mixed> $args
     */
    public function send(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $groupId = (int) ($args['groupId'] ?? 0);
        $payload = (array) ($request->getParsedBody() ?? []);
        $content = isset($payload['content']) ? trim((string) $payload['content']) : '';
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);

        if ($content === '') {
            return $this->jsonResponse($response, 400, ['error' => 'Message content is required']);
        }

        try {
            $result = $this->messageService->sendMessage($groupId, $userId, $content);
            return $this->jsonResponse($response, 201, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string,mixed> $args
     */
    public function listMessages(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $groupId = (int) ($args['groupId'] ?? 0);
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);

        $queryParams = $request->getQueryParams();
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $pageSize = (int) ($queryParams['page_size'] ?? 20);

        try {
            $result = $this->messageService->listMessages($groupId, $userId, $page, $pageSize);
            return $this->jsonResponse($response, 200, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }
}