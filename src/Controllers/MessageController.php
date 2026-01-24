<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MessageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @OA\Tag(
 *     name="Messages",
 *     description="Group messaging endpoints"
 * )
 */
class MessageController extends BaseController
{
    public function __construct(private MessageService $messageService)
    {
    }

    /**
     * @OA\Post(
     *     path="/groups/{groupId}/messages",
     *     operationId="sendMessage",
     *     tags={"Messages"},
     *     summary="Send a message to a group",
     *     description="Send a message to a group. User must be a member of the group.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="groupId",
     *         in="path",
     *         description="Group UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         description="Message content",
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(
     *                 property="content",
     *                 type="string",
     *                 description="Message content",
     *                 example="Hello everyone!"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="group_id", type="string", format="uuid"),
     *             @OA\Property(property="user_uuid", type="string", format="uuid"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Not a member of the group"),
     *     @OA\Response(response=404, description="Group not found")
     * )
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

        try {
            $result = $this->messageService->sendMessage($groupId, $userUuid, $content);
            return $this->jsonResponse($response, 201, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     *     path="/groups/{groupId}/messages",
     *     operationId="listMessages",
     *     tags={"Messages"},
     *     summary="List messages in a group",
     *     description="Get paginated list of messages in a group. User must be a member of the group.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="groupId",
     *         in="path",
     *         description="Group UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Number of messages per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Messages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="uuid", type="string", format="uuid"),
     *                     @OA\Property(property="group_id", type="string", format="uuid"),
     *                     @OA\Property(property="user_id", type="string", format="uuid"),
     *                     @OA\Property(property="username", type="string"),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="page_size", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="total_pages", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Not a member of the group"),
     *     @OA\Response(response=404, description="Group not found")
     * )
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
