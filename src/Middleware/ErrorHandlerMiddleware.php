<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Psr7\Response;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpNotFoundException $e) {
            return $this->createJsonErrorResponse(404, 'Route not found', $e->getMessage());
        } catch (HttpMethodNotAllowedException $e) {
            return $this->createJsonErrorResponse(405, 'Method not allowed', $e->getMessage());
        } catch (\Throwable $e) {
            // Log the error in production
            error_log($e->getMessage());

            // Return generic error in production, detailed in development
            $isDebug = (bool) ($_ENV['APP_DEBUG'] ?? false);
            $message = $isDebug ? $e->getMessage() : 'An error occurred processing your request';
            $details = $isDebug ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ] : [];

            return $this->createJsonErrorResponse(500, 'Internal Server Error', $message, $details);
        }
    }

    /**
     * @param array<string,mixed> $details
     */
    private function createJsonErrorResponse(int $statusCode, string $error, string $message, array $details = []): ResponseInterface
    {
        $response = new Response();
        $response = $response->withStatus($statusCode);
        $response = $response->withHeader('Content-Type', 'application/json');

        $body = [
            'error' => $error,
            'message' => $message,
        ];

        if (!empty($details)) {
            $body['details'] = $details;
        }

        $json = json_encode($body, JSON_PRETTY_PRINT);
        $response->getBody()->write($json !== false ? $json : '{"error":"Internal Server Error"}');

        return $response;
    }
}
