<?php
require_once '../includes/auth.php';
require_once 'data.php';

$taskId = intval($_GET['id'] ?? 0);
if (!$taskId) {
    header('Location: index.php');
    exit;
}

$task = getTaskById($taskId);
$staff_list = getAllStaff();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated = updateTask($taskId, [
        'title'       => $_POST['title'],
        'description' => $_POST['description'],
        'priority'    => $_POST['priority'],
        'status'      => $_POST['status'],
        'due_date'    => $_POST['due_date'],
        'assigned_to' => $_POST['assignee']
    ]);

    $_SESSION['flash'] = [
        'type' => $updated ? 'success' : 'error',
        'msg'  => $updated ? 'Task updated successfully!' : 'Failed to update task.'
    ];

    // Don't redirect immediately — we'll show toast and redirect via JS
    header("Location: edit.php?id=$taskId&show_toast=1");
    exit;
}

require_once 'header.php';
?>

<style>
/* === Toast Styles === */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    border-radius: 8px;
    color: #fff;
    font-weight: 500;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.4s ease, transform 0.4s ease;
}

.toast.show {
    opacity: 1;
    transform: translateY(0);
}

.toast-success {
    background-color: #28a745;
}

.toast-error {
    background-color: #dc3545;
}
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-edit"></i> Edit Assignment</h1>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="form-container">
    <form method="POST" class="form-grid">
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>
        </div>

        <div class="form-group">
            <label>Assign To *</label>
            <select name="assignee" required>
                <?php foreach ($staff_list as $s): ?>
                    <option value="<?= $s['staff_id'] ?>" <?= $s['staff_id'] == $task['assigned_to'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['staff_names']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Due Date *</label>
            <input type="date" name="due_date" value="<?= htmlspecialchars($task['due_date']) ?>" required>
        </div>

        <div class="form-group">
            <label>Priority</label>
            <select name="priority">
                <option value="low" <?= $task['priority'] == 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= $task['priority'] == 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= $task['priority'] == 'high' ? 'selected' : '' ?>>High</option>
                <option value="urgent" <?= $task['priority'] == 'urgent' ? 'selected' : '' ?>>Urgent</option>
            </select>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="pending" <?= $task['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" <?= $task['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $task['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label>Description *</label>
            <textarea name="description" rows="4" required><?= htmlspecialchars($task['description']) ?></textarea>
        </div>

        <div class="form-group full-width" style="text-align:right;">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if (isset($_SESSION['flash'])): 
        $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        const toast = document.createElement('div');
        toast.className = `toast toast-<?= $f['type'] ?>`;
        toast.innerHTML = `<i class="fas <?= $f['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i> <?= htmlspecialchars($f['msg']) ?>`;
        document.getElementById('toastContainer').appendChild(toast);
        
        // Animate toast
        setTimeout(() => toast.classList.add('show'), 200);
        
        // Fade and redirect after 3s
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
                window.location.href = "index.php";
            }, 500);
        }, 3000);
    <?php endif; ?>
});
</script>

<?php require_once 'footer.php'; ?>
