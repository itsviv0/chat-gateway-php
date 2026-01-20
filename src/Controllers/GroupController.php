<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\GroupService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PDOException;

class GroupController extends BaseController
{
    public function __construct(private GroupService $groupService)
    {
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = (array) ($request->getParsedBody() ?? []);
        $name = trim((string) ($payload['name'] ?? ''));
        $description = isset($payload['description']) ? trim((string) $payload['description']) : null;
        $isPrivate = filter_var($payload['is_private'] ?? false, FILTER_VALIDATE_BOOL);

        if ($name === '' || strlen($name) > 100) {
            return $this->jsonResponse(
                $response,
                400,
                ['error' => 'Group name is required and must be at most 100 characters']
            );
        }

        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);

        try {
            $result = $this->groupService->createGroup($name, $description, $isPrivate, $userId);
            return $this->jsonResponse($response, 201, $result);
        } catch (PDOException $exception) {
            return $this->jsonResponse($response, 500, ['error' => 'Failed to create group']);
        }
    }

    /**
     * @param array<string,mixed> $args
     */
    public function join(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $groupId = (int) ($args['groupId'] ?? 0);
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);
        $payload = (array) ($request->getParsedBody() ?? []);
        $inviteToken = isset($payload['invite_token']) ? trim((string) $payload['invite_token']) : null;

        try {
            $result = $this->groupService->joinGroup($groupId, $userId, $inviteToken);
            return $this->jsonResponse($response, 200, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string,mixed> $args
     */
    public function invite(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $groupId = (int) ($args['groupId'] ?? 0);
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);
        $payload = (array) ($request->getParsedBody() ?? []);
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $expiresInHours = isset($payload['expires_in_hours']) ? (int) $payload['expires_in_hours'] : 168;

        if ($email === '') {
            return $this->jsonResponse($response, 400, ['error' => 'Email is required to issue an invitation']);
        }

        try {
            $result = $this->groupService->createInvitation($groupId, $userId, $email, $expiresInHours);
            return $this->jsonResponse($response, 201, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }
}
