<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once 'data.php';
require_once 'vendor/autoload.php';

use Mpdf\Mpdf;

// ---------- Auth ----------
$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    header('Location: ../index.php');
    exit();
}

// ---------- Staff list ----------
$staff_list = getAllStaff();
$staff_json = json_encode(array_map(
    fn($s) => ['id' => $s['staff_id'], 'text' => $s['staff_names']],
    $staff_list
));

// ---------- Input ----------
$search_staff_id = filter_input(INPUT_GET, 'staff_id', FILTER_VALIDATE_INT);
$search_name     = trim($_GET['search_name'] ?? '');

// ---------- Data ----------
$tasks = [];
$total = 0;

if ($search_staff_id) {
    $tasks = getTasksForStaff($search_staff_id);
    $total = count($tasks);
}

// ---------- Export ----------
if (!empty($_GET['export']) && $search_staff_id) {
    $format = $_GET['export']; // pdf | csv
    exportStaffTasks($search_staff_id, $format, $tasks, $search_name);
    exit;
}

require_once 'header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;600&display=swap">

<style>
:root {
    --bg:#f9f9fb; --card:#fff; --text:#1d1d1f; --text-light:#6e6e73;
    --border:#d2d2d7; --accent:#007aff; --dash:#c7c7cc; --mono:'Roboto Mono',monospace;
    --success:#30a14e; --danger:#d73a49; --warning:#d97706; --info:#1565c0;
}

body {
    background:var(--bg);
    color:var(--text);
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
    line-height:1.6;
    margin:0;
}

.container { max-width:1200px; margin:0 auto; padding:20px; }

.page-header {
    display:flex; justify-content:space-between; align-items:center;
    padding:20px 0; border-bottom:1px solid var(--border); margin-bottom:32px;
}

.page-title {
    font-size:28px; font-weight:600; margin:0; display:flex; align-items:center; gap:12px;
}

.controls { display:flex; flex-wrap:wrap; gap:16px; margin-bottom:32px; align-items:center; }

.search-box { flex:1; min-width:300px; }

.select2-container--default .select2-selection--single {
    border:1.5px solid var(--border); border-radius:12px; height:48px; padding:0 16px;
    background:var(--card); font-family:var(--mono); font-size:15px;
    box-shadow:0 1px 3px rgba(0,0,0,.05); transition:all .2s;
}

.select2-container--default .select2-selection__rendered {
    line-height:46px; color:var(--text);
}

.select2-container--default.select2-container--open .select2-selection--single {
    border-color:var(--accent);
    box-shadow:0 0 0 3px rgba(0,122,255,.15);
}

.export-group { display:flex; gap:12px; }

.btn {
    padding:10px 20px; border-radius:12px; font-weight:500; font-size:14px;
    text-decoration:none; display:inline-flex; align-items:center; gap:8px;
    transition:all .2s; box-shadow:0 1px 3px rgba(0,0,0,.1); cursor:pointer;
}

.btn-success { background:var(--success); color:#fff; }
.btn-danger { background:var(--danger); color:#fff; }

.btn:hover {
    transform:translateY(-1px); box-shadow:0 4px 8px rgba(0,0,0,.15);
}

.report {
    background:var(--card); border-radius:16px; padding:32px;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
    border:1px solid var(--border); font-family:var(--mono);
    font-size:14px; line-height:1.8;
}

.report-header {
    text-align:center; border-bottom:2px dashed var(--dash);
    padding-bottom:20px; margin-bottom:32px;
}

.report-title {
    font-size:20px; font-weight:600; text-transform:uppercase; letter-spacing:2px;
}

.report-subtitle {
    font-size:13px; color:var(--text-light); margin-top:8px;
}

.report-subtitle strong { color:var(--text); }

.section {
    margin:32px 0; padding:24px 0; border-top:1px dashed var(--dash);
}

.section:first-of-type { border-top:none; padding-top:0; }

.section-title {
    font-weight:600; font-size:15px; text-transform:uppercase;
    letter-spacing:1.5px; color:var(--accent);
    margin-bottom:16px; display:flex; align-items:center; gap:8px;
}

.task-grid {
    display:grid;
    grid-template-columns:repeat(2, 1fr);
    gap:20px;
}

.task-entry {
    background:#f8f9fa; border-left:4px solid var(--accent);
    padding:20px; border-radius:0 12px 12px 0;
    display:grid; grid-template-columns:1fr 1fr; grid-template-rows:auto auto;
    gap:16px; position:relative;
    transition:all .2s;
}

.task-entry:hover { box-shadow:0 4px 12px rgba(0,0,0,.08); transform:translateY(-2px); }

.task-title {
    grid-column:1 / -1;
    margin:0 0 16px; font-size:17px; font-weight:600;
    display:flex; align-items:center; gap:8px;
}

.info-section {
    display:grid; grid-template-columns:1fr; gap:12px;
}

.info-group { display:flex; flex-direction:column; gap:4px; }
.info-label { color:var(--text-light); font-weight:500; text-transform:uppercase; letter-spacing:.5px; font-size:11px; }
.info-value { color:var(--text); font-weight:500; }

.badge {
    display:inline-block; padding:4px 10px; border-radius:20px;
    font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;
}

.status-pending{background:#fff4e5;color:var(--warning);}
.status-in_progress{background:#e3f2fd;color:var(--info);}
.status-completed{background:#e8f5e8;color:var(--success);}
.status-overdue{background:#ffebee;color:var(--danger);}
.status-cancelled{background:#eceff1;color:#546e7a;}
.priority-low{background:#e0e0e0;color:#424242;}
.priority-medium{background:#bbdefb;color:var(--info);}
.priority-high{background:#ffcc80;color:#ef6c00;}
.priority-urgent{background:#ffcdd2;color:var(--danger);}

.task-desc {
    grid-column:1 / -1;
    margin:16px 0; padding:12px; background:rgba(0,0,0,.03);
    border-radius:8px; font-size:13.5px; line-height:1.7;
    border-left:3px solid var(--accent);
}

.task-attachments {
    grid-column:1 / -1;
    font-size:13px; color:var(--text-light);
    display:flex; align-items:center; gap:6px;
}

.task-meta-footer {
    grid-column:1 / -1;
    margin-top:16px; padding-top:12px; border-top:1px dashed var(--dash);
    font-size:12px; color:var(--text-light);
    display:flex; align-items:center; gap:6px;
}

.no-results {
    text-align:center; padding:80px 20px; color:var(--text-light);
    font-style:italic; font-size:16px;
}

.no-results i {
    font-size:56px; color:#d1d1d6; margin-bottom:16px; display:block;
}

.no-results strong { color:var(--text); display:block; margin-top:8px; font-style:normal; }

@media (max-width:1024px){
    .task-grid { grid-template-columns:1fr; }
    .task-entry { grid-template-columns:1fr; }
}

@media (max-width:768px){
    .controls{flex-direction:column;align-items:stretch;}
    .search-box{min-width:100%;}
    .export-group{justify-content:center;}
}
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-tasks"></i> Task Monitoring Report</h1>
    </div>

    <div class="controls">
        <div class="search-box">
            <select id="staff-select"></select>
        </div>

        <?php if ($search_staff_id): ?>
            <div class="export-group">
                <a href="?staff_id=<?= $search_staff_id ?>&search_name=<?= urlencode($search_name) ?>&export=pdf"
                   class="btn btn-danger"><i class="fas fa-file-pdf"></i> Export PDF</a>
                <a href="?staff_id=<?= $search_staff_id ?>&search_name=<?= urlencode($search_name) ?>&export=csv"
                   class="btn btn-success"><i class="fas fa-file-csv"></i> Export CSV</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($search_staff_id): ?>
        <div class="report" id="report-content">
            <div class="report-header">
                <div class="report-title">Operational Summary</div>
                <div class="report-subtitle">
                    SUBJECT: <strong><?= htmlspecialchars($search_name) ?></strong> |
                    TOTAL TASKS: <strong><?= $total ?></strong> |
                    GENERATED: <strong><?= date('d M Y \a\t H:i') ?></strong>
                </div>
            </div>

            <?php if ($total === 0): ?>
                <div class="no-results">
                    <i class="fas fa-folder-open"></i>
                    No tasks found for this personnel
                    <strong>Try selecting a different staff member</strong>
                </div>
            <?php else: ?>
                <div class="section">
                    <div class="section-title"><i class="fas fa-list-check"></i> Task Details</div>

                    <div class="task-grid">
                        <?php foreach ($tasks as $t): ?>
                            <div class="task-entry">
                                <h3 class="task-title">
                                    <i class="fas fa-clipboard-list"></i>
                                    <?= htmlspecialchars($t['title']) ?>
                                </h3>

                                <!-- Two sections per card -->
                                <div class="info-section">
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-user-plus"></i> Assigned By</div>
                                        <div class="info-value"><?= htmlspecialchars($t['assigned_by_name']) ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-calendar-plus"></i> Assigned Date</div>
                                        <div class="info-value"><?= date('d M Y H:i', strtotime($t['created_at'])) ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-circle-info"></i> Status</div>
                                        <div class="info-value">
                                            <span class="badge status-<?= htmlspecialchars($t['status']) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-section">
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-user-check"></i> Assigned To</div>
                                        <div class="info-value"><?= htmlspecialchars($t['assigned_to_name'] ?? $search_name) ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-calendar-alt"></i> Due Date</div>
                                        <div class="info-value"><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '<em>—</em>' ?></div>
                                    </div>
                                    <div class="info-group">
        <div class="info-label"><i class="fas fa-calendar-check"></i> Completed Date</div>
        <div class="info-value"><?= $t['completed_at'] ? date('d M Y', strtotime($t['completed_at'])) : '<em>—</em>' ?></div>
    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-exclamation-triangle"></i> Priority</div>
                                        <div class="info-value">
                                            <span class="badge priority-<?= htmlspecialchars($t['priority']) ?>">
                                                <?= strtoupper($t['priority']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($t['description'])): ?>
                                    <div class="task-desc">
                                        <strong><i class="fas fa-align-left"></i> Description:</strong><br>
                                        <?= nl2br(htmlspecialchars($t['description'])) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="task-attachments">
                                    <i class="fas fa-paperclip"></i>
                                    <strong>Attachments:</strong>
                                    <?= count($t['attachments'])
                                        ? count($t['attachments']) . ' file' . (count($t['attachments']) > 1 ? 's' : '')
                                        : 'No attachments' ?>
                                </div>

                                <div class="task-meta-footer">
                                    | Last Updated: <?= date('d M Y H:i', strtotime($t['updated_at'] ?? $t['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            Select a staff member to view their task report
            <strong>Use the search box above to begin</strong>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
    const staffData = <?= $staff_json ?>;
    const selectedStaffId = <?= $search_staff_id ?: 'null' ?>;
    const selectedStaffName = <?= json_encode($search_name) ?>;

    // Add default placeholder option
    staffData.unshift({ id: '', text: 'Select Staff' });

    $('#staff-select').select2({
        placeholder: 'Select Staff',
        data: staffData,
        width: '100%',
        allowClear: true,
        templateResult: s => !s.id ? s.text : $(`<span><i class="fas fa-user"></i> ${s.text}</span>`),
        templateSelection: s => s.id ? s.text : 'Select Staff'
    });

    // Preserve selection after reload
    if (selectedStaffId && selectedStaffName) {
        const optionExists = staffData.some(s => s.id == selectedStaffId);
        if (!optionExists) {
            const newOption = new Option(selectedStaffName, selectedStaffId, true, true);
            $('#staff-select').append(newOption).trigger('change');
        } else {
            $('#staff-select').val(selectedStaffId).trigger('change');
        }
    }

    // Navigate on selection
    $('#staff-select').on('select2:select', e => {
        const {id, text} = e.params.data;
        if (id) {
            location.href = `?staff_id=${id}&search_name=${encodeURIComponent(text)}`;
        }
    });

    // Clear selection
    $('#staff-select').on('select2:clear', () => {
        location.href = `?`;
    });
});
</script>


<?php require_once 'footer.php'; ?>
