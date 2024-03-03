<?php

namespace FlexDatabaseMigrate\Database;

use mysqli;

class DatabaseConnector {
    private mysqli $connection;

    public function __construct(array $config)
    {
        $this->connection = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
        if ($this->connection->connect_error) {
            die('Connection failed: ' . $this->connection->connect_error);
        }
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }

    public function escapeString(string $value): string
    {
        return $this->connection->real_escape_string($value);
    }
}
