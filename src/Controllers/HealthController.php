<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

/**
 * @OA\Tag(
 *     name="Health",
 *     description="Service health check"
 * )
 */
class HealthController
{
    /**
     * @OA\Get(
     *     path="/health",
     *     operationId="healthCheck",
     *     tags={"Health"},
     *     summary="Health check",
     *     description="Returns service health status",
     *     @OA\Response(
     *         response=200,
     *         description="Service is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="service", type="string", example="Chat Gateway API")
     *         )
     *     )
     * )
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
