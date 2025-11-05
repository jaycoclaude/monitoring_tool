<?php
require_once '../includes/auth.php';
require_once 'data.php';
$current_staff = $_SESSION['user_id'];

// === HANDLE STATUS UPDATE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $updated = updateTaskStatus($_POST['id'], $_POST['status'], $current_staff);
    $_SESSION['flash'] = $updated
        ? ['type' => 'success', 'msg' => 'Status updated successfully!']
        : ['type' => 'error', 'msg' => 'Failed to update status.'];
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    body { font-family: 'Segoe UI', sans-serif; }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .page-title {
        font-size: 24px;
        font-weight: 600;
        color: #0A3D62;
    }

    .back-btn {
        text-decoration: none;
        color: #0A3D62;
        font-weight: 500;
    }

    .assignment-container {
        display: flex;
        gap: 25px;
        flex-wrap: wrap;
    }

    .card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        padding: 25px;
        flex: 1;
        min-width: 350px;
    }

    .card-title {
        font-size: 18px;
        margin-bottom: 20px;
        color: #0A3D62;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-row {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        min-width: 220px;
    }

    label {
        display: block;
        font-weight: 600;
        color: #0A3D62;
        margin-bottom: 6px;
    }

    input[readonly], textarea[readonly], select[disabled] {
        width: 100%;
        background: #f8f9fa;
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 15px;
        color: #333;
        resize: none;
    }

    textarea { min-height: 90px; }

    .btn {
        border: none;
        border-radius: 8px;
        padding: 10px 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-primary { background-color: #0A3D62; color: #fff; }
    .btn-ghost { background-color: #e9ecef; color: #0A3D62; }

    /* === Status Colors === */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        border-radius: 30px;
        padding: 6px 12px;
        text-transform: capitalize;
        font-size: 14px;
        width: fit-content;
    }

    .status-pending { background-color: #f0f0f0; color: #555; }
    .status-in_progress { background-color: #fff4e5; color: #F0BC12FF; }
    .status-completed { background-color: #e6f4ea; color: #22863a; }
    .status-cancelled { background-color: #fdecea; color: #d73a49; }

    /* Matching button highlights */
    .btn[data-status="pending"].btn-primary { background-color: #555; }
    .btn[data-status="in_progress"].btn-primary { background-color: #E2B009FF; }
    .btn[data-status="completed"].btn-primary { background-color: #22863a; }

    .remarks-form textarea, .attach-form input[type=file] { width: 100%; }

    .toast {
        position: fixed;
        top: 100px;
        right: 20px;
        min-width: 300px;
        background: white;
        border-radius: 14px;
        padding: 16px 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 15px;
        font-weight: 500;
        border-left: 5px solid;
        animation: slideIn 0.4s ease;
        z-index: 10000;
    }

    .toast-success { color: #30a14e; border-left-color: #30a14e; background: #f0fdf4; }
    .toast-error   { color: #d73a49; border-left-color: #d73a49; background: #fdf2f2; }

    @keyframes slideIn {
        from { transform: translateX(120%); opacity: 0; }
        to   { transform: translateX(0); opacity: 1; }
    }

    .attachment-list { list-style: none; padding: 0; }
    .attachment-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 0; border-bottom: 1px solid #f0f0f0;
    }

    .attachment-actions a { margin-left: 10px; }

    .side-forms { display: flex; flex-direction: column; gap: 25px; }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-clipboard-list"></i> Assignment Details</h1>
    <a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="assignment-container">
    <!-- LEFT SIDE: READ-ONLY FORM -->
    <div class="card">
        <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Task Information</h2>
        <form>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fa-solid fa-heading"></i> Title</label>
                    <input type="text" value="<?= htmlspecialchars($task['title']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-flag"></i> Priority</label>
                    <input type="text" value="<?= ucfirst($task['priority']) ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fa-solid fa-user"></i> From</label>
                    <input type="text" value="<?= htmlspecialchars($task['assigned_by_name']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-user-check"></i> To</label>
                    <input type="text" value="<?= htmlspecialchars($task['assigned_to_name']) ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fa-solid fa-calendar-plus"></i> Assigned Date</label>
                    <input type="text" value="<?= formatDate($task['created_at']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-calendar-day"></i> Due Date</label>
                    <input type="text" value="<?= formatDate($task['due_date']) ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label><i class="fa-solid fa-align-left"></i> Description</label>
                    <textarea readonly><?= htmlspecialchars($task['description']) ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fa-solid fa-circle-notch"></i> Status</label>
                    <span class="status-badge status-<?= $task['status'] ?>">
                        <i class="fa-solid 
                            <?= $task['status'] === 'completed' ? 'fa-check-circle' : 
                                ($task['status'] === 'in_progress' ? 'fa-spinner' :
                                ($task['status'] === 'pending' ? 'fa-pause-circle' : 'fa-times-circle')) ?>">
                        </i>
                        <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                    </span>
                </div>
            </div>
        </form>
    </div>

    <!-- RIGHT SIDE: STATUS + REMARKS + ATTACHMENT -->
    <div class="side-forms">
        <!-- Status Form -->
        <div class="card">
            <h2 class="card-title"><i class="fa-solid fa-hourglass-half"></i> Update Status</h2>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $task['task_id'] ?>">
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" name="status" value="pending"
                        class="btn <?= $task['status'] === 'pending' ? 'btn-primary' : 'btn-ghost' ?>" data-status="pending">
                        <i class="fa-solid fa-pause"></i> Pending
                    </button>
                    <button type="submit" name="status" value="in_progress"
                        class="btn <?= $task['status'] === 'in_progress' ? 'btn-primary' : 'btn-ghost' ?>" data-status="in_progress">
                        <i class="fa-solid fa-spinner"></i> In Progress
                    </button>
                    <button type="submit" name="status" value="completed"
                        class="btn <?= $task['status'] === 'completed' ? 'btn-primary' : 'btn-ghost' ?>" data-status="completed">
                        <i class="fa-solid fa-check"></i> Completed
                    </button>
                </div>
            </form>
        </div>

        <!-- Remarks Form -->
        <div class="card">
            <h2 class="card-title"><i class="fa-solid fa-comment-dots"></i> Add Remarks</h2>
            <form class="remarks-form" method="POST" action="add_remark.php">
                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                <textarea name="remark" placeholder="Enter your remarks..." required></textarea>
                <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                    <i class="fa-solid fa-paper-plane"></i> Submit Remark
                </button>
            </form>
        </div>

        <!-- Attachment Upload -->
        <div class="card">
            <h2 class="card-title"><i class="fa-solid fa-paperclip"></i> Add Attachment</h2>
            <form class="attach-form" method="POST" action="upload_attachment.php" enctype="multipart/form-data">
                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                <input type="file" name="attachment" required>
                <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                    <i class="fa-solid fa-upload"></i> Upload
                </button>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($task['attachments'])): ?>
<div class="card" style="margin-top:25px;">
    <h2 class="card-title"><i class="fa-solid fa-paperclip"></i> Attachments (<?= count($task['attachments']) ?>)</h2>
    <ul class="attachment-list">
        <?php foreach ($task['attachments'] as $file): ?>
            <li class="attachment-item">
                <span><i class="fa-solid fa-file"></i> <?= htmlspecialchars($file) ?></span>
                <div class="attachment-actions">
                    <a href="download.php?file=<?= urlencode($file) ?>&mode=preview" target="_blank" class="btn btn-ghost">
                        <i class="fa-solid fa-eye"></i> Preview
                    </a>
                    <a href="download.php?file=<?= urlencode($file) ?>&mode=download" class="btn btn-primary">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- === TOAST === -->
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
document.getElementById('close-toast')?.addEventListener('click', () => window.location.href = 'index.php');
setTimeout(() => {
    if (document.getElementById('toast')) window.location.href = 'index.php';
}, 4000);
</script>

<?php require_once 'footer.php'; ?>
