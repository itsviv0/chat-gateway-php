<?php

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware to handle authentication via JWT Bearer token.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private string $jwtSecret;

    public function __construct()
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production';
    }

    /**
     * Process an incoming server request.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorizedResponse('Missing authorization header');
        }

        // Extract token (Bearer token format)
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Invalid authorization format');
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            // Extract user UUID and other claims from JWT
            $userUuid = $decoded->sub;
            $username = $decoded->username ?? null;

            // Add user info to request attributes
            $request = $request->withAttribute('user_uuid', $userUuid)
                ->withAttribute('username', $username)
                ->withAttribute('jwt_claims', $decoded);

            return $handler->handle($request);
        } catch (\Exception $e) {
            return $this->unauthorizedResponse('Invalid or expired token: ' . $e->getMessage());
        }
    }

    /**
     * Create an unauthorized response.
     *
     * @param string $message
     * @return ResponseInterface
     */
    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response();

        $json = json_encode(['error' => $message]);
        if ($json === false) {
            error_log(sprintf(
                'AuthMiddleware unauthorizedResponse json_encode failed: %s (original message: %s)',
                json_last_error_msg(),
                $message
            ));
            $json = '{"error":"Authorization failed"}';
        }

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
