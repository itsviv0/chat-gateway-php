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
    $container->set(Database::class, function (Container $c) {
        $envPath = $_ENV['DB_PATH'] ?? 'database/database.sqlite';

        // Resolve path. Check if it is absolute.
        // Unix: starts with /
        // Windows: starts with Drive:\ or Drive:/ or \ (UNC)
        $isAbsolute = str_starts_with($envPath, '/') ||
            str_starts_with($envPath, '\\') ||
            preg_match('/^[a-zA-Z]:[\\\\\/]/', $envPath);

        $dbPath = $isAbsolute ? $envPath : __DIR__ . '/../' . $envPath;

        $logger = $c->get(LoggerInterface::class);
        return new Database($dbPath, $logger);
    });

    return $container;
};
