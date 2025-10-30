<?php
// data.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$current_staff = $_SESSION['user_id'];

if (!$current_staff) {
    // Not logged in or invalid session
    header('Location: ../index.php');
    exit();
}
function getDB() {
    global $pdo;
    return $pdo;
}


function getTasks(int $current_user_id): array {
    $db = getDB();

    // First, get the staff_id for the current user
    $stmt = $db->prepare("SELECT staff_id FROM tbl_staff WHERE user_id = :user_id AND staff_status = 1 LIMIT 1");
    $stmt->execute([':user_id' => $current_user_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        // No staff record found for this user
        return [];
    }

    $current_staff_id = $staff['staff_id'];

    $sql = "
        SELECT t.*,
               s1.staff_names AS assigned_by_name,
               s2.staff_names AS assigned_to_name
        FROM tbl_tasks t
        JOIN tbl_staff s1 ON t.assigned_by = s1.staff_id
        JOIN tbl_staff s2 ON t.assigned_to = s2.staff_id
        WHERE t.is_deleted = 0
          AND (t.assigned_by = :staff_id OR t.assigned_to = :staff_id)
        ORDER BY t.created_at DESC
    ";

    $params = [':staff_id' => $current_staff_id];

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tasks as &$task) {
            $task['attachments'] = $task['attachments']
                ? json_decode($task['attachments'], true)
                : [];
        }

        return $tasks;
    } catch (PDOException $e) {
        error_log('getTasks error: ' . $e->getMessage());
        return [];
    }
}

function getTaskById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT t.*, 
                                  s1.staff_names AS assigned_by_name,
                                  s2.staff_names AS assigned_to_name
                           FROM tbl_tasks t
                           JOIN tbl_staff s1 ON t.assigned_by = s1.staff_id
                           JOIN tbl_staff s2 ON t.assigned_to = s2.staff_id
                           WHERE t.task_id = ? AND t.is_deleted = 0");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($task) {
        $task['attachments'] = $task['attachments'] ? json_decode($task['attachments'], true) : [];
    }
    return $task;
}

function addTask($data) {
    $pdo = getDB();
    $attachments = json_encode($data['attachments'] ?? []);

    $stmt = $pdo->prepare("INSERT INTO tbl_tasks 
        (title, description, assigned_by, assigned_to, status, priority, due_date, attachments, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->execute([
        $data['title'],
        $data['description'],
        $data['assigned_by'],
        $data['assigned_to'],
        $data['status'] ?? 'pending',
        $data['priority'] ?? 'medium',
        $data['due_date'],
        $attachments
    ]);

    return $pdo->lastInsertId();
}

function updateTaskStatus($task_id, $status, $staff_id) {
    $pdo = getDB();
    $pdo->beginTransaction();

    // Update task
    $stmt = $pdo->prepare("UPDATE tbl_tasks SET status = ?, updated_at = NOW() WHERE task_id = ?");
    $stmt->execute([$status, $task_id]);

    // Log update
    $stmt = $pdo->prepare("INSERT INTO tbl_task_updates (task_id, staff_id, status_change, created_at) 
                           VALUES (?, ?, ?, NOW())");
    $stmt->execute([$task_id, $staff_id, $status]);

    $pdo->commit();
}

function getTaskUpdates($task_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT tu.*, u.user_email 
        FROM tbl_task_updates tu
        JOIN tbl_hm_users u ON tu.staff_id = u.user_id
        WHERE tu.task_id = ?
        ORDER BY tu.created_at DESC
    ");
    $stmt->execute([$task_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getAllStaff() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT staff_id, staff_names, staff_email FROM tbl_staff WHERE staff_status = 1 ORDER BY staff_names");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatDate($date) {
    return $date ? date('M d, Y', strtotime($date)) : '—';
}

function getStatusClass($status) {
    return match ($status) {
        'pending' => 'pending',
        'in_progress' => 'in-progress',
        'completed' => 'completed',
        'overdue' => 'overdue',
        default => 'pending',
    };
}

function getPriorityBadge($priority) {
    $labels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
    return $labels[$priority] ?? 'Medium';
}