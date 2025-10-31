<?php
require_once '../includes/auth.php';
require_once 'data.php';
require_once 'vendor/autoload.php';

use Mpdf\Mpdf;

// Current user
$current_user_id = $_SESSION['user_id'];
if (!$current_user_id) {
    header('Location: ../index.php');
    exit();
}

// Get tasks
$tasks = getTasks($current_user_id);

// Current staff info
$db = getDB();
$stmt = $db->prepare("SELECT staff_id, staff_names, staff_email FROM tbl_staff WHERE user_id = :user_id AND staff_status = 1 LIMIT 1");
$stmt->execute([':user_id' => $current_user_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
$current_staff_name  = $staff['staff_names'] ?? '';
$current_staff_email = $staff['staff_email'] ?? $_SESSION['staff_email'] ?? '';

// Filter completed tasks
$completedTasks = array_filter($tasks, fn($t) => $t['assigned_to_name'] === $current_staff_name && $t['status'] == 'completed');

// Generate PDF
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 25,
    'margin_bottom' => 25,
    'margin_left' => 20,
    'margin_right' => 20,
]);

$mpdf->SetTitle('Completed Tasks Report');
$mpdf->SetAuthor('RWANDA FDA');
$mpdf->SetCreator('RWANDA FDA System');

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Completed Tasks Report</title>
<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: #1f2937;
        font-size: 11pt;
        margin: 0;
        padding: 0;
        background: #f9fafb;
    }
    .page { padding: 25px; }
    .header { text-align: center; margin-bottom: 25px; }
    .institution-name {
        font-size: 18pt;
        font-weight: 700;
        margin: 5px 0 5px;
    }
    .info {
        font-size: 10pt;
        color: #6b7280;
        margin: 2px 0;
    }
    .section { margin-top: 30px; }
    .section-title {
        font-size: 13pt;
        font-weight: 600;
        color: #2563eb;
        display: flex;
        align-items: center;
        margin-bottom: 12px;
    }
    .section-title svg {
        width: 18px; height: 18px;
        margin-right: 8px;
        fill: #2563eb;
    }
    .card {
        background: #ffffff;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .card h3 {
        margin: 0 0 5px;
        font-size: 12pt;
        color: #1e3a8a;
    }
    .card p {
        margin: 2px 0;
        font-size: 10pt;
        color: #374151;
    }
    .card .meta { font-size: 9pt; color: #6b7280; }
    .status-text { color: #2563eb; font-weight: 600; }
    .no-data { text-align: center; padding: 30px; color: #6b7280; font-style: italic; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <img src="' . __DIR__ . '/assets/Logo.png" alt="RWANDA FDA Logo" style="width:150px; height:150px; display:inline-block; margin-bottom:10px;">
        <div class="institution-name">COMPLETED TASKS REPORT</div>
        <div class="info"><strong>Prepared For:</strong> ' . htmlspecialchars($current_staff_name) . ' | <strong>Date:</strong> ' . date('F j, Y') . '</div>
    </div>

    <div class="section">
        <div class="section-title">
        </div>';

if (empty($completedTasks)) {
    $html .= '<div class="no-data">No completed tasks found.</div>';
} else {
    $i = 1; // numbering
    foreach ($completedTasks as $task) {
        $assignedBy = ($task['assigned_by_name'] === $current_staff_name) ? 'Me' : htmlspecialchars($task['assigned_by_name']);
        $assignedTo = htmlspecialchars($task['assigned_to_name']);
        $assignedAt = date('M j, Y', strtotime($task['created_at']));
        $completedAt = date('M j, Y', strtotime($task['completed_at'] ?? $task['updated_at']));
        $statusText = ucfirst(str_replace('_', ' ', $task['status']));

        $html .= '
        <div class="card">
            <h3>' . $i++ . '. ' . htmlspecialchars($task['title']) . '</h3>
            <p>' . htmlspecialchars($task['description']) . '</p>
            <p class="meta"><strong>Assigned By:</strong> ' . $assignedBy . ' | <strong>Assigned To:</strong> ' . $assignedTo . '</p>
            <p class="meta"><strong>Assigned At:</strong> ' . $assignedAt . ' | <strong>Completed At:</strong> ' . $completedAt . '</p>
            <p class="meta"><strong>Status:</strong> <span class="status-text">' . $statusText . '</span></p>
        </div>';
    }
}

$html .= '
    </div>
</div>
</body>
</html>';

$mpdf->WriteHTML($html);
$mpdf->Output('completed_tasks_report.pdf', 'I');
?>
