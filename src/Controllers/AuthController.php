<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends BaseController
{
    public function __construct(
        private AuthService $authService,
        private UserRepository $userRepository
    ) {
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = (array) ($request->getParsedBody() ?? []);
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        if ($email === '' || $password === '') {
            return $this->jsonResponse($response, 400, ['error' => 'Email and password are required']);
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            return $this->jsonResponse($response, 401, ['error' => 'Invalid email or password']);
        }

        if (!password_verify($password, $user['password_hash'])) {
            return $this->jsonResponse($response, 401, ['error' => 'Invalid email or password']);
        }

        $token = $this->authService->generateToken($user['uuid'], $user['username']);

        return $this->jsonResponse($response, 200, [
            'token' => $token,
            'user' => [
                'uuid' => $user['uuid'],
                'username' => $user['username'],
                'email' => $user['email'],
            ],
        ]);
    }
}
