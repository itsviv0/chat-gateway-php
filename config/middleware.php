<?php

declare(strict_types=1);

use Slim\App;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonBodyParserMiddleware;

return function (App $app) {
    // Parse JSON body
    $app->add(new JsonBodyParserMiddleware());

    // CORS
    $app->add(new CorsMiddleware());

    // Error middleware
    $app->addErrorMiddleware(
        (bool) ($_ENV['APP_DEBUG'] ?? false),
        true,
        true
    );

    // Routing middleware
    $app->addRoutingMiddleware();
};
