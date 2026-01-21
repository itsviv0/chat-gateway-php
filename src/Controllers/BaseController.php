<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;

abstract class BaseController
{
    /**
     * @param array<string,mixed> $payload
     */
    protected function jsonResponse(ResponseInterface $response, int $statusCode, array $payload): ResponseInterface
    {
        $json = json_encode($payload);
        if ($json === false) {
            $json = json_encode(['error' => 'Failed to encode response']);
            $statusCode = 500;
        }

        $response->getBody()->write($json ?: '');

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
