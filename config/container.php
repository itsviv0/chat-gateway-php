<?php

declare(strict_types=1);

use DI\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Services\Database;
use Psr\Log\LoggerInterface;

return function (Container $container) {

    // Logger
    $container->set(LoggerInterface::class, function () {
        $logger = new Logger($_ENV['APP_NAME'] ?? 'chat-gateway');
        $logger->pushHandler(
            new StreamHandler(
                __DIR__ . '/../' . ($_ENV['LOG_PATH'] ?? 'logs/app.log'),
                $_ENV['LOG_LEVEL'] ?? Logger::DEBUG
            )
        );
        return $logger;
    });

    // Database
    $container->set(Database::class, function () {
        return new Database($_ENV['DB_PATH'] ?? 'database/chat.db');
    });

    return $container;
};
