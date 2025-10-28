<?php
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    updateTaskStatus(intval($_POST['id']), $_POST['status']);
    header('Location: view.php?id=' . $_POST['id']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
$task = getTaskById($id);
if (!$task) { header('Location: index.php'); exit; }

function renderStatusBadge($status) {
    $classes = [
        'pending' => 'badge badge-pending',
        'in-progress' => 'badge badge-progress',
        'completed' => 'badge badge-completed'
    ];
    return "<span class='{$classes[$status]}'>{$status}</span>";
}

function renderPriorityBadge($priority) {
    $classes = [
        'low' => 'badge badge-low',
        'medium' => 'badge badge-medium',
        'high' => 'badge badge-high'
    ];
    return "<span class='{$classes[$priority]}'>{$priority}</span>";
}
?>

<div class="page-header">
    <h1 class="page-title">Assignment Details</h1>
    <a href="index.php" class="back-btn">← Back to Assignments</a>
</div>

<div class="assignment-container">
    <!-- LEFT SECTION: Details -->
    <div class="assignment-left">
        <div class="card detail-card">
            <h2 class="card-title">Basic Information</h2>
            <div class="detail-row"><span class="label">Title:</span> <span class="value"><?php echo htmlspecialchars($task['title']); ?></span></div>
            <div class="detail-row"><span class="label">Description:</span> <span class="value"><?php echo nl2br(htmlspecialchars($task['description'])); ?></span></div>
            <div class="detail-row"><span class="label">From:</span> <span class="value"><?php echo htmlspecialchars($task['from']); ?></span></div>
            <div class="detail-row"><span class="label">To:</span> <span class="value"><?php echo htmlspecialchars($task['to']); ?></span></div>
            <div class="detail-row"><span class="label">Due Date:</span> <span class="value"><?php echo formatDate($task['dueDate']); ?></span></div>
            <div class="detail-row"><span class="label">Created At:</span> <span class="value"><?php echo formatDate($task['createdAt']); ?></span></div>
            <div class="detail-row"><span class="label">Priority:</span> <span class="value"><?php echo renderPriorityBadge($task['priority']); ?></span></div>
            <div class="detail-row"><span class="label">Status:</span> <span class="value"><?php echo renderStatusBadge($task['status']); ?></span></div>
        </div>

        <!-- Attachments -->
        <?php if (!empty($task['attachments'])): ?>
        <div class="card attachment-card">
            <h2 class="card-title">Attachments (<?php echo count($task['attachments']); ?>)</h2>
            <ul class="attachment-list">
                <?php foreach ($task['attachments'] as $file): ?>
                <li class="attachment-item">
                    <span class="file-name"><?php echo htmlspecialchars($file); ?></span>
                    <div class="attachment-actions">
                        <a href="download.php?file=<?php echo urlencode($file); ?>&mode=preview" target="_blank" class="btn btn-ghost">Preview</a>
                        <a href="download.php?file=<?php echo urlencode($file); ?>&mode=download" class="btn btn-primary">Download</a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT SECTION: Update Status -->
    <div class="assignment-right">
        <div class="card status-card">
            <h2 class="card-title">Update Status</h2>
            <form method="POST" class="status-form">
                <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                <select name="status" class="status-select">
                    <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in-progress" <?php echo $task['status'] === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                <div class="status-buttons">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <?php if ($task['status'] !== 'completed'): ?>
                        <button type="submit" name="status" value="completed" class="btn btn-success">Mark as Completed</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
