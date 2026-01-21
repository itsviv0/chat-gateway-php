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

        $userUuid = $request->getAttribute('user_uuid');

        if (!$userUuid) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        try {
            $result = $this->groupService->createGroup($name, $description, $isPrivate, $userUuid);
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
        $groupId = (string) ($args['groupId'] ?? '');
        $userUuid = $request->getAttribute('user_uuid');
        $payload = (array) ($request->getParsedBody() ?? []);
        $inviteToken = isset($payload['invite_token']) ? trim((string) $payload['invite_token']) : null;

        if (!$userUuid) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        try {
            $result = $this->groupService->joinGroup($groupId, $userUuid, $inviteToken);
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
        $groupId = (string) ($args['groupId'] ?? '');
        $userUuid = $request->getAttribute('user_uuid');
        $payload = (array) ($request->getParsedBody() ?? []);
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $expiresInHours = isset($payload['expires_in_hours']) ? (int) $payload['expires_in_hours'] : 168;

        if (!$userUuid) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        if ($email === '') {
            return $this->jsonResponse($response, 400, ['error' => 'Email is required to issue an invitation']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse($response, 400, ['error' => 'Invalid email address format']);
        }

        if ($expiresInHours < 1 || $expiresInHours > 720) {
            return $this->jsonResponse(
                $response,
                400,
                ['error' => 'Expiration time must be between 1 and 720 hours (30 days)']
            );
        }

        try {
            $result = $this->groupService->createInvitation($groupId, $userUuid, $email, $expiresInHours);
            return $this->jsonResponse($response, 201, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }
}
