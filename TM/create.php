<?php
require_once '../includes/auth.php';
require_once 'data.php';
$current_staff = $_SESSION['user_id'];

$staff_list = getAllStaff();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        $dir = 'uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        foreach ($_FILES['attachments']['name'] as $k => $name) {
            if ($_FILES['attachments']['error'][$k] === 0) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), ['pdf','doc','docx','jpg','png','zip'])) {
                    $file = time() . "_$k.$ext";
                    if (move_uploaded_file($_FILES['attachments']['tmp_name'][$k], $dir . $file)) {
                        $uploaded[] = $file;
                    }
                }
            }
        }
    }

    addTask([
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'assigned_by' => $current_staff,
        'assigned_to' => $_POST['assignee'],
        'status' => 'pending',
        'priority' => $_POST['priority'],
        'due_date' => $_POST['due_date'], 
        'attachments' => $uploaded
    ]);

    header('Location: index.php');
    exit;
}
?>
<?php require_once 'header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Create Assignment</h1>
    <a href="index.php" class="back-btn">Back</a>
</div>

<div class="form-container">
    <form method="POST" enctype="multipart/form-data" class="form-grid">
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" required>
        </div>

        <div class="form-group">
            <label>Assign To *</label>
            <select name="assignee" required>
                <option value="">Select staff...</option>
                <?php foreach ($staff_list as $s): ?>
                    <option value="<?= $s['staff_id'] ?>"><?= htmlspecialchars($s['staff_names']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Due Date *</label>
            <input type="date" name="due_date" required>
        </div>

        <div class="form-group full-width">
            <label>Description *</label>
            <textarea name="description" required rows="4"></textarea>
        </div>

        <div class="form-group">
            <label>Priority</label>
            <select name="priority">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label>Attachments</label>
            <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.png,.zip">
            <small>PDF, DOC, JPG, PNG, ZIP</small>
        </div>

        <div class="form-group full-width" style="text-align:right;">
            <button type="submit" class="btn-submit">Create Assignment</button>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>