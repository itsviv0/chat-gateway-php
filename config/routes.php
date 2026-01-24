<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\HealthController;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Controllers\SwaggerController;
use App\Middleware\AuthMiddleware;

return function (App $app) {
    // Swagger documentation
    $app->get('/swagger', [SwaggerController::class, 'ui']);
    $app->get('/swagger/', [SwaggerController::class, 'ui']);
    $app->get('/swagger.json', [SwaggerController::class, 'spec']);

    // Health check endpoint
    $app->get('/health', [HealthController::class, 'check']);

    // Auth endpoints (no auth required)
    $app->post('/auth/login', [AuthController::class, 'login']);

    // Protected user endpoints
    $app->get('/users/me', [UserController::class, 'getMe'])->add(AuthMiddleware::class);

    // Protected group endpoints
    $app->group('/groups', function (RouteCollectorProxy $group) {
        $group->get('', [GroupController::class, 'list']);
        $group->post('', [GroupController::class, 'create']);
        $group->get('/{groupId}', [GroupController::class, 'getDetails']);
        $group->post('/{groupId}/join', [GroupController::class, 'join']);
        $group->post('/{groupId}/invite', [GroupController::class, 'invite']);
        $group->post('/{groupId}/messages', [MessageController::class, 'send']);
        $group->get('/{groupId}/messages', [MessageController::class, 'listMessages']);
    })->add(AuthMiddleware::class);
};

