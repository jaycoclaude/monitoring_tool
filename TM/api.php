<?php
require_once 'data.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'getAllTasks') {
    echo json_encode(getTasks());
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>

