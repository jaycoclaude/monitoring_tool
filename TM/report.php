<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once 'data.php';
require_once 'vendor/autoload.php';

use Mpdf\Mpdf;

$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    header('Location: ../index.php');
    exit();
}

$staff_list = getAllStaff();
$staff_json = json_encode(array_map(
    fn($s) => ['id' => $s['staff_id'], 'text' => $s['staff_names']],
    $staff_list
));

$search_staff_id = filter_input(INPUT_GET, 'staff_id', FILTER_VALIDATE_INT);
$search_name     = trim($_GET['search_name'] ?? '');

$tasks = [];
$total = 0;

if ($search_staff_id) {
    $tasks = getTasksForStaff($search_staff_id);
    $total = count($tasks);
}

// === EXPORT WITH TOAST FEEDBACK ===
if (!empty($_GET['export']) && $search_staff_id) {
    $format = $_GET['export'];

    try {
        exportStaffTasks($search_staff_id, $format, $tasks, $search_name);
        // Export triggers download — show success toast on return
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Export completed! Download started.'];
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Export failed: ' . $e->getMessage()];
    }

    // Redirect back to show toast
    $query = http_build_query([
        'staff_id' => $search_staff_id,
        'search_name' => $search_name
    ]);
    header("Location: report.php?$query");
    exit;
}

require_once 'header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@400;500;600&display=swap">

<style>
    :root {
        --bg: #f9f9fb;
        --card: #fff;
        --text: #1d1d1f;
        --text-light: #6e6e73;
        --border: #d2d2d7;
        --accent: #007aff;
        --dash: #c7c7cc;
        --mono: 'Roboto Mono', monospace;
        --success: #30a14e;
        --danger: #d73a49;
        --warning: #d97706;
        --info: #1565c0;
        --elegant-blue: #0071e3;
        --elegant-gray: #8e8e93;
        --radius: 12px;
        --focus-ring: rgba(0, 113, 227, 0.3);
    }

    /* === TOAST NOTIFICATION – ONLY ADDED === */
    .toast {
        position: fixed;
        top: 100px;
        right: 20px;
        min-width: 300px;
        max-width: 420px;
        background: var(--card);
        border-radius: 14px;
        padding: 16px 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 15px;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.4s ease;
        border-left: 5px solid;
    }
    .toast-success { color: var(--success); border-left-color: var(--success); background: #f0fdf4; }
    .toast-error   { color: var(--danger); border-left-color: var(--danger); background: #fdf2f2; }
    .toast i:first-child { font-size: 20px; }
    .toast-close {
        margin-left: auto;
        background: none;
        border: none;
        font-size: 18px;
        color: var(--elegant-gray);
        cursor: pointer;
        padding: 4px;
        border-radius: 50%;
        transition: background 0.2s;
    }
    .toast-close:hover { background: rgba(0,0,0,0.1); }

    @keyframes slideIn {
        from { transform: translateX(120%); opacity: 0; }
        to   { transform: translateX(0); opacity: 1; }
    }

    /* === ALL YOUR ORIGINAL STYLES BELOW === */
    body { background: var(--bg); color: var(--text); font-family: -elegant-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 0; }
    .container { width: 100%; max-width: none; margin: 0; padding: 20px 40px; box-sizing: border-box; }
    .page-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid var(--border); margin-bottom: 32px; width: 100%; box-sizing: border-box; }
    .page-title { font-size: 28px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 12px; }
    .controls { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 32px; align-items: center; width: 100%; }
    .search-box { flex: 1; min-width: 300px; }

    /* SELECT2 – Your Full elegant Style */
    .select2-container { width: 100% !important; font-family: 'SF Pro Display', -elegant-system, sans-serif; font-size: 17px; }
    .select2-selection { border-radius: var(--radius) !important; border: 1px solid var(--border) !important; background: var(--card) !important; min-height: 48px !important; padding: 0 16px !important; display: flex !important; align-items: center !important; justify-content: space-between !important; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,.05) !important; }
    .select2-container--default .select2-selection--single:focus, .select2-container--default .select2-selection--multiple:focus { border-color: var(--elegant-blue) !important; box-shadow: 0 0 0 3px var(--focus-ring) !important; }
    .select2-selection__rendered { margin: 0 !important; padding: 0 !important; line-height: normal !important; display: flex !important; align-items: center !important; flex: 1; color: var(--text); }
    .select2-selection__placeholder { color: var(--elegant-gray); font-weight: 400; }
    .select2-selection__arrow { height: 100% !important; width: 40px !important; top: 0 !important; right: 0 !important; display: flex !important; align-items: center !important; justify-content: center !important; }
    .select2-selection__arrow b { border: solid var(--elegant-gray); border-width: 6px 4px 0 4px !important; margin: 0 !important; width: 0 !important; height: 0 !important; }
    .select2-dropdown { border-radius: var(--radius) !important; border: 1px solid var(--border) !important; margin-top: 6px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important; background: var(--card); overflow: hidden; }
    .select2-search--dropdown { padding: 8px 12px !important; }
    .select2-search__field { border-radius: 10px !important; border: 1px solid var(--border) !important; padding: 10px 14px !important; font-size: 16px !important; outline: none !important; background: #F7F6F5FF !important; color: var(--text) !important; width: 100% !important; box-sizing: border-box; transition: all 0.2s; }
    .select2-search__field:focus { border-color: var(--elegant-blue) !important; box-shadow: 0 0 0 2px var(--focus-ring) !important; }
    .select2-results__options { max-height: 280px; overflow-y: auto; padding: 4px 0; scrollbar-width: thin; scrollbar-color: #c7c7cc transparent; }
    .select2-results__options::-webkit-scrollbar { width: 8px; }
    .select2-results__options::-webkit-scrollbar-track { background: transparent; }
    .select2-results__options::-webkit-scrollbar-thumb { background: #c7c7cc; border-radius: 4px; border: 2px solid transparent; background-clip: content-box; }
    .select2-results__option { padding: 10px 16px !important; margin: 0 8px !important; border-radius: 10px !important; font-size: 17px; font-weight: 400; color: var(--text); display: flex !important; align-items: center !important; transition: background 0.2s ease; min-height: 44px; gap: 12px; }
    .select2-results__option--highlighted { background: var(--elegant-blue) !important; color: white !important; }
    .select2-icon { width: 28px; height: 28px; font-size: 18px; display: flex; align-items: center; justify-content: center; border-radius: 6px; flex-shrink: 0; color: var(--elegant-blue); }

    /* BUTTONS */
    .export-group { display: flex; gap: 12px; }
    .btn { padding: 10px 20px; border-radius: 12px; font-weight: 500; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all .2s; box-shadow: 0 1px 3px rgba(0, 0, 0, .1); cursor: pointer; }
    .btn-success { background: var(--success); color: #fff; }
    .btn-danger { background: var(--danger); color: #fff; }
    .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0, 0, 0, .15); }

    /* REPORT – Your Full Styling */
    .report { background: var(--card); border-radius: 0; padding: 32px 40px; width: 100%; margin: 0; box-shadow: none; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); font-family: var(--mono); font-size: 14px; line-height: 1.8; box-sizing: border-box; }
    .report-header { text-align: center; border-bottom: 2px dashed var(--dash); padding-bottom: 20px; margin-bottom: 32px; }
    .report-title { font-size: 20px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; }
    .report-subtitle { font-size: 13px; color: var(--text-light); margin-top: 8px; }
    .report-subtitle strong { color: var(--text); }
    .section { margin: 32px 0; padding: 24px 0; border-top: 1px dashed var(--dash); }
    .section:first-of-type { border-top: none; padding-top: 0; }
    .section-title { font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--accent); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .task-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .task-entry { background: #f8f9fa; border-left: 4px solid var(--accent); padding: 20px; border-radius: 0 12px 12px 0; display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: auto auto; gap: 16px; position: relative; transition: all .2s; }
    .task-entry:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, .08); transform: translateY(-2px); }
    .task-title { grid-column: 1 / -1; margin: 0 0 16px; font-size: 17px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .info-section { display: grid; grid-template-columns: 1fr; gap: 12px; }
    .info-group { display: flex; flex-direction: column; gap: 4px; }
    .info-label { color: var(--text-light); font-weight: 500; text-transform: uppercase; letter-spacing: .5px; font-size: 11px; }
    .info-value { color: var(--text); font-weight: 500; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
    .status-pending { background: #fff4e5; color: var(--warning); }
    .status-in_progress { background: #e3f2fd; color: var(--info); }
    .status-completed { background: #e8f5e8; color: var(--success); }
    .priority-low { background: #e0e0e0; color: #424242; }
    .priority-medium { background: #bbdefb; color: var(--info); }
    .priority-high { background: #ffcc80; color: #ef6c00; }
    .priority-urgent { background: #ffcdd2; color: var(--danger); }
    .task-desc { grid-column: 1 / -1; margin: 16px 0; padding: 12px; background: rgba(0, 0, 0, .03); border-radius: 8px; font-size: 13.5px; line-height: 1.7; border-left: 3px solid var(--accent); }
    .task-attachments { grid-column: 1 / -1; font-size: 13px; color: var(--text-light); display: flex; align-items: center; gap: 6px; }
    .task-meta-footer { grid-column: 1 / -1; margin-top: 16px; padding-top: 12px; border-top: 1px dashed var(--dash); font-size: 12px; color: var(--text-light); display: flex; align-items: center; gap: 6px; }
    .no-results { text-align: center; padding: 80px 20px; color: var(--text-light); font-style: italic; font-size: 16px; }
    .no-results i { font-size: 56px; color: #d1d1d6; margin-bottom: 16px; display: block; }
    .no-results strong { color: var(--text); display: block; margin-top: 8px; font-style: normal; }

    @media (max-width:1024px) { .task-grid { grid-template-columns: 1fr; } .task-entry { grid-template-columns: 1fr; } }
    @media (max-width:768px) { .controls { flex-direction: column; align-items: stretch; } .search-box { min-width: 100%; } .export-group { justify-content: center; } .container { padding: 16px 20px; } }
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

    <!-- TOAST MESSAGE -->
    <?php if (isset($_SESSION['flash'])): 
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
    ?>
    <div class="toast toast-<?= $flash['type'] ?>" id="toast">
        <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <span><?= htmlspecialchars($flash['msg']) ?></span>
        <button type="button" class="toast-close" id="close-toast">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

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
                                    <?= count($t['attachments']) ? count($t['attachments']) . ' file' . (count($t['attachments']) > 1 ? 's' : '') : 'No attachments' ?>
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
$(function() {
    const staffData = <?= $staff_json ?>;
    const selectedStaffId = <?= $search_staff_id ?: 'null' ?>;
    const selectedStaffName = <?= json_encode($search_name) ?>;

    staffData.unshift({ id: '', text: 'Select Staff' });

    $('#staff-select').select2({
        placeholder: 'Select Staff',
        data: staffData,
        width: '100%',
        allowClear: true,
        minimumResultsForSearch: 1,
        dropdownParent: $('body'),
        templateResult: s => {
            if (!s.id) return s.text;
            return $(`<span style="display:flex;align-items:center;gap:12px;">
                <i class="fas fa-user select2-icon"></i>
                <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${s.text}</span>
            </span>`);
        },
        templateSelection: s => s.id ? s.text : 'Select Staff',
        escapeMarkup: m => m
    });

    if (selectedStaffId && selectedStaffName) {
        const optionExists = staffData.some(s => s.id == selectedStaffId);
        if (!optionExists) {
            const newOption = new Option(selectedStaffName, selectedStaffId, true, true);
            $('#staff-select').append(newOption).trigger('change');
        } else {
            $('#staff-select').val(selectedStaffId).trigger('change');
        }
    }

    $('#staff-select').on('select2:select', e => {
        const { id, text } = e.params.data;
        if (id) {
            location.href = `?staff_id=${id}&search_name=${encodeURIComponent(text)}`;
        }
    });

    $('#staff-select').on('select2:clear', () => {
        location.href = `?`;
    });

    // === TOAST: CLOSE → REDIRECT TO INDEX ===
    $('#close-toast').on('click', function() {
        window.location.href = 'index.php';
    });

    // === AUTO-REDIRECT AFTER 4s ===
    setTimeout(function() {
        if (document.getElementById('toast')) {
            window.location.href = 'index.php';
        }
    }, 4000);
});
</script>

<?php require_once 'footer.php'; ?>