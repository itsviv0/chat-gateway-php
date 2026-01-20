<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class HealthController
{
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
        if ($json === false) {
            $json = '{"error":"Failed to encode response"}';
        }

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
