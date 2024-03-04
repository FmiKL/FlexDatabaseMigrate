<?php

namespace FlexDatabaseMigrate\Migration;

use FlexDatabaseMigrate\Database\DatabaseConnector;
use FlexDatabaseMigrate\Database\QueryExecutor;
use FlexDatabaseMigrate\Logger\Logger;

class DataMigrator {
    public function __construct(
        private DatabaseConnector $sourceDb,
        private DatabaseConnector $destinationDb,
        private string $keyColumn
    ) {}

    public function updateColumn(string $sourceColumn, string $destinationColumn): void
    {
        list($sourceTable, $sourceColumnName) = explode('.', $sourceColumn);
        list($destinationTable, $destinationColumnName) = explode('.', $destinationColumn);

        $query = "SELECT $this->keyColumn, $sourceColumnName FROM $sourceTable";
        $sourceExecutor = new QueryExecutor($this->sourceDb->getConnection());
        $sourceResult = $sourceExecutor->execute($query);

        if ($sourceResult === false) {
            Logger::log("Failed to fetch data from source table $sourceTable for column update.");
            return;
        }

        $totalAffectedRows = 0;
        $hasError = false;

        while ($row = $sourceResult->fetch_assoc()) {
            $keyValue = $row[$this->keyColumn];
            $columnValue = $row[$sourceColumnName];
            $updateQuery = "UPDATE $destinationTable SET $destinationColumnName = '" . $this->destinationDb->getConnection()->escape_string($columnValue) . "' WHERE $this->keyColumn = '" . $this->destinationDb->getConnection()->escape_string($keyValue) . "'";
            $destinationExecutor = new QueryExecutor($this->destinationDb->getConnection());
            if ($destinationExecutor->execute($updateQuery)) {
                $totalAffectedRows += $this->destinationDb->getConnection()->affected_rows;
            } else {
                $hasError = true;
                Logger::log("Failed to update $destinationColumnName in $destinationTable for key $keyValue.");
            }
        }

        if (!$hasError) {
            Logger::log("Update operation completed for $destinationTable.$destinationColumnName. Total rows affected: $totalAffectedRows.");
        }
    }

    public function migrateData(array $columnMapping): void
    {
        $sourceTableColumns = array_keys($columnMapping);
        $sourceTable = explode('.', $sourceTableColumns[0])[0];

        $destinationTableColumns = array_values($columnMapping);
        $destinationTable = explode('.', $destinationTableColumns[0])[0];

        $sourceColumns = implode(', ', array_map(function ($item) { return explode('.', $item)[1]; }, $sourceTableColumns));
        $sourceQuery = "SELECT $this->keyColumn, $sourceColumns FROM $sourceTable";
        $sourceExecutor = new QueryExecutor($this->sourceDb->getConnection());
        $sourceResult = $sourceExecutor->execute($sourceQuery);

        if ($sourceResult === false) {
            Logger::log("Failed to fetch data from source table $sourceTable.");
            return;
        }

        $totalAffectedRows = 0;
        $hasError = false;

        while ($row = $sourceResult->fetch_assoc()) {
            $columns = [];
            $values = [];
            foreach ($columnMapping as $sourceCol => $destCol) {
                $colName = explode('.', $destCol)[1];
                $columns[] = $colName;
                $values[] = "'" . $this->destinationDb->getConnection()->escape_string($row[explode('.', $sourceCol)[1]]) . "'";
            }
            $insertQuery = "INSERT INTO $destinationTable (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ") ON DUPLICATE KEY UPDATE ";
            $updates = [];
            foreach ($columns as $column) {
                $updates[] = "$column=VALUES($column)";
            }
            $insertQuery .= implode(', ', $updates);

            $destinationExecutor = new QueryExecutor($this->destinationDb->getConnection());
            if ($destinationExecutor->execute($insertQuery)) {
                $totalAffectedRows += $this->destinationDb->getConnection()->affected_rows;
            } else {
                $hasError = true;
                Logger::log("Failed to migrate data to $destinationTable from $sourceTable.");
            }
        }

        if (!$hasError) {
            Logger::log("Data migration completed to $destinationTable. Total rows affected: $totalAffectedRows.");
        }
    }
}
