<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

/**
 * Controller to handle health check requests.
 */
class HealthController
{
    /**
     * Handle health check request.
     *
     * Returns a JSON response indicating the service health status,
     * current timestamp, and service name.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function check(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = [
            'status' => 'healthy',
            'timestamp' => time(),
            'service' => $_ENV['APP_NAME'] ?? 'Chat Gateway API'
        ];

        $json = json_encode($data);
        $statusCode = 200;

        if ($json === false) {
            $json = '{"error":"Failed to encode response"}';
            $statusCode = 500;
        }

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
