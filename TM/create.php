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

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Select2 CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<style>
    .form-container i,
    .page-header i {
        color: #003366; /* Dark blue for all icons */
    }
    .btn-submit i {
        color: #ffffff; /* White icon on submit button */
    }
    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #ccc;
        border-radius: 4px;
        display: flex;
        align-items: center;
    }
    .select2-selection__rendered {
        padding-left: 8px !important;
    }
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fa-solid fa-clipboard-list"></i> Create Assignment
    </h1>
    <a href="index.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<div class="form-container">
    <form method="POST" enctype="multipart/form-data" class="form-grid">
        <div class="form-group">
            <label>
                <i class="fa-solid fa-heading"></i> Title *
            </label>
            <input type="text" name="title" placeholder="Enter task title" required>
        </div>

        <div class="form-group">
            <label>
                <i class="fa-solid fa-user-check"></i> Assign To *
            </label>
            <select name="assignee" class="select2" required>
                <option value="">Select staff...</option>
                <?php foreach ($staff_list as $s): ?>
                    <option value="<?= $s['staff_id'] ?>"><?= htmlspecialchars($s['staff_names']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>
                <i class="fa-solid fa-calendar-day"></i> Due Date *
            </label>
            <input type="date" name="due_date" required>
        </div>

        <div class="form-group full-width">
            <label>
                <i class="fa-solid fa-align-left"></i> Description *
            </label>
            <textarea name="description" placeholder="Describe the task..." required rows="4"></textarea>
        </div>

        <div class="form-group">
            <label>
                <i class="fa-solid fa-flag"></i> Priority
            </label>
            <select name="priority" class="select2">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label>
                <i class="fa-solid fa-paperclip"></i> Attachments
            </label>
            <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.png,.zip">
            <small>
                <i class="fa-solid fa-file"></i> Accepted: PDF, DOC, JPG, PNG, ZIP
            </small>
        </div>

        <div class="form-group full-width" style="text-align:right;">
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-plus-circle"></i> Create Assignment
            </button>
        </div>
    </form>
</div>

<!-- Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true
        });
    });


</script>
<script>
    $(document).ready(function() {
        // Initialize select2
        $('.select2').select2({
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true
        });

        // Prevent selecting past dates
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="due_date"]').setAttribute('min', today);
    });
</script>


<?php require_once 'footer.php'; ?>
