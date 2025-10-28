<?php
require_once 'config.php';


function getTasks() {
    $conn = getDB();
    $result = $conn->query("SELECT * FROM tasks ORDER BY id DESC");
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        // Decode attachments JSON string to array
        $row['attachments'] = json_decode($row['attachments'], true) ?: [];
        $tasks[] = $row;
    }
    
    return $tasks;
}

function getTaskById($id) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Decode attachments JSON string to array
        $row['attachments'] = json_decode($row['attachments'], true) ?: [];
        return $row;
    }
    
    return null;
}

function addTask($task) {
    $conn = getDB();
    
    // Encode attachments array to JSON string
    $attachments = json_encode($task['attachments'] ?? []);
    
    $stmt = $conn->prepare("INSERT INTO tasks (title, description, `from`, `to`, status, dueDate, createdAt, attachments, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", 
        $task['title'], 
        $task['description'], 
        $task['from'], 
        $task['to'], 
        $task['status'], 
        $task['dueDate'], 
        $task['createdAt'], 
        $attachments, 
        $task['priority']
    );
    
    if ($stmt->execute()) {
        $task['id'] = $conn->insert_id;
        return $task;
    }
    
    return null;
}

function updateTaskStatus($id, $status) {
    $conn = getDB();
    $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
}

function updateTask($id, $task) {
    $conn = getDB();
    
    // Encode attachments array to JSON string
    $attachments = json_encode($task['attachments'] ?? []);
    
    $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, `from` = ?, `to` = ?, status = ?, dueDate = ?, createdAt = ?, attachments = ?, priority = ? WHERE id = ?");
    $stmt->bind_param("sssssssssi", 
        $task['title'], 
        $task['description'], 
        $task['from'], 
        $task['to'], 
        $task['status'], 
        $task['dueDate'], 
        $task['createdAt'], 
        $attachments, 
        $task['priority'],
        $id
    );
    
    return $stmt->execute();
}

function deleteTask($id) {
    $conn = getDB();
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function getStatusClass($status) {
    return $status === 'pending' ? 'pending' : ($status === 'in-progress' ? 'in-progress' : 'completed');
}

function getPriorityBadge($priority) {
    return $priority === 'high' ? 'High' : ($priority === 'medium' ? 'Medium' : 'Low');
}

function searchTasks($searchTerm, $tasks) {
    if (empty($searchTerm)) return $tasks;
    $st = strtolower($searchTerm);
    return array_filter($tasks, function($t) use ($st) {
        return stripos($t['title'], $st) !== false || stripos($t['description'], $st) !== false || stripos($t['from'], $st) !== false || stripos($t['to'], $st) !== false;
    });
}

function getCurrentUser() {
    return "Alice Admin";
}
?>

