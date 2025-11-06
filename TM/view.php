<?php
require_once '../includes/auth.php';
require_once 'data.php';
$current_user_id = $_SESSION['user_id'];

// ------------------------------------------------------------------
//  POST HANDLERS
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = (int)($_POST['task_id'] ?? 0);

    // ----- 1. Submit for Review (assignee) -----
    if (isset($_POST['submit_review'])) {
        $comment = trim($_POST['review_comment'] ?? '');
        $uploaded = [];

        if (!empty($_FILES['review_attachments']['name'][0])) {
            $dir = __DIR__ . '/uploads/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            foreach ($_FILES['review_attachments']['name'] as $k => $name) {
                if ($_FILES['review_attachments']['error'][$k] === 0) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext), ['pdf','doc','docx','jpg','png','zip'])) {
                        $file = time() . "_$k.$ext";
                        if (move_uploaded_file($_FILES['review_attachments']['tmp_name'][$k], $dir . $file)) {
                            $uploaded[] = $file;
                        }
                    }
                }
            }
        }

        $ok = submitTaskForReview($task_id, getCurrentStaffId(), $comment, $uploaded);
        $_SESSION['flash'] = $ok
            ? ['type'=>'success','msg'=>'Task submitted for review.']
            : ['type'=>'error','msg'=>'Failed to submit for review.'];
        header("Location: view.php?id=$task_id");
        exit;
    }

    // ----- 2. Approve (assigner) -----
    if (isset($_POST['approve'])) {
        $ok = approveTask($task_id, getCurrentStaffId());
        $_SESSION['flash'] = $ok
            ? ['type'=>'success','msg'=>'Task approved & marked completed.']
            : ['type'=>'error','msg'=>'Approval failed.'];
        header("Location: view.php?id=$task_id");
        exit;
    }

    // ----- 3. Return for Re-do (assigner) -----
    if (isset($_POST['return_redo'])) {
        $reason = trim($_POST['redo_reason'] ?? '');
        $ok = returnTaskForRedo($task_id, getCurrentStaffId(), $reason);
        $_SESSION['flash'] = $ok
            ? ['type'=>'success','msg'=>'Task returned for re-do.']
            : ['type'=>'error','msg'=>'Return failed.'];
        header("Location: view.php?id=$task_id");
        exit;
    }

    // ----- Existing status update (pending / in_progress) -----
    if (isset($_POST['status'])) {
        $ok = updateTaskStatus($task_id, $_POST['status'], getCurrentStaffId());
        $_SESSION['flash'] = $ok
            ? ['type'=>'success','msg'=>'Status updated.']
            : ['type'=>'error','msg'=>'Update failed.'];
        header("Location: view.php?id=$task_id");
        exit;
    }
}

// ------------------------------------------------------------------
//  Load task + review data
// ------------------------------------------------------------------
$task_id = (int)($_GET['id'] ?? 0);
$task    = getTaskById($task_id);
if (!$task) {
    header('Location: index.php');
    exit;
}

$review  = getLatestReview($task_id);   // may be null
$updates = getTaskUpdates($task_id);

// ------------------------------------------------------------------
//  Helper: current staff_id
// ------------------------------------------------------------------
function getCurrentStaffId(): int {
    static $id = null;
    if ($id === null) {
        $db = getDB();
        $stmt = $db->prepare("SELECT staff_id FROM tbl_staff WHERE user_id = ? AND staff_status = 1 LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $id = (int)($stmt->fetchColumn() ?: 0);
    }
    return $id;
}
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
    .status-review { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }

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

    /* New styles */
    .review-box { background: #fff9e6; padding: 20px; border-radius: 12px; margin-top: 25px; border-left: 4px solid #ffc107; }
    .review-box h3 { margin-top: 0; }
    .review-attach a { margin-right: 12px; color: #0A3D62; }
    .btn-review   { background: #ffb302; color: #fff; }
    .btn-approve  { background: #28a745; color: #fff; }
    .btn-return   { background: #dc3545; color: #fff; }
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
                                ($task['status'] === 'pending' ? 'fa-pause-circle' :
                                ($task['status'] === 'review' ? 'fa-eye' : 'fa-times-circle'))) ?>">
                        </i>
                        <?= $task['status'] === 'review' ? 'Under Review' : ucfirst(str_replace('_', ' ', $task['status'])) ?>
                    </span>
                </div>
            </div>
        </form>
    </div>

    <!-- RIGHT SIDE: STATUS + REMARKS + ATTACHMENT + REVIEW ACTIONS -->
    <div class="side-forms">
        <!-- 1. Update Status (existing) -->
        <div class="card">
            <h2 class="card-title"><i class="fa-solid fa-hourglass-half"></i> Update Status</h2>
            <form method="POST">
                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" name="status" value="pending"
                        class="btn <?= $task['status'] === 'pending' ? 'btn-primary' : 'btn-ghost' ?>" data-status="pending">
                        <i class="fa-solid fa-pause"></i> Pending
                    </button>
                    <button type="submit" name="status" value="in_progress"
                        class="btn <?= $task['status'] === 'in_progress' ? 'btn-primary' : 'btn-ghost' ?>" data-status="in_progress">
                        <i class="fa-solid fa-spinner"></i> In Progress
                    </button>
                    <div type="submit" name="status" value="completed"
                        class="btn <?= $task['status'] === 'completed' ? 'btn-primary' : 'btn-ghost' ?>" data-status="completed">
                        <i class="fa-solid fa-check"></i> Completed
</div>
                </div>
            </form>
        </div>

        <!-- 2. Submit for Review (assignee only) -->
        <?php if ($task['assigned_to'] == getCurrentStaffId() && !in_array($task['status'], ['completed', 'review'])): ?>
        <div class="card">
            <h2 class="card-title"><i class="fa-solid fa-paper-plane"></i> Submit for Review</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                <textarea name="review_comment" placeholder="Add a comment (optional)" rows="3"
                          style="width:100%;margin-bottom:8px;"></textarea>
                <input type="file" name="review_attachments[]" multiple
                       accept=".pdf,.doc,.docx,.jpg,.png,.zip" style="margin-bottom:8px;">
                <button type="submit" name="submit_review" class="btn btn-review">
                    <i class="fa-solid fa-check-double"></i> Submit for Review
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- 3. Approve / Return (assigner only) -->
        <?php if ($task['assigned_by'] == $current_user_id && $task['status'] === 'review'): ?>
        <div class="card">
            <h2 class="card-title"><i class="fa-solid fa-gavel"></i> Review Decision</h2>



            <form method="POST" style="display:inline;margin-left:8px;">
                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                <textarea name="redo_reason" placeholder="Reason for return…" rows="2" required
                          style="width:100%;margin:8px 0;"></textarea>
                <button type="submit" name="return_redo" class="btn btn-return">
                    <i class="fa-solid fa-undo"></i> Return for Re-do
                </button>
            </form>
                        <form method="POST" style="display:inline;">
                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                <button type="submit" name="approve" class="btn btn-approve">
                    <i class="fa-solid fa-thumbs-up"></i> Approve (Complete)
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Original Task Attachments -->
<?php if (!empty($task['attachments'])): ?>
<div class="card" style="margin-top:25px;">
    <h2 class="card-title"><i class="fa-solid fa-paperclip"></i> Task Attachments (<?= count($task['attachments']) ?>)</h2>
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

<!-- Review Submission (if exists) -->
<?php if ($review): ?>
<div class="card review-box">
    <h3><i class="fa-solid fa-comment-medical"></i> Review Submission</h3>
    <p><strong>Submitted by:</strong> <?= htmlspecialchars($review['submitted_by_name']) ?>
       on <?= date('M j, Y H:i', strtotime($review['created_at'])) ?></p>
    <?php if ($review['comment']): ?>
        <p><strong>Comment:</strong> <?= nl2br(htmlspecialchars($review['comment'])) ?></p>
    <?php endif; ?>
    <?php if (!empty($review['attachments'])): ?>
        <div class="review-attach">
            <strong>Review Attachments:</strong>
            <?php foreach ($review['attachments'] as $f): ?>
                <a href="download.php?file=<?= urlencode($f) ?>&mode=preview" target="_blank">
                    <i class="fa-solid fa-paperclip"></i> <?= htmlspecialchars($f) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- TOAST -->
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