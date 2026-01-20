<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Database;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PDO;

class DatabaseTest extends TestCase
{
    public function testGetConnectionReturnsPdoInstance(): void
    {
        $database = new Database(':memory:');
        $pdo = $database->getConnection();

        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testConnectionFailureLogsErrorAndThrowsException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        // Expect error to be logged
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Database connection failed'));

        // Use an invalid path/DSN that will definitely fail for SQLite
        // SQLite is pretty robust, but passing a directory as a file usually fails 
        // or using a read-only path.
        // Actually, for SQLite, failing the constructor is hard unless the path is unwritable.
        // Let's use a trick: 'sqlite:/root/invalid_path/db.sqlite' (assuming we are not root)

        // Use an invalid/unwritable database path to force a connection failure
        $database = new Database('/root/invalid_path/db.sqlite', $logger);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed');

        $database->getConnection();
    }

    public function testConnectionIsReused(): void
    {
        $database = new Database(':memory:');
        $pdo1 = $database->getConnection();
        $pdo2 = $database->getConnection();

        $this->assertSame($pdo1, $pdo2);
    }

    public function testPdoAttributesAreConfigured(): void
    {
        $database = new Database(':memory:');
        $pdo = $database->getConnection();

        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertEquals(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    public function testDatabaseFileIsCreated(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_db_' . uniqid() . '.sqlite';

        // Ensure file does not exist
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        try {
            $database = new Database($tempFile);
            $database->getConnection();

            $this->assertFileExists($tempFile);
        } finally {
            // Cleanup
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
