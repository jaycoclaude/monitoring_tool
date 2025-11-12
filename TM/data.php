<?php
// data.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '/../includes/logger.php';
$current_staff = $_SESSION['user_id'];

if (!$current_staff) {
    // Not logged in or invalid session
    header('Location: ../index.php');
    exit();
}
function getDB()
{
    global $pdo;
    return $pdo;
}




function getTasks(int $current_user_id): array
{
    $db = getDB();

    log_message("🔍 [getTasks] Starting for user_id={$current_user_id}", 'tasks');

    // Step 1: Get current user's staff_id
    $stmt = $db->prepare("SELECT staff_id FROM tbl_staff WHERE user_id = :user_id AND staff_status = 1 LIMIT 1");
    $stmt->execute([':user_id' => $current_user_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_staff_id = $staff['staff_id'] ?? null;

    if (!$current_staff_id) {
        log_message("⚠️ [getTasks] No active staff record found for user_id={$current_user_id}", 'tasks');
        return [];
    }

    // Step 2: Fetch tasks created or assigned to the user
    $sql = "
        SELECT 
            t.*,
            s1.staff_names AS assigned_by_name,
            s2.staff_names AS assigned_to_name,
            u1.user_email AS assigned_by_email,
            s2.staff_email AS assigned_to_email
        FROM tbl_tasks t
        LEFT JOIN tbl_hm_users u1 ON t.assigned_by = u1.user_id
        LEFT JOIN tbl_staff s1 ON u1.user_id = s1.user_id
        LEFT JOIN tbl_staff s2 ON t.assigned_to = s2.staff_id
        WHERE t.is_deleted = 0
          AND (t.assigned_by = :user_id OR t.assigned_to = :staff_id)
        ORDER BY t.created_at DESC
    ";

    $params = [
        ':user_id' => $current_user_id,
        ':staff_id' => $current_staff_id
    ];

    log_message("📘 [getTasks] Executing SQL with params: " . json_encode($params), 'tasks');

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        log_message("📦 [getTasks] Retrieved " . count($tasks) . " tasks for user_id={$current_user_id}", 'tasks');

        // Step 3: Process tasks
        foreach ($tasks as &$task) {
            $task['assigned_by_name'] = $task['assigned_by_name'] ?? 'Unknown';
            $task['assigned_to_name'] = $task['assigned_to_name'] ?? 'Unknown';
            $task['assigned_by_email'] = $task['assigned_by_email'] ?? 'Unknown';
            $task['assigned_to_email'] = $task['assigned_to_email'] ?? 'Unknown';
            $task['attachments'] = !empty($task['attachments'])
                ? json_decode($task['attachments'], true) ?: []
                : [];
        }

        log_message("✅ [getTasks] Completed successfully for user_id={$current_user_id}", 'tasks');
        return $tasks;
    } catch (PDOException $e) {
        log_message("❌ [getTasks] SQL Error: " . $e->getMessage(), 'tasks');
        return [];
    }
}
function getTaskById($id)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT t.*, 
                                  s1.staff_names AS assigned_by_name,
                                  s2.staff_names AS assigned_to_name,
                                  u1.user_email AS assigned_by_email,
                                  s2.staff_email AS assigned_to_email
                           FROM tbl_tasks t
                           LEFT JOIN tbl_hm_users u1 ON t.assigned_by = u1.user_id
                           LEFT JOIN tbl_staff s1 ON u1.user_id = s1.user_id
                           LEFT JOIN tbl_staff s2 ON t.assigned_to = s2.staff_id
                           WHERE t.task_id = ? AND t.is_deleted = 0");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($task) {
        $task['attachments'] = $task['attachments'] ? json_decode($task['attachments'], true) : [];
        // Add fallbacks for missing data
        $task['assigned_by_name'] = $task['assigned_by_name'] ?? 'Unknown';
        $task['assigned_to_name'] = $task['assigned_to_name'] ?? 'Unknown';
    }
    return $task;
}
function addTask($data)
{
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

function updateTaskStatus($task_id, $status, $staff_id)
{
    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE tbl_tasks SET status = ?, updated_at = NOW() WHERE task_id = ?");
        $stmt->execute([$status, $task_id]);

        $stmt = $pdo->prepare("
            INSERT INTO tbl_task_updates (task_id, staff_id, status_change, comment, created_at)
            VALUES (?, ?, ?, '', NOW())
        ");
        $stmt->execute([$task_id, $staff_id, $status]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();

        // Add this line temporarily to debug if needed:
        error_log('updateTaskStatus error: ' . $e->getMessage());

        return false;
    }
}


function getTaskUpdates($task_id)
{
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


function getAllStaff()
{
    $pdo = getDB();
    $stmt = $pdo->query("SELECT staff_id, staff_names, staff_email FROM tbl_staff WHERE staff_status = 1 ORDER BY staff_names");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatDate($date)
{
    return $date ? date('M d, Y', strtotime($date)) : '—';
}

function getStatusClass($status)
{
    return match ($status) {
        'pending' => 'pending',
        'in_progress' => 'in-progress',
        'completed' => 'completed',
        'overdue' => 'overdue',
        default => 'pending',
    };
}

function getPriorityBadge($priority)
{
    $labels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
    return $labels[$priority] ?? 'Medium';
}

/* --------------------------------------------------------------
   1. Get ALL tasks for a staff member (no filters)
   -------------------------------------------------------------- */
function getTasksForStaff(int $staff_id): array
{
    $db = getDB();

    $sql = "
        SELECT
            t.*,
            s1.staff_names AS assigned_by_name,
            s2.staff_names AS assigned_to_name,
            u1.user_email   AS assigned_by_email,
            s2.staff_email  AS assigned_to_email
        FROM tbl_tasks t
        LEFT JOIN tbl_hm_users u1 ON t.assigned_by = u1.user_id
        LEFT JOIN tbl_staff s1 ON u1.user_id = s1.user_id
        LEFT JOIN tbl_staff s2 ON t.assigned_to = s2.staff_id
        WHERE t.is_deleted = 0
          AND t.assigned_to = :staff_id
        ORDER BY t.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':staff_id' => $staff_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['attachments'] = $row['attachments'] ? json_decode($row['attachments'], true) : [];
    }
    return $rows;
}

function updateTask($id, $data): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        UPDATE tbl_tasks SET
            title = :title,
            description = :description,
            priority = :priority,
            status = :status,
            due_date = :due_date,
            assigned_to = :assigned_to,
            updated_at = NOW()
        WHERE task_id = :id
    ");
    return $stmt->execute([
        ':id' => $id,
        ':title' => $data['title'],
        ':description' => $data['description'],
        ':priority' => $data['priority'],
        ':status' => $data['status'],
        ':due_date' => $data['due_date'],
        ':assigned_to' => $data['assigned_to']
    ]);
}

function deleteTask($id): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE tbl_tasks SET is_deleted = 1 WHERE task_id = :id");
    return $stmt->execute([':id' => $id]);
}

/* --------------------------------------------------------------
   2. Export to CSV
   -------------------------------------------------------------- */
function exportCSV(array $tasks, string $staff_name)
{
    $filename = "tasks_" . preg_replace('/[^a-zA-Z0-9]/', '_', $staff_name) . "_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'ID',
        'Title',
        'Description',
        'Assigned By',
        'Status',
        'Priority',
        'Due Date',
        'Created At',
        'Completed At',
        'Attachments Count'
    ]);

    foreach ($tasks as $t) {
        fputcsv($out, [
            $t['task_id'],
            $t['title'],
            $t['description'],
            $t['assigned_by_name'],
            $t['status'],
            $t['priority'],
            $t['due_date'],
            $t['created_at'],
            $t['completed_at'] ?? '',
            count($t['attachments'])
        ]);
    }
    fclose($out);
    exit;
}

/* --------------------------------------------------------------
   3. Export to PDF using MPDF (same as your other report)
   -------------------------------------------------------------- */
function exportPDF(array $tasks, string $staff_name)
{
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_top'    => 25,
        'margin_bottom' => 25,
        'margin_left'   => 20,
        'margin_right'  => 20,
    ]);

    $mpdf->SetTitle('Staff Task Report – ' . $staff_name);
    $mpdf->SetAuthor('Task Manager');
    $mpdf->SetCreator('Task Manager');

    $logoPath = __DIR__ . '/assets/Logo.png';
    $logoHtml = file_exists($logoPath) ? '<img src="' . $logoPath . '" style="width:120px; height:auto; margin-bottom:10px;">' : '';

    $html = '
    <!DOCTYPE html>
    <html><head><meta charset="utf-8">
    <style>
        body {font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:11pt; margin:0; padding:0;}
        .header {text-align:center; margin-bottom:30px;}
        .title {font-size:18pt; font-weight:700; margin:5px 0;}
        .info {font-size:10pt; color:#6b7280;}
        .task-entry {border-left:4px solid #007aff; background:#f8f9fa; padding:12px 16px; margin-bottom:16px; border-radius:8px;}
        .task-title {font-weight:600; font-size:12pt; margin-bottom:6px;}
        .task-meta {font-size:9pt; color:#555; margin-bottom:6px;}
        .task-desc {font-size:10pt; margin-bottom:6px; color:#333;}
        .badge {padding:2px 6px; border-radius:6px; color:#fff; font-size:0.75em;}
        .badge-pending {background:#856404;}
        .badge-in_progress {background:#0c5460;}
        .badge-completed {background:#155724;}
        .badge-overdue {background:#721c24;}
        .badge-low {background:#6c757d;}
        .badge-medium {background:#0f5e8a;}
        .badge-high {background:#e0a800;}
        .badge-urgent {background:#c82333;}
        .no-data {text-align:center; padding:40px; color:#888; font-style:italic;}
    </style>
    </head><body>

    <div class="header">
        ' . $logoHtml . '
        <div class="title">STAFF TASK REPORT</div>
        <div class="info"><strong>Staff:</strong> ' . htmlspecialchars($staff_name) . ' | <strong>Generated:</strong> ' . date('F j, Y \a\t H:i') . '</div>
    </div>';

    if (empty($tasks)) {
        $html .= '<div class="no-data">No tasks found for this staff member.</div>';
    } else {
        foreach ($tasks as $t) {
            $statusClass = 'badge-' . str_replace('_', '_', $t['status']);
            $priorityClass = 'badge-' . $t['priority'];
            $html .= '
            <div class="task-entry">
                <div class="task-title">' . htmlspecialchars($t['title']) . '</div>
                <div class="task-meta"><strong>Assigned By:</strong> ' . htmlspecialchars($t['assigned_by_name']) . ' | 
                <strong>Status:</strong> <span class="badge ' . $statusClass . '">' . ucfirst(str_replace('_', ' ', $t['status'])) . '</span> | 
                <strong>Priority:</strong> <span class="badge ' . $priorityClass . '">' . ucfirst($t['priority']) . '</span></div>
                <div class="task-meta"><strong>Due Date:</strong> ' . ($t['due_date'] ? date('M j, Y', strtotime($t['due_date'])) : '—') . ' | 
                <strong>Assigned:</strong> ' . date('M j, Y', strtotime($t['created_at'])) . ' | 
                <strong>Completed:</strong> ' . ($t['completed_at'] ? date('M j, Y', strtotime($t['completed_at'])) : '—') . '</div>
                ' . (!empty($t['description']) ? '<div class="task-desc"><strong>Description:</strong> ' . nl2br(htmlspecialchars($t['description'])) . '</div>' : '') . '
                <div class="task-meta"><strong>Attachments:</strong> ' . count($t['attachments']) . ' file(s)</div>
            </div>';
        }
    }

    $html .= '</body></html>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('staff_tasks_' . preg_replace('/[^a-zA-Z0-9]/', '_', $staff_name) . '_' . date('Ymd_His') . '.pdf', 'D');
    exit;
}


/* --------------------------------------------------------------
   4. Unified Export Dispatcher
   -------------------------------------------------------------- */
function exportStaffTasks(int $staff_id, string $format, array $tasks)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT staff_names FROM tbl_staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    $name = $stmt->fetchColumn() ?: 'unknown';

    if ($format === 'csv') {
        exportCSV($tasks, $name);
    } elseif ($format === 'pdf') {
        exportPDF($tasks, $name);
    }
}

// ---------------------------------------------------------------
//  NEW STATUS (add to the ENUM in the DB – see note at the end)
// ---------------------------------------------------------------
define('STATUS_REVIEW', 'review');

// ---------------------------------------------------------------
//  1. Submit for Review (assignee)
// ---------------------------------------------------------------
function submitTaskForReview(int $task_id, int $staff_id, string $comment, array $newFiles = []): bool
{
    $pdo = getDB();
    try {
        $pdo->beginTransaction();

        // 1. Save comment + files in tbl_task_updates
        $report = !empty($newFiles) ? json_encode($newFiles) : null;
        $stmt = $pdo->prepare("
            INSERT INTO tbl_task_updates
                (task_id, staff_id, comment, report_filename, status_change, created_at)
            VALUES
                (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$task_id, $staff_id, $comment, $report, STATUS_REVIEW]);

        // 2. Move uploaded files to /uploads/
        if (!empty($newFiles)) {
            $dir = __DIR__ . '/uploads/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            foreach ($_FILES['review_attachments']['name'] as $k => $name) {
                if ($_FILES['review_attachments']['error'][$k] === 0) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext), ['pdf','doc','docx','jpg','png','zip'])) {
                        $file = time() . "_$k.$ext";
                        move_uploaded_file($_FILES['review_attachments']['tmp_name'][$k], $dir . $file);
                        // file name already stored in JSON above
                    }
                }
            }
        }

        // 3. Change task status
        $stmt = $pdo->prepare("UPDATE tbl_tasks SET status = ?, updated_at = NOW() WHERE task_id = ?");
        $stmt->execute([STATUS_REVIEW, $task_id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("submitTaskForReview: " . $e->getMessage());
        return false;
    }
}

// ---------------------------------------------------------------
//  2. Approve → completed (assigner)
// ---------------------------------------------------------------
function approveTask(int $task_id, int $staff_id): bool
{
    return changeTaskStatus($task_id, 'completed', $staff_id);
}

// ---------------------------------------------------------------
//  3. Return for re-do (assigner) → in_progress + reason in comment
// ---------------------------------------------------------------
function returnTaskForRedo(int $task_id, int $staff_id, string $reason): bool
{
    $pdo = getDB();
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO tbl_task_updates
                (task_id, staff_id, comment, status_change, created_at)
            VALUES
                (?, ?, ?, 'in_progress', NOW())
        ");
        $stmt->execute([$task_id, $staff_id, $reason]);

        $stmt = $pdo->prepare("UPDATE tbl_tasks SET status = 'in_progress', updated_at = NOW() WHERE task_id = ?");
        $stmt->execute([$task_id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// ---------------------------------------------------------------
//  Helper – generic status change (used by approve)
// ---------------------------------------------------------------
function changeTaskStatus(int $task_id, string $newStatus, int $staff_id): bool
{
    $pdo = getDB();
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO tbl_task_updates
                (task_id, staff_id, status_change, created_at)
            VALUES (?, ?, ?, NOW())");
        $stmt->execute([$task_id, $staff_id, $newStatus]);

        $stmt = $pdo->prepare("UPDATE tbl_tasks SET status = ?, updated_at = NOW() WHERE task_id = ?");
        $stmt->execute([$newStatus, $task_id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// ---------------------------------------------------------------
//  4. Get the latest review entry (comment + files)
// ---------------------------------------------------------------
function getLatestReview(int $task_id): ?array
{
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT tu.*, s.staff_names AS submitted_by_name
        FROM tbl_task_updates tu
        JOIN tbl_staff s ON tu.staff_id = s.staff_id
        WHERE tu.task_id = ? AND tu.status_change = ?
        ORDER BY tu.created_at DESC LIMIT 1
    ");
    $stmt->execute([$task_id, STATUS_REVIEW]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    $row['attachments'] = $row['report_filename']
        ? json_decode($row['report_filename'], true)
        : [];
    return $row;
}

