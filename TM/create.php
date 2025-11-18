<?php
require_once '../includes/auth.php';
require_once 'data.php';
$current_staff = $_SESSION['user_id'];

$staff_list        = getAllStaff();
$staff_task_counts = getStaffOpenTaskCounts();   // ← counts open tasks per staff

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
<link rel="stylesheet" href="assets/styles.css">

<style>
/* Task count badge – blue bubble */
.task-badge {
    background: #007aff;
    color: #fff;
    font-size: 0.85rem;      /* larger font */
    padding: 4px 8px;        /* more padding */
    border-radius: 14px;     /* rounder */
    min-width: 24px;         /* wider min width */
    text-align: center;
    line-height: 1;
    display: inline-block;
}

/* Optional: slightly larger icons in select2 */
.select2-container--default .select2-selection--single .select2-selection__rendered i {
    margin-right: 6px;
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
                <?php foreach ($staff_list as $s):
                    $cnt = $staff_task_counts[$s['staff_id']] ?? 0;
                ?>
                    <option value="<?= $s['staff_id'] ?>" data-tasks="<?= $cnt ?>">
                        <?= htmlspecialchars($s['staff_names']) ?>
                    </option>
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
    // === SELECT2 WITH ICONS & BADGE ===
    $('.select2').each(function() {
        const $select   = $(this);
        const isAssignee = $select.attr('name') === 'assignee';

        $select.select2({
            width: '100%',
            placeholder: isAssignee ? 'Select staff...' : 'Select priority',
            allowClear: true,
            minimumResultsForSearch: isAssignee ? 1 : Infinity,
            dropdownParent: $('body'),

            // ---- ICON + BADGE IN DROPDOWN ----
            templateResult: function(state) {
                if (!state.id) return state.text;

                const tasks = state.element?.dataset.tasks || 0;
                const badge = tasks ? `<span class="task-badge">${tasks}</span>` : '';

                const icon = isAssignee
                    ? 'fa-user'
                    : state.id === 'low'   ? 'fa-arrow-down'
                    : state.id === 'medium'? 'fa-equals'
                    : state.id === 'high'  ? 'fa-arrow-up'
                    : 'fa-exclamation-triangle';

                return $(`
                    <span style="display:flex;align-items:center;gap:8px;">
                        <i class="fas ${icon} select2-icon"></i>
                        <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            ${state.text}
                        </span>
                        ${badge}
                    </span>
                `);
            },

            // ---- SELECTED TEXT WITH VISIBLE BADGE ----
            templateSelection: function(state) {
                if (!state.id) return state.text;
                const tasks = state.element?.dataset.tasks || 0;
                if (tasks) {
                    return $(`
                        <span style="display:flex;align-items:center;gap:6px;">
                            <span>${state.text}</span>
                            <span class="task-badge">${tasks}</span>
                        </span>
                    `);
                }
                return state.text;
            },

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
