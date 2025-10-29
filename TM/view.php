<?php
require_once '../includes/auth.php';
require_once 'data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    updateTaskStatus($_POST['id'], $_POST['status'], $current_staff);
    header("Location: view.php?id=" . $_POST['id']);
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

<div class="page-header">
    <h1 class="page-title">Assignment Details</h1>
    <a href="index.php" class="back-btn">Back</a>
</div>

<div class="assignment-container">
    <div class="assignment-left">
        <div class="card detail-card">
            <h2 class="card-title">Task Information</h2>
            <div class="detail-row"><span class="label">Title:</span> <span class="value"><?= htmlspecialchars($task['title']) ?></span></div>
            <div class="detail-row"><span class="label">Description:</span> <span class="value"><?= nl2br(htmlspecialchars($task['description'])) ?></span></div>
            <div class="detail-row"><span class="label">From:</span> <span class="value"><?= htmlspecialchars($task['assigned_by_name']) ?></span></div>
            <div class="detail-row"><span class="label">To:</span> <span class="value"><?= htmlspecialchars($task['assigned_to_name']) ?></span></div>
            <div class="detail-row"><span class="label">Due Date:</span> <span class="value"><?= formatDate($task['due_date']) ?></span></div>
            <div class="detail-row"><span class="label">Created:</span> <span class="value"><?= formatDate($task['created_at']) ?></span></div>
            <div class="detail-row"><span class="label">Priority:</span> <span class="value"><span class="badge badge-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span></span></div>
            <div class="detail-row"><span class="label">Status:</span> <span class="value"><span class="badge badge-<?= str_replace('_', '-', $task['status']) ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span></span></div>
        </div>
    </div>

  <div class="assignment-right">
    <div class="card status-card">
        <h2 class="card-title">Update Status</h2>
        <form method="POST" class="status-form">
            <input type="hidden" name="id" value="<?= $task['task_id'] ?>">
            
            <!-- Status Buttons -->
            <button type="submit" name="status" value="pending" 
                class="btn <?= $task['status'] === 'pending' ? 'btn-primary' : 'btn-ghost' ?>">
                Pending
            </button>
            
            <button type="submit" name="status" value="in_progress" 
                class="btn <?= $task['status'] === 'in_progress' ? 'btn-primary' : 'btn-ghost' ?>">
                In Progress
            </button>
            
            <button type="submit" name="status" value="completed" 
                class="btn <?= $task['status'] === 'completed' ? 'btn-primary' : 'btn-ghost' ?>">
                Completed
            </button>
        </form>
    </div>
    
</div>



    </div>
     <?php if (!empty($task['attachments'])): ?>
            <div class="card attachment-card">
                <h2 class="card-title">Attachments (<?= count($task['attachments']) ?>)</h2>
                <ul class="attachment-list">
                    <?php foreach ($task['attachments'] as $file): ?>
                        <li class="attachment-item">
                            <span class="file-name"><?= htmlspecialchars($file) ?></span>
                            <div class="attachment-actions">
                                <a href="download.php?file=<?= urlencode($file) ?>&mode=preview" target="_blank" class="btn btn-ghost">Preview</a>
                                <a href="download.php?file=<?= urlencode($file) ?>&mode=download" class="btn btn-primary">Download</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
</div>


<?php require_once 'footer.php'; ?>