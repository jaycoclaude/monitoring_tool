<?php
require_once '../includes/auth.php';
require_once 'data.php';
$current_staff = $_SESSION['user_id'];

// === HANDLE STATUS UPDATE WITH FLASH ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $updated = updateTaskStatus($_POST['id'], $_POST['status'], $current_staff);
    
    if ($updated) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Status updated successfully!'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to update status.'];
    }

    // Stay on same page to show toast
    header("Location: view.php?id=" . intval($_POST['id']));
    exit;
}

$task_id = intval($_GET['id'] ?? 0);
$task = getTaskById($task_id);
if (!$task) {
    header('Location: index.php');
    exit;
}

$updates = getTaskUpdates($task_id);
?>
<?php require_once 'header.php'; ?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    /* === TOAST NOTIFICATION – ONLY ADDED STYLES === */
    .toast {
        position: fixed;
        top: 100px;
        right: 20px;
        min-width: 300px;
        max-width: 420px;
        background: white;
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
    .toast-success { color: #30a14e; border-left-color: #30a14e; background: #f0fdf4; }
    .toast-error   { color: #d73a49; border-left-color: #d73a49; background: #fdf2f2; }
    .toast i:first-child { font-size: 20px; }
    .toast-close {
        margin-left: auto;
        background: none;
        border: none;
        font-size: 18px;
        color: #8e8e93;
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
</style>

<div class="page-header">
    <h1 class="page-title" style="color:#0A3D62;">
        <i class="fa-solid fa-clipboard-list" style="color:#0A3D62;"></i> Assignment Details
    </h1>
    <a href="index.php" class="back-btn">
        <i class="fa-solid fa-arrow-left" style="color:#0A3D62;"></i> Back
    </a>
</div>

<div class="assignment-container">
    <div class="assignment-left">
        <div class="card detail-card">
            <h2 class="card-title" style="color:#0A3D62;">
                <i class="fa-solid fa-circle-info" style="color:#0A3D62;"></i> Task Information
            </h2>
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-heading" style="color:#0A3D62;"></i> Title:</span> <span class="value"><?= htmlspecialchars($task['title']) ?></span></div>
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-align-left" style="color:#0A3D62;"></i> Description:</span> <span class="value"><?= nl2br(htmlspecialchars($task['description'])) ?></span></div>
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-user" style="color:#0A3D62;"></i> From:</span> <span class="value"><?= htmlspecialchars($task['assigned_by_name']) ?></span></div>
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-user-check" style="color:#0A3D62;"></i> To:</span> <span class="value"><?= htmlspecialchars($task['assigned_to_name']) ?></span></div>
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-calendar-plus" style="color:#0A3D62;"></i> Assigned Date:</span> <span class="value"><?= formatDate($task['created_at']) ?></span></div>
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-calendar-day" style="color:#0A3D62;"></i> Due Date:</span> <span class="value"><?= formatDate($task['due_date']) ?></span></div>
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-flag" style="color:#0A3D62;"></i> Priority:</span> 
                <span class="value"><span class="badge badge-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span></span>
            </div>
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-circle-notch" style="color:#0A3D62;"></i> Status:</span> 
                <span class="value"><span class="badge badge-<?= str_replace('_', '-', $task['status']) ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span></span>
            </div>
        </div>
    </div>

    <div class="assignment-right">
        <div class="card status-card">
            <h2 class="card-title" style="color:#0A3D62;">
                <i class="fa-solid fa-hourglass-half" style="color:#0A3D62;"></i> Update Status
            </h2>
            <form method="POST" class="status-form">
                <input type="hidden" name="id" value="<?= $task['task_id'] ?>">
                
                <button type="submit" name="status" value="pending" 
                    class="btn <?= $task['status'] === 'pending' ? 'btn-primary' : 'btn-ghost' ?>">
                    <i class="fa-solid fa-pause" style="color:#fff;"></i> Pending
                </button>
                
                <button type="submit" name="status" value="in_progress" 
                    class="btn <?= $task['status'] === 'in_progress' ? 'btn-primary' : 'btn-ghost' ?>">
                    <i class="fa-solid fa-spinner" style="color:#fff;"></i> In Progress
                </button>
                
                <button type="submit" name="status" value="completed" 
                    class="btn <?= $task['status'] === 'completed' ? 'btn-primary' : 'btn-ghost' ?>">
                    <i class="fa-solid fa-check" style="color:#fff;"></i> Completed
                </button>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($task['attachments'])): ?>
    <div class="card attachment-card">
        <h2 class="card-title" style="color:#0A3D62;">
            <i class="fa-solid fa-paperclip" style="color:#0A3D62;"></i> Attachments (<?= count($task['attachments']) ?>)
        </h2>
        <ul class="attachment-list">
            <?php foreach ($task['attachments'] as $file): ?>
                <li class="attachment-item">
                    <span class="file-name"><i class="fa-solid fa-file" style="color:#0A3D62;"></i> <?= htmlspecialchars($file) ?></span>
                    <div class="attachment-actions">
                        <a href="download.php?file=<?= urlencode($file) ?>&mode=preview" target="_blank" class="btn btn-ghost">
                            <i class="fa-solid fa-eye" style="color:#0A3D62;"></i> Preview
                        </a>
                        <a href="download.php?file=<?= urlencode($file) ?>&mode=download" class="btn btn-primary">
                            <i class="fa-solid fa-download" style="color:#fff;"></i> Download
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

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

<script>
// === TOAST: CLOSE → REDIRECT TO INDEX ===
document.getElementById('close-toast')?.addEventListener('click', function() {
    window.location.href = 'index.php';
});

// === AUTO-REDIRECT AFTER 4 SECONDS ===
setTimeout(function() {
    if (document.getElementById('toast')) {
        window.location.href = 'index.php';
    }
}, 4000);
</script>

<?php require_once 'footer.php'; ?>