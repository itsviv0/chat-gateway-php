<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\GroupService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PDOException;

/**
 * @OA\Tag(
 *     name="Groups",
 *     description="Group management and messaging endpoints"
 * )
 */
class GroupController extends BaseController
{
    public function __construct(private GroupService $groupService)
    {
    }

    /**
     * @OA\Post(
     *     path="/groups",
     *     operationId="createGroup",
     *     tags={"Groups"},
     *     summary="Create a new group",
     *     description="Create a new chat group",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=100),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_private", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Group created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="uuid", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_public", type="integer"),
     *             @OA\Property(property="created_by_uuid", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid input"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/groups",
     *     operationId="listGroups",
     *     tags={"Groups"},
     *     summary="List user's groups",
     *     description="Get all groups accessible to the user (public groups + groups where user is a member)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of groups",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="uuid", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="is_public", type="integer"),
     *                 @OA\Property(property="created_by_uuid", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userUuid = $request->getAttribute('user_uuid');

        if (!$userUuid) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        try {
            $groups = $this->groupService->getGroupsForUser($userUuid);
            return $this->jsonResponse($response, 200, ['data' => $groups]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, ['error' => 'Failed to retrieve groups']);
        }
    }

    /**
     * @OA\Get(
     *     path="/groups/{groupId}",
     *     operationId="getGroupDetails",
     *     tags={"Groups"},
     *     summary="Get group details with members",
     *     description="Retrieve detailed information about a group including its member list",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupId",
     *         in="path",
     *         required=true,
     *         description="Group UUID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group details retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="uuid", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_public", type="integer"),
     *             @OA\Property(property="created_by_uuid", type="string"),
     *             @OA\Property(property="member_count", type="integer"),
     *             @OA\Property(
     *                 property="members",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="uuid", type="string"),
     *                     @OA\Property(property="username", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="role", type="string"),
     *                     @OA\Property(property="joined_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Group not found")
     * )
     */
    /**
     * @param array<string, mixed> $args
     */
    public function getDetails(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $groupId = (string) ($args['groupId'] ?? '');
        $userUuid = $request->getAttribute('user_uuid');

        if (!$userUuid) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        try {
            $result = $this->groupService->getGroupDetails($groupId);
            return $this->jsonResponse($response, 200, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }
}
