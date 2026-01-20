<?php

declare(strict_types=1);

use Slim\App;
use App\Controllers\HealthController;

return function (App $app) {
    // Health check endpoint
    $app->get('/health', [HealthController::class, 'check']);

    // API routes will be added in future phases after database setup
};
