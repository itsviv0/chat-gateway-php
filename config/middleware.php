<?php

declare(strict_types=1);

use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonBodyParserMiddleware;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app) {
    // Parse JSON body
    $app->add(new JsonBodyParserMiddleware());

    // CORS
    $app->add(new CorsMiddleware());

    // Error middleware with custom error handler
    $errorMiddleware = $app->addErrorMiddleware(
        (bool) ($_ENV['APP_DEBUG'] ?? false),
        true,
        true
    );

    // Custom error handler for JSON responses
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorMiddleware->setErrorHandler(
        HttpNotFoundException::class,
        function (ServerRequestInterface $request, \Throwable $exception, bool $displayErrorDetails) {
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(404);
            $response = $response->withHeader('Content-Type', 'application/json');
            
            $body = json_encode([
                'error' => 'Route not found',
                'message' => $exception->getMessage()
            ], JSON_PRETTY_PRINT);
            
            $response->getBody()->write($body);
            return $response;
        }
    );

    $errorMiddleware->setErrorHandler(
        HttpMethodNotAllowedException::class,
        function (ServerRequestInterface $request, \Throwable $exception, bool $displayErrorDetails) {
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(405);
            $response = $response->withHeader('Content-Type', 'application/json');
            
            $body = json_encode([
                'error' => 'Method not allowed',
                'message' => $exception->getMessage()
            ], JSON_PRETTY_PRINT);
            
            $response->getBody()->write($body);
            return $response;
        }
    );

    // Routing middleware
    $app->addRoutingMiddleware();
};
