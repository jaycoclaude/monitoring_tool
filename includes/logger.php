<?php
function log_message(string $message, string $context = 'app'): void {
    $logDir = __DIR__ . '/../logs';
    $logFile = "{$logDir}/{$context}.log";

    // Ensure log directory exists
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    // Format timestamped message
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";

    // Append to file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
