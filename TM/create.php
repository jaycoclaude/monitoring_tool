<?php
require_once '../includes/auth.php';
require_once 'data.php';
$current_staff = $_SESSION['user_id'];

$staff_list = getAllStaff();

// === HANDLE FORM SUBMISSION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        $dir = 'uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        foreach ($_FILES['attachments']['name'] as $k => $name) {
            if ($_FILES['attachments']['error'][$k] === 0) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), ['pdf','doc','docx','jpg','png','zip'])) {
                    $file = time() . "_$k.$ext";
                    if (move_uploaded_file($_FILES['attachments']['tmp_name'][$k], $dir . $file)) {
                        $uploaded[] = $file;
                    }
                }
            }
        }
    }

    $taskAdded = addTask([
        'title'       => $_POST['title'],
        'description' => $_POST['description'],
        'assigned_by' => $current_staff,
        'assigned_to' => $_POST['assignee'],
        'status'      => 'pending',
        'priority'    => $_POST['priority'],
        'due_date'    => $_POST['due_date'],
        'attachments' => $uploaded
    ]);

    if ($taskAdded) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Task created successfully!'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to create task. Please try again.'];
    }

    // Stay on this page to show toast
    header('Location: create.php');
    exit;
}
?>
<?php require_once 'header.php'; ?>

<!-- Font Awesome + Select2 + SF Pro -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    :root {
        --elegant-blue: #0071e3;
        --elegant-gray: #8e8e93;
        --border: #d2d2d7;
        --card: #ffffff;
        --text: #1d1d1f;
        --radius: 12px;
        --focus-ring: rgba(0, 113, 227, 0.3);
        --icon-blue: #003366;
    }

    /* === ICONS === */
    .form-container i, .page-header i { color: var(--icon-blue); }
    .btn-submit i { color: #ffffff; }

    /* === SELECT2 – elegant STYLE === */
    .select2-container { width: 100% !important; font-family: 'SF Pro Display', sans-serif; font-size: 16px; }
    .select2-selection {
        border-radius: var(--radius) !important; border: 1px solid var(--border) !important;
        background: var(--card) !important; min-height: 44px !important; padding: 0 14px !important;
        display: flex !important; align-items: center !important; justify-content: space-between !important;
        transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .select2-container--default .select2-selection--single:focus {
        border-color: var(--elegant-blue) !important; box-shadow: 0 0 0 3px var(--focus-ring) !important;
    }
    .select2-selection__rendered { margin:0; padding:0; display:flex; align-items:center; flex:1; color:var(--text); gap:10px; }
    .select2-selection__placeholder { color: var(--elegant-gray); }
    .select2-selection__arrow { height:100%; width:36px; display:flex; align-items:center; justify-content:center; }
    .select2-selection__arrow b { border: solid var(--elegant-gray); border-width: 6px 4px 0 4px !important; margin:0; }

    .select2-dropdown {
        border-radius: var(--radius) !important; border: 1px solid var(--border) !important;
        margin-top: 6px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
        background: var(--card); overflow: hidden;
    }

    .select2-search--dropdown { padding: 8px 12px !important; }
    .select2-search__field {
        border-radius: 10px !important; border: 1px solid var(--border) !important;
        padding: 10px 14px !important; font-size: 16px !important; background: #f5f5f7 !important;
        color: var(--text) !important; width: 100% !important; box-sizing: border-box; outline: none;
        transition: all 0.2s;
    }
    .select2-search__field:focus {
        border-color: var(--elegant-blue) !important; box-shadow: 0 0 0 2px var(--focus-ring) !important;
    }

    .select2-results__options {
        max-height: 220px !important; min-height: 220px !important;
        overflow-y: scroll !important; padding: 8px 0 !important;
        scrollbar-width: thin; scrollbar-color: #c7c7cc transparent; scroll-behavior: smooth;
    }
    .select2-results__options::-webkit-scrollbar { width: 8px; }
    .select2-results__options::-webkit-scrollbar-track { background: transparent; }
    .select2-results__options::-webkit-scrollbar-thumb {
        background: #c7c7cc; border-radius: 4px; border: 2px solid transparent; background-clip: content-box;
    }

    .select2-results__option {
        padding: 12px 16px !important; margin: 0 8px !important; border-radius: 10px !important;
        font-size: 16px; color: var(--text); display: flex !important; align-items: center !important;
        gap: 12px; min-height: 48px !important; transition: background 0.2s;
    }
    .select2-results__option--highlighted { background: var(--elegant-blue) !important; color: white !important; }
    .select2-icon { width: 28px; height: 28px; font-size: 17px; display: flex; align-items: center; justify-content: center; color: var(--icon-blue); flex-shrink: 0; }

    /* === FORM CONTAINER – LANDSCAPE === */
    .form-container {
        max-width: 1000px; margin: 0 auto; padding: 32px;
        background: var(--card); border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08); border: 1px solid var(--border);
    }

    .form-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;
    }
    .form-group.full-width { grid-column: 1 / -1; }
    @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; }
    .form-group label {
        font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; color: var(--text);
    }
    .form-group input, .form-group textarea, .form-group select {
        padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px;
        font-size: 16px; background: var(--card); transition: all 0.2s;
    }
    .form-group input:focus, .form-group textarea:focus {
        border-color: var(--elegant-blue); box-shadow: 0 0 0 3px var(--focus-ring); outline: none;
    }

    .btn-submit {
        background: var(--elegant-blue); color: white; padding: 12px 24px; border: none;
        border-radius: 12px; font-weight: 600; font-size: 16px; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(0,113,227,0.2);
    }
    .btn-submit:hover { background: #0066cc; transform: translateY(-1px); }

    .back-btn { color: var(--icon-blue); text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; }
    .back-btn:hover { color: var(--elegant-blue); }

    small { color: var(--elegant-gray); font-size: 13px; margin-top: 6px; display: flex; align-items: center; gap: 6px; }

    /* === TOAST NOTIFICATION === */
    .toast {
        position: fixed; top: 100px; right: 20px; min-width: 300px; max-width: 420px;
        background: var(--card); border-radius: 14px; padding: 16px 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; align-items: center;
        gap: 12px; font-size: 15px; font-weight: 500; z-index: 10000;
        animation: slideIn 0.4s ease; border-left: 5px solid;
    }
    .toast-success { color: #30a14e; border-left-color: #30a14e; background: #f0fdf4; }
    .toast-error   { color: #d73a49; border-left-color: #d73a49; background: #fdf2f2; }
    .toast i:first-child { font-size: 20px; }
    .toast-close {
        margin-left: auto; background: none; border: none; font-size: 18px;
        color: var(--elegant-gray); cursor: pointer; padding: 4px; border-radius: 50%;
        transition: background 0.2s;
    }
    .toast-close:hover { background: rgba(0,0,0,0.1); }

    @keyframes slideIn {
        from { transform: translateX(120%); opacity: 0; }
        to   { transform: translateX(0); opacity: 1; }
    }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-clipboard-list"></i> Create Assignment</h1>
    <a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="form-container">

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

    <form method="POST" enctype="multipart/form-data" class="form-grid">
        <div class="form-group">
            <label><i class="fa-solid fa-heading"></i> Title *</label>
            <input type="text" name="title" placeholder="Enter task title" required>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-user-check"></i> Assign To *</label>
            <select name="assignee" class="select2" required>
                <option value="">Select staff...</option>
                <?php foreach ($staff_list as $s): ?>
                    <option value="<?= $s['staff_id'] ?>"><?= htmlspecialchars($s['staff_names']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-calendar-day"></i> Due Date *</label>
            <input type="date" name="due_date" required>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-flag"></i> Priority</label>
            <select name="priority" class="select2">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label><i class="fa-solid fa-align-left"></i> Description *</label>
            <textarea name="description" placeholder="Describe the task..." required rows="4"></textarea>
        </div>

        <div class="form-group full-width">
            <label><i class="fa-solid fa-paperclip"></i> Attachments</label>
            <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.png,.zip">
            <small><i class="fa-solid fa-file"></i> Accepted: PDF, DOC, JPG, PNG, ZIP</small>
        </div>

        <div class="form-group full-width" style="text-align:right;">
            <button type="submit" class="btn-submit"><i class="fa-solid fa-plus-circle"></i> Create Assignment</button>
        </div>
    </form>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // === SELECT2 WITH ICONS & SEARCH CONTROL ===
    $('.select2').each(function() {
        const $select = $(this);
        const isAssignee = $select.attr('name') === 'assignee';

        $select.select2({
            width: '100%',
            placeholder: isAssignee ? 'Select staff...' : 'Select priority',
            allowClear: true,
            minimumResultsForSearch: isAssignee ? 1 : Infinity,
            dropdownParent: $('body'),
            templateResult: function(state) {
                if (!state.id) return state.text;
                let icon = isAssignee ? 'fa-user' :
                           state.id === 'low' ? 'fa-arrow-down' :
                           state.id === 'medium' ? 'fa-equals' :
                           state.id === 'high' ? 'fa-arrow-up' : 'fa-exclamation-triangle';
                return $(`
                    <span style="display:flex;align-items:center;gap:12px;">
                        <i class="fas ${icon} select2-icon"></i>
                        <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            ${state.text}
                        </span>
                    </span>
                `);
            },
            templateSelection: s => s.id ? s.text : $select.data('placeholder'),
            escapeMarkup: m => m
        });
    });

    // === PREVENT PAST DATES ===
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('input[name="due_date"]').setAttribute('min', today);

    // === TOAST: CLOSE → REDIRECT ===
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