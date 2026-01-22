<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Utils\Validator;
use Firebase\JWT\JWT;

class AuthService
{
    private string $jwtSecret;
    private int $tokenExpiry;

    public function __construct(private UserRepository $userRepository)
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production';
        $this->tokenExpiry = (int)($_ENV['JWT_EXPIRY'] ?? 3600); // Default 1 hour
    }

    /**
     * Login user with email and password.
     *
     * @param string $email
     * @param string $password
     * @return array<string,mixed> Array containing token and user data
     * @throws \RuntimeException on validation or authentication failure
     */
    public function login(string $email, string $password): array
    {
        // Validate and sanitize input
        $validation = Validator::validateLogin([
            'email' => $email,
            'password' => $password,
        ]);

        if (!$validation['valid']) {
            throw new \RuntimeException(Validator::formatErrors($validation['errors']), 400);
        }

        $sanitizedEmail = $validation['sanitized']['email'];
        $sanitizedPassword = $validation['sanitized']['password'];

        $user = $this->userRepository->findByEmail($sanitizedEmail);
        if ($user === null) {
            throw new \RuntimeException('Invalid email or password', 401);
        }

        if (!password_verify($sanitizedPassword, $user['password_hash'])) {
            throw new \RuntimeException('Invalid email or password', 401);
        }

        $token = $this->generateToken($user['uuid'], $user['username']);

        return [
            'token' => $token,
            'user' => [
                'uuid' => $user['uuid'],
                'username' => $user['username'],
                'email' => $user['email'],
            ],
        ];
    }

    /**
     * Generate a JWT token for a user.
     *
     * @param string $userUuid
     * @param string $username
     * @param array<string,mixed> $additionalClaims
     * @return string
     */
    public function generateToken(string $userUuid, string $username, array $additionalClaims = []): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->tokenExpiry;

        $payload = array_merge([
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => $userUuid,
            'username' => $username,
        ], $additionalClaims);

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Get the JWT secret.
     *
     * @return string
     */
    public function getJwtSecret(): string
    {
        return $this->jwtSecret;
    }

    /**
     * Get the token expiry time in seconds.
     *
     * @return int
     */
    public function getTokenExpiry(): int
    {
        return $this->tokenExpiry;
    }
}
