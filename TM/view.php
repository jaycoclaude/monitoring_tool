<?php
require_once '../includes/auth.php';
require_once 'data.php';
$current_staff = $_SESSION['user_id'];

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

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-calendar-day" style="color:#0A3D62;"></i> Due Date:</span> <span class="value"><?= formatDate($task['due_date']) ?></span></div>
            <div class="detail-row"><span class="label" style="color:#0A3D62;"><i class="fa-solid fa-calendar-plus" style="color:#0A3D62;"></i> Created:</span> <span class="value"><?= formatDate($task['created_at']) ?></span></div>
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

<?php require_once 'footer.php'; ?>
