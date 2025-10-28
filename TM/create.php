<?php
require_once 'header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedFiles = [];
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        foreach ($_FILES['attachments']['name'] as $key => $filename) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $tempName = $_FILES['attachments']['tmp_name'][$key];
                $targetFile = $uploadDir . time() . '_' . basename($filename);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf','doc','docx','txt','jpg','jpeg','png','zip']) && move_uploaded_file($tempName, $targetFile)) {
                    $uploadedFiles[] = basename($targetFile);
                }
            }
        }
    }

    addTask([
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'from' => $currentUser,
        'to' => $_POST['assignee'],
        'status' => 'pending',
        'dueDate' => $_POST['dueDate'],
        'createdAt' => date('Y-m-d'),
        'attachments' => $uploadedFiles,
        'priority' => $_POST['priority']
    ]);
    header('Location: index.php'); 
    exit;
}
?>

<div class="page-header">
  <h1 class="page-title">Create Assignment</h1>
  <a href="index.php" class="back-btn">← Back</a>
</div>

<div class="form-container">
  <form method="POST" enctype="multipart/form-data" class="form-grid">
    <div class="form-group">
      <label>Title *</label>
      <input type="text" name="title" required placeholder="Enter assignment title">
    </div>

    <div class="form-group">
      <label>Assign To *</label>
      <select name="assignee" required>
        <option value="">Select assignee...</option>
        <option>Bob Developer</option>
        <option>Charlie Manager</option>
        <option>David Designer</option>
        <option>Eva Tester</option>
      </select>
    </div>

    <div class="form-group">
      <label>Due Date *</label>
      <input type="date" name="dueDate" required>
    </div>

    <div class="form-group full-width">
      <label>Description *</label>
      <textarea name="description" required placeholder="Describe the task..."></textarea>
    </div>

    <div class="form-group">
      <label>Priority</label>
      <select name="priority">
        <option value="low">Low</option>
        <option value="medium" selected>Medium</option>
        <option value="high">High</option>
      </select>
    </div>

    <div class="form-group full-width">
      <label>Attachments</label>
      <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip">
      <small>Allowed: PDF, DOC, JPG, PNG, ZIP</small>
    </div>

    <div class="form-group full-width" style="text-align:right;">
      <button type="submit" class="btn-submit">Create Assignment</button>
    </div>
  </form>
</div>

<?php require_once 'footer.php'; ?>
