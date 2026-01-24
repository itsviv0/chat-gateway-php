<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="User profile endpoints"
 * )
 */
class UserController extends BaseController
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * @OA\Get(
     *     path="/users/me",
     *     operationId="getCurrentUser",
     *     tags={"Users"},
     *     summary="Get current user profile",
     *     description="Returns the authenticated user's profile information",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="uuid", type="string", description="User UUID"),
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - missing or invalid token"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function getMe(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userUuid = $request->getAttribute('user_uuid');

        if (!$userUuid) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        $user = $this->userRepository->findByUuid($userUuid);
        if ($user === null) {
            return $this->jsonResponse($response, 404, ['error' => 'User not found']);
        }

        return $this->jsonResponse($response, 200, [
            'uuid' => $user['uuid'],
            'username' => $user['username'],
            'email' => $user['email'],
            'created_at' => $user['created_at'],
        ]);
    }
}
