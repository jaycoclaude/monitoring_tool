<?php
require_once 'data.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file uploads
    $uploadedFiles = [];
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        $uploadDir = 'uploads/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Handle multiple file uploads
        foreach ($_FILES['attachments']['name'] as $key => $filename) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $tempName = $_FILES['attachments']['tmp_name'][$key];
                $targetFile = $uploadDir . time() . '_' . basename($filename);
                
                // Validate file type and move uploaded file
                $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip'];
                $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($fileExt, $allowedTypes)) {
                    if (move_uploaded_file($tempName, $targetFile)) {
                        $uploadedFiles[] = basename($targetFile);
                    }
                }
            }
        }
    }
    
    $newTask = [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'from' => getCurrentUser(),
        'to' => $_POST['assignee'],
        'status' => 'pending',
        'dueDate' => $_POST['dueDate'],
        'createdAt' => date('Y-m-d'),
        'attachments' => $uploadedFiles,
        'priority' => $_POST['priority']
    ];
    
    addTask($newTask);
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Create Assignment – TaskFlow</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container">
  <div class="page-header">
    <h1 class="page-title">Create New Assignment</h1>
    <a href="index.php" class="btn ghost">Back</a>
  </div>
  <div class="form-container">
    <form method="POST" id="assignmentForm" enctype="multipart/form-data">
      <div class="form-group full-width"><label>Title *</label><input name="title" class="form-control" required></div>
      <div class="form-group full-width"><label>Description *</label><textarea name="description" class="form-control" required></textarea></div>
      <div class="form-group"><label>Assign To *</label>
        <select name="assignee" class="form-control" required>
          <option value="">Select...</option>
          <option>Bob Developer</option><option>Charlie Manager</option><option>David Designer</option><option>Eva Tester</option>
        </select>
      </div>
      <div class="form-group"><label>Due Date *</label><input type="date" name="dueDate" class="form-control" required></div>
      <div class="form-group"><label>Priority</label>
        <select name="priority" class="form-control"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option>
        </select>
      </div>
      <div class="form-group full-width">
        <label>Attachments</label>
        <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip">
        <small style="color:#666;font-size:12px;">Allowed formats: PDF, DOC, DOCX, TXT, JPG, PNG, ZIP</small>
      </div>
      <div class="form-group full-width">
        <button type="submit" class="btn" style="width:100%;padding:12px;">Create Assignment</button>
      </div>
    </form>
  </div>
</main>

<script src="assets/script.js"></script>
</body>
</html>

