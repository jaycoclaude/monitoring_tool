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
                    if (in_array(strtolower($ext), ['pdf', 'doc', 'docx', 'jpg', 'png', 'zip'])) {
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
            ? ['type' => 'success', 'msg' => 'Task submitted for review.']
            : ['type' => 'error', 'msg' => 'Failed to submit for review.'];
        header("Location: view.php?id=$task_id");
        exit;
    }

    // ----- 2. Approve (assigner) -----
    if (isset($_POST['approve'])) {
        $ok = approveTask($task_id, getCurrentStaffId());
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Task approved & marked completed.']
            : ['type' => 'error', 'msg' => 'Approval failed.'];
        header("Location: view.php?id=$task_id");
        exit;
    }

    // ----- 3. Return for Re-do (assigner) -----
    if (isset($_POST['return_redo'])) {
        $reason = trim($_POST['redo_reason'] ?? '');
        $ok = returnTaskForRedo($task_id, getCurrentStaffId(), $reason);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Task returned for re-do.']
            : ['type' => 'error', 'msg' => 'Return failed.'];
        header("Location: view.php?id=$task_id");
        exit;
    }

    // ----- Existing status update (pending / in_progress) -----
    if (isset($_POST['status'])) {
        $ok = updateTaskStatus($task_id, $_POST['status'], getCurrentStaffId());
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Status updated.']
            : ['type' => 'error', 'msg' => 'Update failed.'];
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
function getCurrentStaffId(): int
{
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
<link rel="stylesheet" href="assets/styles.css">


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
                            <?= $task['status'] === 'completed' ? 'fa-check-circle' : ($task['status'] === 'in_progress' ? 'fa-spinner' : ($task['status'] === 'pending' ? 'fa-pause-circle' : ($task['status'] === 'review' ? 'fa-eye' : 'fa-times-circle'))) ?>">
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
<?php if (can('submit_review') && $task['assigned_to'] == getCurrentStaffId() && !in_array($task['status'], ['completed', 'review'])): ?>
    <div class="card">
        <h2 class="card-title"><i class="fa-solid fa-share-square"></i> Submit for Review</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">

            <div class="form-group">
                <label for="review_comment"><i class="fa-solid fa-comment-dots"></i> Comment</label>
                <textarea name="review_comment" id="review_comment" placeholder="Enter any remarks or summary..." rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label for="review_attachments"><i class="fa-solid fa-paperclip"></i> Attach Files (optional)</label>
                <input type="file" name="review_attachments[]" id="review_attachments" multiple accept=".pdf,.doc,.docx,.jpg,.png,.zip">
            </div>

            <div class="form-actions" style="text-align:center; margin-top:10px;">
                <button type="submit" name="submit_review" class="btn btn-review">
                    <i class="fa-solid fa-paper-plane"></i> Submit for Review
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>




        <!-- 3. Approve / Return (assigner only) -->
        <?php if ($task['assigned_by'] == $current_user_id && $task['status'] === 'review'): ?>
            <div class="card review-decision">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fa-solid fa-gavel"></i> Review Decision
                    </h2>
                </div>

                <div class="card-body">
                    <form method="POST" class="decision-form">
                        <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">

                        <div class="form-group">
                            <label for="redo_reason"><i class="fa-solid fa-comment-dots"></i> Comment</label>
                            <textarea name="redo_reason" id="redo_reason" placeholder="Enter Comment..." rows="3" required style="width:100%;"></textarea>
                        </div><br>

                        <div class="decision-actions" style="display: flex; justify-content: center; gap: 15px;">
                            <?php if ($task['assigned_by'] == $current_user_id && $task['status'] === 'review'): ?>
                                <?php if (can('return_task')): ?>
                                    <button type="submit" name="return_redo" class="btn btn-return">Return for Re-do</button>
                                <?php endif; ?>

                                <?php if (can('approve_task')): ?>
                                    <button type="submit" name="approve" class="btn btn-approve">Approve (Complete)</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

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
<?php endif; ?> <br><br>

<!-- Task Discussion (Assignee + Assigner Comments) -->
<?php if ($review || !empty($updates)): ?>
    <div class="card review-box">
        <h3><i class="fa-solid fa-comments"></i> Task Discussion</h3>

        <?php if ($review): ?>
            <div class="comment-item highlight">
                <div class="comment-header">
                    <strong><i class="fa-solid fa-user-check"></i> <?= htmlspecialchars($review['submitted_by_name']) ?></strong>
                    <span class="comment-date"><?= date('M j, Y H:i', strtotime($review['created_at'])) ?></span>
                </div>
                <?php if ($review['comment']): ?>
                    <div class="comment-body">
                        <?= nl2br(htmlspecialchars($review['comment'])) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($review['attachments'])): ?>
                    <div class="comment-attachments">
                        <strong><i class="fa-solid fa-paperclip"></i> Attachments:</strong>
                        <?php foreach ($review['attachments'] as $f): ?>
                            <a href="download.php?file=<?= urlencode($f) ?>&mode=preview" target="_blank">
                                <?= htmlspecialchars($f) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($updates)): ?>
            <?php foreach ($updates as $update): ?>
                <div class="comment-item">
                    <div class="comment-header">
                        <strong>
                            <?php if ($update['user_email'] == $task['assigned_by_email'] ?? false): ?>
                                <i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($update['user_email']) ?> (Assigner)
                            <?php elseif ($update['user_email'] == $task['assigned_to_email'] ?? false): ?>
                                <i class="fa-solid fa-user"></i> <?= htmlspecialchars($update['user_email']) ?> (Assignee)
                            <?php else: ?>
                                <i class="fa-solid fa-user"></i> <?= htmlspecialchars($update['user_email'] ?? 'Unknown') ?>
                            <?php endif; ?>
                        </strong>
                        <span class="comment-date">
                            <?= date('M j, Y H:i', strtotime($update['created_at'])) ?>
                        </span>
                    </div>

                    <?php if (!empty($update['comment'])): ?>
                        <div class="comment-body">
                            <?= nl2br(htmlspecialchars($update['comment'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $attachments = !empty($update['report_filename'])
                        ? json_decode($update['report_filename'], true)
                        : [];
                    ?>
                    <?php if (!empty($attachments)): ?>
                        <div class="comment-attachments">
                            <strong><i class="fa-solid fa-paperclip"></i> Attachments:</strong>
                            <?php foreach ($attachments as $f): ?>
                                <a href="download.php?file=<?= urlencode($f) ?>&mode=preview" target="_blank">
                                    <?= htmlspecialchars($f) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
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
    }, 1000);
</script>

<?php require_once 'footer.php'; ?>