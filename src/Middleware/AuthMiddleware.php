<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware to handle authentication via Bearer token.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private Database $database)
    {
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

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE api_token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if ($user === false) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        $request = $request->withAttribute('token', $token)
            ->withAttribute('user', $user);

        return $handler->handle($request);
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
