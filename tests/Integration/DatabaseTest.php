<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\Database;
use PHPUnit\Framework\TestCase;
use PDOException;

class DatabaseTest extends TestCase
{
    private Database $database;

    protected function setUp(): void
    {
        // Use in-memory database for checking constraints
        $this->database = new Database(':memory:');
    }

    public function testForeignKeysAreEnforced(): void
    {
        $pdo = $this->database->getConnection();

        // Create parent and child tables
        $pdo->exec("CREATE TABLE parents (id INTEGER PRIMARY KEY, name TEXT)");
        $pdo->exec("CREATE TABLE children (id INTEGER PRIMARY KEY, parent_id INTEGER, FOREIGN KEY(parent_id) REFERENCES parents(id))");

        // Insert parent
        $pdo->exec("INSERT INTO parents (id, name) VALUES (1, 'Parent')");

        // Insert valid child
        $pdo->exec("INSERT INTO children (id, parent_id) VALUES (1, 1)");

        // Assert that we can query it back
        $stmt = $pdo->query("SELECT * FROM children WHERE id = 1");
        $this->assertNotFalse($stmt->fetch());

        // Try to insert invalid child (non-existent parent)
        $this->expectException(PDOException::class);
        // SQLite error code for constraint violation matches generic PDO exception logic in our service, 
        // but PDO execution throws PDOException directly on query failure if ERRMODE is EXCEPTION.

        try {
            $pdo->exec("INSERT INTO children (id, parent_id) VALUES (2, 999)");
        } catch (PDOException $e) {
            // Verify it is a constraint violation
            $this->assertStringContainsString('FOREIGN KEY constraint failed', $e->getMessage());
            throw $e;
        }
    }
}
