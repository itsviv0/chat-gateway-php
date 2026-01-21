<?php

declare(strict_types=1);

use DI\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Services\Database;
use App\Services\GroupService;
use App\Services\MessageService;
use App\Repositories\GroupRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\MessageRepository;
use Psr\Log\LoggerInterface;
use App\Middleware\AuthMiddleware;
use PDO;

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

    // PDO connection (for repositories)
    $container->set(PDO::class, function (Container $c) {
        return $c->get(Database::class)->getConnection();
    });

    // Repositories
    $container->set(GroupRepository::class, function (Container $c) {
        return new GroupRepository($c->get(PDO::class));
    });

    $container->set(MembershipRepository::class, function (Container $c) {
        return new MembershipRepository($c->get(PDO::class));
    });

    $container->set(InvitationRepository::class, function (Container $c) {
        return new InvitationRepository($c->get(PDO::class));
    });

    $container->set(MessageRepository::class, function (Container $c) {
        return new MessageRepository($c->get(PDO::class));
    });

    // Services
    $container->set(GroupService::class, function (Container $c) {
        return new GroupService(
            $c->get(PDO::class),
            $c->get(GroupRepository::class),
            $c->get(MembershipRepository::class),
            $c->get(InvitationRepository::class)
        );
    });

    $container->set(MessageService::class, function (Container $c) {
        return new MessageService(
            $c->get(GroupRepository::class),
            $c->get(MembershipRepository::class),
            $c->get(MessageRepository::class)
        );
    });

    $container->set(AuthMiddleware::class, function (Container $c) {
        return new AuthMiddleware($c->get(Database::class));
    });

    return $container;
};
