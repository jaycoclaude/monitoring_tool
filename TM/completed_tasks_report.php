<?php
// completed_tasks_report.php (updated)
// Generates Completed Tasks PDF (filtered by date range).
require_once '../includes/auth.php';
require_once 'data.php';
require_once 'vendor/autoload.php';

use Mpdf\Mpdf;

$current_user_id = $_SESSION['user_id'];
if (!$current_user_id) {
    header('Location: ../index.php');
    exit();
}

// Fetch tasks same as index
$tasks = getTasks($current_user_id);

// Current staff info
$db = getDB();
$stmt = $db->prepare("SELECT staff_id, staff_names, staff_email FROM tbl_staff WHERE user_id = :user_id AND staff_status = 1 LIMIT 1");
$stmt->execute([':user_id' => $current_user_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
$current_staff_name  = $staff['staff_names'] ?? '';
$current_staff_email = $staff['staff_email'] ?? $_SESSION['staff_email'] ?? '';

// Parse and validate date range from GET (for the modal form)
$rawStart = trim($_GET['start_date'] ?? '');
$rawEnd   = trim($_GET['end_date'] ?? '');
$startDate = null; $endDate = null;
$errors = [];

if ($rawStart !== '') {
    $s = strtotime($rawStart . ' 00:00:00');
    if ($s === false) $errors[] = 'Invalid start date';
    else $startDate = $s;
}
if ($rawEnd !== '') {
    $e = strtotime($rawEnd . ' 23:59:59');
    if ($e === false) $errors[] = 'Invalid end date';
    else $endDate = $e;
}
if ($startDate && $endDate && $startDate > $endDate) {
    // be forgiving: swap
    $tmp = $startDate; $startDate = $endDate; $endDate = $tmp;
}

// Filter completed tasks using completed_at fallback to updated_at (Option B)
$completedTasks = array_filter($tasks, function($t) use ($current_staff_name, $startDate, $endDate) {
    if (($t['assigned_to_name'] ?? '') !== $current_staff_name) return false;
    if (($t['status'] ?? '') !== 'completed') return false;

    $completedAtStr = $t['completed_at'] ?? $t['updated_at'] ?? null;
    if (!$completedAtStr) return false;
    $completedAt = strtotime($completedAtStr);
    if ($completedAt === false) return false;

    if ($startDate && $completedAt < $startDate) return false;
    if ($endDate && $completedAt > $endDate) return false;

    return true;
});

// Sort by completed date descending
usort($completedTasks, function($a, $b) {
    $ta = strtotime($a['completed_at'] ?? $a['updated_at'] ?? '');
    $tb = strtotime($b['completed_at'] ?? $b['updated_at'] ?? '');
    return $tb <=> $ta;
});

// Build PDF with mPDF
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

// Build HTML for PDF
$html = '<!doctype html><html><head><meta charset="utf-8"><title>Completed Tasks Report</title><style>
body{font-family: -elegant-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color:#111827; font-size:11pt}
.page{padding:20px}
.header{text-align:center}
.institution-name{font-size:18pt;font-weight:700;margin:6px 0}
.info{font-size:10pt;color:#6b7280;margin:2px 0}
.card{background:#fff;border-radius:10px;padding:12px;margin-bottom:10px;box-shadow:0 2px 6px rgba(0,0,0,0.04)}
.card h3{margin:0 0 6px;font-size:12pt;color:#1e40af}
.card p{margin:3px 0;font-size:10pt;color:#374151}
.meta{font-size:9pt;color:#6b7280}
.ontime{color:#16a34a;font-weight:600}
.delayed{color:#dc2626;font-weight:600}
.no-data{text-align:center;padding:30px;color:#6b7280;font-style:italic}
.table{width:100%;border-collapse:collapse;margin-top:12px}
.table th, .table td{border:1px solid #e6eef8;padding:8px;font-size:10pt}
.table th{background:#eff6ff;color:#0f172a}
</style></head><body><div class="page"><div class="header">';

// Insert logo if exists
$logoPath = __DIR__ . '/assets/Logo.png';
if (file_exists($logoPath)) {
    $html .= '<img src="' . $logoPath . '" alt="Logo" style="width:90px;height:90px;display:block;margin:0 auto 8px">';
}

$html .= '<div class="institution-name">COMPLETED TASKS REPORT</div>';
$html .= '<div class="info"><strong>Prepared For:</strong> ' . htmlspecialchars($current_staff_name) . ' | <strong>Report Period:</strong> ' .
    ($startDate ? date('M j, Y', $startDate) : 'All') . ' — ' . ($endDate ? date('M j, Y', $endDate) : 'All') . '</div>';
$html .= '</div><div class="section">';

if (empty($completedTasks)) {
    $html .= '<div class="no-data">No completed tasks found for the selected period.</div>';
} else {
    $i = 1;
    foreach ($completedTasks as $task) {
        $assignedBy  = (($task['assigned_by_name'] ?? '') === $current_staff_name) ? 'Me' : htmlspecialchars($task['assigned_by_name'] ?? '');
        $assignedTo  = htmlspecialchars($task['assigned_to_name'] ?? '');
        $assignedAt  = !empty($task['created_at']) ? date('M j, Y', strtotime($task['created_at'])) : '';
        $completedAtStr = $task['completed_at'] ?? $task['updated_at'] ?? '';
        $completedAt = $completedAtStr ? date('M j, Y', strtotime($completedAtStr)) : '';
        $statusText  = ucfirst(str_replace('_', ' ', $task['status'] ?? ''));

        // determine on-time/delayed
        $dueDate     = !empty($task['due_date']) ? strtotime($task['due_date']) : null;
        $doneDate    = $completedAtStr ? strtotime($completedAtStr) : null;
        $timingLabel = '';
        if ($dueDate && $doneDate) {
            if ($doneDate <= $dueDate) { $timingLabel = '<span class="ontime">On Time</span>'; }
            else { $timingLabel = '<span class="delayed">Delayed</span>'; }
        }

        $html .= '<div class="card">';
        $html .= '<h3>' . $i++ . '. ' . htmlspecialchars($task['title'] ?? '') . '</h3>';
        $html .= '<p>' . nl2br(htmlspecialchars($task['description'] ?? '')) . '</p>';
        $html .= '<p class="meta"><strong>Assigned By:</strong> ' . $assignedBy . ' | <strong>Assigned To:</strong> ' . $assignedTo . '</p>';
        $html .= '<p class="meta"><strong>Assigned At:</strong> ' . $assignedAt . ' | <strong>Completed At:</strong> ' . $completedAt . '</p>';
        $html .= '<p class="meta"><strong>Status:</strong> <strong>' . $statusText . '</strong> | <strong>Completion:</strong> ' . $timingLabel . '</p>';
        $html .= '</div>';
    }
}

$html .= '</div></div></body></html>';

$mpdf->WriteHTML($html);
$mpdf->Output('completed_tasks_report.pdf', 'I');
exit();
