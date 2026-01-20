<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use App\Services\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Create Container and configure it
$container = new Container();
$containerConfig = require __DIR__ . '/../config/container.php';
$containerConfig($container);

try {
    $db = $container->get(Database::class);
    $pdo = $db->getConnection();
    echo "Database connection successful!\n";

    // Test Foreign Key Constraints
    try {
        // Attempt to create a group with a non-existent user (id: 99999)
        // Group needs created_by which references users(id)
        $pdo->exec("INSERT INTO groups (name, created_by) VALUES ('Test Group', 99999)");
        echo "FAIL: Foreign key constraint NOT enforced!\n";
    } catch (\PDOException $e) {
        if (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed')) {
            echo "PASS: Foreign key constraint enforced.\n";
        } else {
            echo "FAIL: Unexpected error: " . $e->getMessage() . "\n";
        }
    }

} catch (\Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}
