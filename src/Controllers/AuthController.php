<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 */
class AuthController extends BaseController
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     operationId="login",
     *     tags={"Authentication"},
     *     summary="User login",
     *     description="Authenticate user with email and password, returns JWT token",
     *     @OA\RequestBody(
     *         description="Login credentials",
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="john@example.com"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="password123"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 description="JWT token for subsequent authenticated requests"
     *             ),
     *             @OA\Property(
     *                 property="user",
     *                 @OA\Property(property="uuid", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="email", type="string", format="email")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Missing required fields"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = (array) ($request->getParsedBody() ?? []);
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        try {
            $result = $this->authService->login($email, $password);
            return $this->jsonResponse($response, 200, $result);
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->jsonResponse($response, $statusCode, ['error' => $e->getMessage()]);
        }
    }
}
