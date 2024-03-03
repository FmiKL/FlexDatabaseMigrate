<?php

namespace FlexDatabaseMigrate\Logger;

class Logger {
    public static function log(string $message): void
    {
        file_put_contents('migrate.log', date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
    }
}
