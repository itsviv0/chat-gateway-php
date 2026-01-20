<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\Database;
use PDO;

class DatabaseTest extends TestCase
{
    private string $testDbPath;

    protected function setUp(): void
    {
        $this->testDbPath = sys_get_temp_dir() . '/test_chat_' . uniqid() . '.db';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
    }

    public function testDatabaseConnectionIsCreated(): void
    {
        $database = new Database($this->testDbPath);
        $connection = $database->getConnection();

        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testConnectionIsReused(): void
    {
        $database = new Database($this->testDbPath);

        $connection1 = $database->getConnection();
        $connection2 = $database->getConnection();

        $this->assertSame($connection1, $connection2, 'Connection should be reused (singleton pattern)');
    }

    public function testPdoAttributesAreSetCorrectly(): void
    {
        $database = new Database($this->testDbPath);
        $connection = $database->getConnection();

        $this->assertEquals(
            PDO::ERRMODE_EXCEPTION,
            $connection->getAttribute(PDO::ATTR_ERRMODE),
            'Error mode should be set to exception'
        );

        $this->assertEquals(
            PDO::FETCH_ASSOC,
            $connection->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE),
            'Default fetch mode should be associative array'
        );
    }

    public function testDatabaseFileIsCreated(): void
    {
        $database = new Database($this->testDbPath);
        $database->getConnection();

        $this->assertFileExists($this->testDbPath, 'SQLite database file should be created');
    }

    public function testConnectionCanExecuteQueries(): void
    {
        $database = new Database($this->testDbPath);
        $connection = $database->getConnection();

        // Create a test table
        $connection->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');

        // Insert data
        $stmt = $connection->prepare('INSERT INTO test (name) VALUES (?)');
        $stmt->execute(['test_name']);

        // Query data
        $stmt = $connection->query('SELECT * FROM test');
        $result = $stmt->fetch();

        $this->assertEquals('test_name', $result['name']);
    }

    public function testInvalidPathThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed');

        // Try to create database in invalid directory
        $database = new Database('/invalid/path/that/does/not/exist/test.db');
        $database->getConnection();
    }
}
