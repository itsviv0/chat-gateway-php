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
        $logger = new Logger($_ENV['APP_NAME']);
        $logger->pushHandler(
            new StreamHandler(
                __DIR__ . '/../' . $_ENV['LOG_PATH'],
                $_ENV['LOG_LEVEL']
            )
        );
        return $logger;
    });

    // Database
    $container->set(Database::class, function () {
        return new Database($_ENV['DB_PATH']);
    });

    return $container;
};
