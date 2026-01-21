<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;

class AuthService
{
    private string $jwtSecret;
    private int $tokenExpiry;

    public function __construct()
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production';
        $this->tokenExpiry = (int)($_ENV['JWT_EXPIRY'] ?? 3600); // Default 1 hour
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
