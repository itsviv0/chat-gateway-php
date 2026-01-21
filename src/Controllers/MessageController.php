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
        $groupId = (string) ($args['groupId'] ?? '');
        $payload = (array) ($request->getParsedBody() ?? []);
        $content = isset($payload['content']) ? trim((string) $payload['content']) : '';
        $userUuid = $request->getAttribute('user_uuid');

        if (!$userUuid) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        if ($content === '') {
            return $this->jsonResponse($response, 400, ['error' => 'Message content is required']);
        }

        if (strlen($content) > 5000) {
            return $this->jsonResponse(
                $response,
                400,
                ['error' => 'Message content must not exceed 5000 characters']
            );
        }

        try {
            $result = $this->messageService->sendMessage($groupId, $userUuid, $content);
            return $this->jsonResponse($response, 201, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string,mixed> $args
     */
    public function listMessages(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $groupId = (string) ($args['groupId'] ?? '');
        $userUuid = $request->getAttribute('user_uuid');

        if (!$userUuid) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        $queryParams = $request->getQueryParams();
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $pageSize = (int) ($queryParams['page_size'] ?? 20);

        try {
            $result = $this->messageService->listMessages($groupId, $userUuid, $page, $pageSize);
            return $this->jsonResponse($response, 200, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }
}
