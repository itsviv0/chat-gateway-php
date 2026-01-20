<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

return [
    'paths' => [
        'migrations' => 'database/migrations',
        'seeds' => 'database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => $_ENV['APP_ENV'] ?? 'development',
        'development' => [
            'adapter' => 'sqlite',
            'name' => 'database/database', // Base path; actual filename is determined by the suffix below
            'suffix' => '.sqlite', // Force .sqlite extension
        ],
        'production' => [
            'adapter' => 'sqlite',
            'name' => 'database/database',
            'suffix' => '.sqlite',
        ],
        'testing' => [
            'adapter' => 'sqlite',
            'memory' => true,
        ],
    ],
    'version_order' => 'creation',
];
