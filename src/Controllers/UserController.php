<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends BaseController
{
    public function __construct(private UserRepository $userRepository)
    {
    }

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
