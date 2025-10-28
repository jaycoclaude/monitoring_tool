<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'task_system');

// Create database connection
function getDB() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to utf8mb4 for better character support
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

// Close database connection
function closeDB() {
    $conn = getDB();
    if ($conn) {
        $conn->close();
    }
}
?>
