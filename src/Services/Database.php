<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class Database
{
    private ?PDO $connection = null;

    public function __construct(
        private string $dbPath,
        private ?LoggerInterface $logger = null
    ) {
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            try {
                $this->connection = new PDO(
                    'sqlite:' . $this->dbPath,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );

                // Enable foreign keys for SQLite
                $this->connection->exec('PRAGMA foreign_keys = ON;');
            } catch (PDOException $e) {
                $this->logger?->error('Database connection failed: ' . $e->getMessage());
                throw new \RuntimeException('Database connection failed');
            }
        }

        return $this->connection;
    }
}
