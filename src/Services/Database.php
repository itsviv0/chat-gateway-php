<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

class Database
{
    private ?PDO $connection = null;

    public function __construct(private string $dbPath)
    {
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
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return $this->connection;
    }
}
