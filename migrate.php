<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
require 'config.php';

use FlexDatabaseMigrate\Database\DatabaseConnector;
use FlexDatabaseMigrate\Migration\DataMigrator;

$sourceDb = new DatabaseConnector(DB_SOURCE);
$destinationDb = new DatabaseConnector(DB_TARGET);

$migrator = new DataMigrator($sourceDb, $destinationDb, 'id');

// This method migrates the data from an entire table to another, creating the destination table if it does not exist and updating the fields if it does.
$migrator->migrateData([
    'source_table.column1' => 'destination_table.columna',
    'source_table.column2' => 'destination_table.columnb',
]);
