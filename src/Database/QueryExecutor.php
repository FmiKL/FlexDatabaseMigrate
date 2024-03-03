<?php

namespace FlexDatabaseMigrate\Database;

use mysqli;
use mysqli_result;
use FlexDatabaseMigrate\Logger\Logger;

class QueryExecutor {
    public function __construct(
        private mysqli $connection
    ){}

    public function execute(string $query): bool|mysqli_result
    {
        $result = $this->connection->query($query);
        if (!$result) {
            Logger::log("Error executing query: {$this->connection->error}");
            return false;
        }
        return $result;
    }
}
