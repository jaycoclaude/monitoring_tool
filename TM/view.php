<?php
require_once 'data.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    updateTaskStatus($id, $status);
    header('Location: view.php?id=' . $id);
    exit;
}

// Get task ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$task = getTaskById($id);

if (!$task) {
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>View Assignment – TaskFlow</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container">
  <div class="page-header">
    <h1 class="page-title">Assignment Details</h1>
    <a href="index.php" class="btn ghost">Back</a>
  </div>

  <!-- Assignment Details -->
  <div class="assignment-details">
    <!-- Basic Info -->
    <div class="detail-container">
      <h2 class="detail-title">Basic Information</h2>
      <div class="detail-row"><span class="detail-label">Title:</span><span class="detail-value"><?php echo htmlspecialchars($task['title']); ?></span></div>
      <div class="detail-row"><span class="detail-label">Description:</span><span class="detail-value"><?php echo htmlspecialchars($task['description']); ?></span></div>
      <div class="detail-row"><span class="detail-label">From:</span><span class="detail-value"><?php echo htmlspecialchars($task['from']); ?></span></div>
      <div class="detail-row"><span class="detail-label">To:</span><span class="detail-value"><?php echo htmlspecialchars($task['to']); ?></span></div>
      <div class="detail-row"><span class="detail-label">Due Date:</span><span class="detail-value"><?php echo formatDate($task['dueDate']); ?></span></div>
      <div class="detail-row"><span class="detail-label">Created:</span><span class="detail-value"><?php echo formatDate($task['createdAt']); ?></span></div>
      <div class="detail-row"><span class="detail-label">Priority:</span><span class="detail-value"><?php echo getPriorityBadge($task['priority']); ?></span></div>
      <div class="detail-row"><span class="detail-label">Status:</span><span class="status <?php echo getStatusClass($task['status']); ?>"><?php echo ucfirst(str_replace('-', ' ', $task['status'])); ?></span></div>
    </div>

    <!-- Attachments -->
    <?php if (!empty($task['attachments'])): ?>
    <div class="detail-container">
      <h2 class="detail-title">Attachments (<?php echo count($task['attachments']); ?>)</h2>
      <ul class="attachment-list">
        <?php foreach ($task['attachments'] as $file): ?>
        <li class="attachment-item" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <span><?php echo htmlspecialchars($file); ?></span>
          <a class="btn ghost" href="download.php?file=<?php echo urlencode($file); ?>&mode=preview" target="_blank">Preview</a>
          <a class="btn" href="download.php?file=<?php echo urlencode($file); ?>&mode=download">Download</a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="detail-container">
      <h2 class="detail-title">Actions</h2>

      <form method="POST" style="display: inline;">
        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
        <select name="status" class="form-control" style="width: auto; display: inline-block;">
          <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="in-progress" <?php echo $task['status'] === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
          <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
        </select>
        <button type="submit" class="btn">Update Status</button>
        <?php if ($task['status'] !== 'completed'): ?>
        <form method="POST" style="display: inline;">
          <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
          <input type="hidden" name="status" value="completed">
          <button type="submit" class="btn success">Mark as Completed</button>
        </form>
      <?php endif; ?>
      </form>
    </div>
  </div>
</main>

<script src="assets/script.js"></script>
</body>
</html>

