<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Create Container and configure it
$container = new Container();
$containerConfig = require __DIR__ . '/../config/container.php';
$containerConfig($container);

// Set container to create App with
AppFactory::setContainer($container);

// Create App
$app = AppFactory::create();

// Register middleware
$middlewareConfig = require __DIR__ . '/../config/middleware.php';
$middlewareConfig($app);

// Register routes
$routesConfig = require __DIR__ . '/../config/routes.php';
$routesConfig($app);

// Run app
$app->run();
