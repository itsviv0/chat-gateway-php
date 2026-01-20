<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\HealthController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Middleware\AuthMiddleware;

return function (App $app) {
    // Health check endpoint
    $app->get('/health', [HealthController::class, 'check']);

    // Protected API routes
    $app->group('/groups', function (RouteCollectorProxy $group) {
        $group->post('', [GroupController::class, 'create']);
        $group->post('/{groupId}/join', [GroupController::class, 'join']);
        $group->post('/{groupId}/invite', [GroupController::class, 'invite']);
        $group->post('/{groupId}/messages', [MessageController::class, 'send']);
        $group->get('/{groupId}/messages', [MessageController::class, 'listMessages']);
    })->add(AuthMiddleware::class);
};
