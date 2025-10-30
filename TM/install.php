<?php
// Installation script for Task System database
require_once '../includes/config.php';

echo "<h2>Installing Task System Database</h2>";

// First, connect without selecting a database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql)) {
    echo "<p>✓ Database '" . DB_NAME . "' created successfully (or already exists)</p>";
} else {
    echo "<p>✗ Error creating database: " . $conn->error . "</p>";
}

// Select database
$conn->select_db(DB_NAME);

// Drop existing table if it exists (for clean installation)
$dropTable = "DROP TABLE IF EXISTS tasks";
if ($conn->query($dropTable)) {
    echo "<p>✓ Old table dropped (if existed)</p>";
}

// Create tasks table
$sql = "CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    `from` VARCHAR(100) NOT NULL,
    `to` VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    dueDate DATE NOT NULL,
    createdAt DATE NOT NULL,
    attachments TEXT,
    priority VARCHAR(20) NOT NULL DEFAULT 'medium',
    INDEX idx_status (status),
    INDEX idx_from (`from`),
    INDEX idx_to (`to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "<p>✓ Table 'tasks' created successfully</p>";
} else {
    echo "<p>✗ Error creating table: " . $conn->error . "</p>";
}

// Insert sample data
echo "<p>Inserting sample data...</p>";

$sampleTasks = [
    "('Design Homepage', 'Create Elegant-inspired homepage with modern UI components', 'Alice Admin', 'Bob Developer', 'pending', '2025-11-30', '2025-10-25', '[\"design-brief.pdf\"]', 'high')",
    "('Prepare Weekly Report', 'Weekly status report for management', 'Bob Developer', 'Alice Admin', 'in-progress', '2025-11-05', '2025-10-28', '[\"template.docx\"]', 'medium')",
    "('Database Optimization', 'Optimize database queries and indexes', 'Charlie Manager', 'Bob Developer', 'completed', '2025-10-20', '2025-10-15', '[\"performance-report.pdf\", \"queries.sql\"]', 'high')",
    "('Client Presentation', 'Prepare slides and demo for client meeting', 'Alice Admin', 'Charlie Manager', 'pending', '2025-12-10', '2025-10-29', '[]', 'medium')"
];

$sql = "INSERT INTO tasks (title, description, `from`, `to`, status, dueDate, createdAt, attachments, priority) VALUES " . implode(', ', $sampleTasks);

if ($conn->query($sql)) {
    echo "<p>✓ Sample data inserted successfully</p>";
} else {
    echo "<p>✗ Error inserting sample data: " . $conn->error . "</p>";
}

echo "<h3>Installation completed!</h3>";
echo "<p><a href='index.php'>Go to Task System</a></p>";

$conn->close();
?>
