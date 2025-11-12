<?php
session_start();
require_once '../includes/config.php';
$user_access = $_SESSION['user_access'];

if ($user_access !== '100') {
    include '../under_development.php';

    exit();
}
// Check login session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_access'])) {
    echo '<div class="alert alert-danger"><p>Not allowed to access. Please login. <a href="../index.php">Click here</a></p></div>';
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Validate user
try {
    $stmt = $pdo->prepare("SELECT user_id, user_status FROM tbl_hm_users WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$user || $user->user_status != 1) {
        session_destroy();
        echo '<div class="alert alert-danger"><p>Not allowed to access. Please login.</p></div>';
        exit();
    }
} catch (Exception $e) {
    error_log('Error in templates: ' . $e->getMessage());
    echo '<div class="alert alert-danger"><p>An error occurred. Please contact the administrator.</p></div>';
    exit();
}

// Initialize variables
$id = $template_title = $template_description = $document_type_id = $template_version  = $template_file_path = "";
$document_reference_number = $effective_date = "";
$template_status = "draft";
$error = $success = "";
$show_form = false;

// Fetch enabled document types
try {
    $stmt = $pdo->query("SELECT id, document_title FROM tbl_qms_documents WHERE document_status = 'enabled' ORDER BY document_title ASC");
    $document_types = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $error = "Error fetching document types: " . $e->getMessage();
    $document_types = [];
}

// Check if we're in edit mode or should show form
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $show_form = true;
    $edit_id = $_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM tbl_qms_templates WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_template = $stmt->fetch(PDO::FETCH_OBJ);
        
        if ($edit_template) {
            $id = $edit_template->id;
            $template_title = $edit_template->template_title;
            $template_description = $edit_template->template_description;
            $document_type_id = $edit_template->document_type_id;
            $template_version = $edit_template->template_version;
            $document_reference_number = $edit_template->document_reference_number;
            $effective_date = $edit_template->effective_date;
            // $template_content = $edit_template->template_content;
            $template_file_path = $edit_template->template_file_path;
            $template_status = $edit_template->template_status;
        }
    } catch (PDOException $e) {
        $error = "Error fetching template: " . $e->getMessage();
    }
}

// Check if add button was clicked
if (isset($_GET['add'])) {
    $show_form = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        // Create new template
        $template_title = trim($_POST['template_title']);
        $template_description = trim($_POST['template_description']);
        $document_type_id = $_POST['document_type_id'];
        $template_version = trim($_POST['template_version']);
        $document_reference_number = trim($_POST['document_reference_number'] ?? '');
        $effective_date = $_POST['effective_date'] ?? null;
        // $template_content = trim($_POST['template_content']);
        $template_status = $_POST['template_status'];
        
        // Handle file upload
        $uploaded_file_path = "";
        if (isset($_FILES['template_file']) && $_FILES['template_file']['error'] == 0) {
            $upload_dir = '../uploads/templates/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = $_FILES['template_file']['name'];
            $file_size = $_FILES['template_file']['size'];
            $file_tmp = $_FILES['template_file']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt');
            
            if (in_array($file_ext, $allowed_ext)) {
                if ($file_size <= 10485760) {
                    $new_file_name = uniqid('template_', true) . '.' . $file_ext;
                    $file_destination = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $file_destination)) {
                        $uploaded_file_path = $file_destination;
                    } else {
                        $error = "Failed to upload file. Please try again.";
                        $show_form = true;
                    }
                } else {
                    $error = "File size exceeds 10MB limit!";
                    $show_form = true;
                }
            } else {
                $error = "Invalid file type. Allowed: PDF, Word, Excel, Text files.";
                $show_form = true;
            }
        }
        
        // Validation
        if (empty($template_title) || empty($document_type_id)) {
            $error = "Please fill in all required fields!";
            $show_form = true;
        } elseif (!$error) {
            try {
                // Check duplicate title
                $stmt = $pdo->prepare("SELECT id FROM tbl_qms_templates WHERE template_title = ? AND document_type_id = ?");
                $stmt->execute([$template_title, $document_type_id]);
                if ($stmt->fetch()) {
                    $error = "Template title already exists for this document type!";
                    $show_form = true;
                    if ($uploaded_file_path && file_exists($uploaded_file_path)) unlink($uploaded_file_path);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO tbl_qms_templates 
                        (template_title, template_description, document_type_id, template_version, 
                         document_reference_number, effective_date,  template_file_path, 
                         template_status, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $template_title, $template_description, $document_type_id, $template_version,
                        $document_reference_number, $effective_date, 
                        $uploaded_file_path, $template_status, $user_id
                    ]);
                    $success = "Template created successfully!";
                    $show_form = false;
                    // Reset form
                    $template_title = $template_description = $template_file_path = "";
                    $template_version = "1.0"; $document_reference_number = $effective_date = "";
                    $template_status = "draft";
                }
            } catch (PDOException $e) {
                $error = "Error creating template: " . $e->getMessage();
                $show_form = true;
                if ($uploaded_file_path && file_exists($uploaded_file_path)) unlink($uploaded_file_path);
            }
        }
    } elseif (isset($_POST['update'])) {
        // Update template
        $id = $_POST['id'];
        $template_title = trim($_POST['template_title']);
        $template_description = trim($_POST['template_description']);
        $document_type_id = $_POST['document_type_id'];
        $template_version = trim($_POST['template_version']);
        $document_reference_number = trim($_POST['document_reference_number'] ?? '');
        $effective_date = $_POST['effective_date'] ?? null;
        // $template_content = trim($_POST['template_content']);
        $template_status = $_POST['template_status'];
        
        $existing_file = isset($_POST['existing_file']) ? $_POST['existing_file'] : '';
        $final_file_path = $existing_file;
        
        if (isset($_POST['remove_file']) && $_POST['remove_file'] == '1') {
            if ($existing_file && file_exists($existing_file)) unlink($existing_file);
            $final_file_path = "";
        }
        
        if (isset($_FILES['template_file']) && $_FILES['template_file']['error'] == 0) {
            $upload_dir = '../uploads/templates/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_name = $_FILES['template_file']['name'];
            $file_size = $_FILES['template_file']['size'];
            $file_tmp = $_FILES['template_file']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt');
            
            if (in_array($file_ext, $allowed_ext) && $file_size <= 10485760) {
                $new_file_name = uniqid('template_', true) . '.' . $file_ext;
                $file_destination = $upload_dir . $new_file_name;
                if (move_uploaded_file($file_tmp, $file_destination)) {
                    if ($existing_file && file_exists($existing_file)) unlink($existing_file);
                    $final_file_path = $file_destination;
                } else {
                    $error = "Failed to upload file.";
                    $show_form = true;
                }
            } else {
                $error = in_array($file_ext, $allowed_ext) ? "File size exceeds 10MB!" : "Invalid file type.";
                $show_form = true;
            }
        }
        
        if (empty($template_title) || empty($document_type_id)) {
            $error = "Please fill in all required fields!";
            $show_form = true;
        } elseif (!$error) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM tbl_qms_templates WHERE template_title = ? AND document_type_id = ? AND id != ?");
                $stmt->execute([$template_title, $document_type_id, $id]);
                if ($stmt->fetch()) {
                    $error = "Template title already exists for this document type!";
                    $show_form = true;
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE tbl_qms_templates SET 
                        template_title = ?, template_description = ?, document_type_id = ?, 
                        template_version = ?, document_reference_number = ?, effective_date = ?, 
                         template_file_path = ?, template_status = ?, 
                        updated_by = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $template_title, $template_description, $document_type_id, $template_version,
                        $document_reference_number, $effective_date, 
                        $final_file_path, $template_status, $user_id, $id
                    ]);
                    $success = "Template updated successfully!";
                    $show_form = false;
                }
            } catch (PDOException $e) {
                $error = "Error updating template: " . $e->getMessage();
                $show_form = true;
            }
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("SELECT template_file_path FROM tbl_qms_templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch(PDO::FETCH_OBJ);
            
            $stmt = $pdo->prepare("DELETE FROM tbl_qms_templates WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($template && $template->template_file_path && file_exists($template->template_file_path)) {
                unlink($template->template_file_path);
            }
            $success = "Template deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting template: " . $e->getMessage();
        }
    }
}

// Fetch all templates for display
try {
    $stmt = $pdo->query("
        SELECT t.*, 
               dt.document_title as document_type_name,
               u1.user_email as created_by_email 
        FROM tbl_qms_templates t 
        LEFT JOIN tbl_qms_documents dt ON t.document_type_id = dt.id 
        LEFT JOIN tbl_hm_users u1 ON t.created_by = u1.user_id 
        ORDER BY t.created_at DESC
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $error = "Error fetching templates: " . $e->getMessage();
    $templates = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>QMS - Template Management</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* [Your existing CSS - unchanged] */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Nunito', sans-serif; background: #f5f7f9; color: #333; line-height: 1.5; font-size: 12px; display: flex; flex-direction: column; height: 100vh; }
        .header { position: sticky; top: 0; z-index: 30; backdrop-filter: blur(4px); background: rgba(255,255,255,.8); border-bottom: 1px solid #e7eef6; box-shadow: 0 1px 8px rgba(0,0,0,.04); }
        .header-content { display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; }
        .branding { display: flex; align-items: center; gap: 10px; }
        .logo { width: 28px; height: 28px; border-radius: 6px; background: #2c5aa0; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 14px; }
        .brand-text h1 { font-size: 12px; font-weight: 600; color: #1a202c; }
        .brand-text p { font-size: 10px; color: #6b7a86; margin-top: -2px; }
        .header-actions { display: flex; align-items: center; gap: 10px; }
        .user-profile { display: flex; align-items: center; gap: 6px; background: #fff; border: 1px solid #e8f1f8; padding: 5px 10px; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .user-avatar { width: 24px; height: 24px; border-radius: 50%; background: #f0f6fb; display: flex; align-items: center; justify-content: center; color: #2c5aa0; font-size: 12px; }
        .user-name { font-size: 11px; font-weight: 500; display: none; }
        .main-container { display: flex; flex: 1; overflow: hidden; }
        .main-content { flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc; }
        .content-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e7eef6; display: flex; justify-content: space-between; align-items: center; }
        .content-header h2 { font-size: 18px; font-weight: 600; color: #1a202c; margin: 0; }
        .content-header p { font-size: 12px; color: #6b7a86; margin-top: 5px; }
        .btn-add { background: #2c5aa0; border-color: #2c5aa0; color: #fff; font-size: 12px; padding: 6px 12px; }
        .btn-add:hover { background: #1e3d72; border-color: #1e3d72; color: #fff; }
        .form-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,.05); border: 1px solid #e7eef6; margin-bottom: 20px; display: <?php echo $show_form ? 'block' : 'none'; ?>; }
        .form-card h3 { font-size: 14px; font-weight: 600; color: #1a202c; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e7eef6; display: flex; justify-content: space-between; align-items: center; }
        .btn-close { background: none; border: none; color: #6b7280; cursor: pointer; font-size: 16px; padding: 0; }
        .btn-close:hover { color: #374151; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .form-control { font-size: 12px; height: 32px; }
        textarea.form-control { height: auto; }
        .btn { font-size: 12px; padding: 6px 12px; }
        .btn-primary { background: #2c5aa0; border-color: #2c5aa0; }
        .btn-primary:hover { background: #1e3d72; border-color: #1e3d72; }
        .table-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,.05); border: 1px solid #e7eef6; }
        .table-card h3 { font-size: 14px; font-weight: 600; color: #1a202c; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e7eef6; }
        .table { font-size: 12px; }
        .table th { font-weight: 600; background: #f8fafc; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; }
        .status-draft { background: #f3f4f6; color: #374151; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-archived { background: #fee2e2; color: #991b1b; }
        .alert { font-size: 12px; padding: 10px 15px; margin-bottom: 15px; }
        @media (min-width: 640px) { .user-name { display: block; } }
        .mobile-menu-toggle { background: none; border: none; font-size: 18px; color: #4a5568; cursor: pointer; padding: 5px 10px; }
        @media (min-width: 769px) { .mobile-menu-toggle { display: none; } }
        .file-info { background: #f0f6fb; border: 1px solid #d0e3f0; border-radius: 4px; padding: 10px; margin-top: 10px; }
        .file-info i { color: #2c5aa0; margin-right: 5px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="main-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header">
                <div>
                    <h2>Template Management</h2>
                    <p>Create and manage templates for different document types</p>
                </div>
                <div class="header-actions">
                    <a href="?add=true" class="btn btn-add">
                        Add Template
                    </a>
                </div>
            </div>

            <?php if (empty($document_types)): ?>
                <div class="alert alert-warning">
                    No enabled document types found. Please <a href="documents.php">create and enable document types</a> first.
                </div>
            <?php endif; ?>

            <!-- Template Form -->
            <div class="form-card" id="templateForm">
                <h3>
                    <?php echo $id ? 'Edit Template' : 'Create New Template'; ?>
                    <button type="button" class="btn-close" onclick="hideForm()">
                        Close
                    </button>
                </h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <?php if ($id && !empty($template_file_path)): ?>
                        <input type="hidden" name="existing_file" value="<?php echo htmlspecialchars($template_file_path); ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="template_title">Template Title *</label>
                                <input type="text" class="form-control" id="template_title" name="template_title" 
                                       value="<?php echo htmlspecialchars($template_title); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="document_type_id">Document Type *</label>
                                <select class="form-control" id="document_type_id" name="document_type_id" required>
                                    <option value="">Select Document Type</option>
                                    <?php foreach ($document_types as $dt): ?>
                                        <option value="<?php echo $dt->id; ?>" <?php echo $document_type_id == $dt->id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dt->document_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="template_version">Version</label>
                                <input type="text" class="form-control" id="template_version" name="template_version" 
                                       value="<?php echo htmlspecialchars($template_version ?: '1.0'); ?>" placeholder="1.0">
                            </div>
                        </div>
                    </div>

                    <!-- New Fields -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="document_reference_number">Document Reference No.</label>
                                <input type="text" class="form-control" id="document_reference_number" 
                                       name="document_reference_number" 
                                       value="<?php echo htmlspecialchars($document_reference_number); ?>" 
                                       placeholder="e.g. TEMP-2025-001">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="effective_date">Effective Date</label>
                                <input type="date" class="form-control" id="effective_date" 
                                       name="effective_date" 
                                       value="<?php echo $effective_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="template_status">Status</label>
                                <select class="form-control" id="template_status" name="template_status">
                                    <option value="draft" <?php echo $template_status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="active" <?php echo $template_status == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="archived" <?php echo $template_status == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="template_description">Description</label>
                                <textarea class="form-control" id="template_description" name="template_description" rows="2"><?php echo htmlspecialchars($template_description); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="template_file">
                                    Upload Template File (Optional)
                                </label>
                                <input type="file" class="form-control" id="template_file" name="template_file" 
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.txt">
                                <small class="text-muted">
                                    Accepted: PDF, Word, Excel, Text - Max: 10MB
                                </small>
                                <?php if ($id && !empty($template_file_path)): ?>
                                    <div class="file-info" id="currentFileInfo">
                                        Current file: 
                                        <strong><?php echo htmlspecialchars(basename($template_file_path)); ?></strong>
                                        <a href="<?php echo htmlspecialchars($template_file_path); ?>" target="_blank" class="btn btn-xs btn-info" style="margin-left: 10px;">
                                            Download
                                        </a>
                                        <button type="button" class="btn btn-xs btn-danger" onclick="removeFile()" style="margin-left: 5px;">
                                            Remove
                                        </button>
                                        <input type="hidden" name="remove_file" id="remove_file" value="0">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
               
                    
                    <div class="d-flex gap-2">
                        <?php if ($id): ?>
                            <button type="submit" name="update" class="btn btn-primary">
                                Update Template
                            </button>
                        <?php else: ?>
                            <button type="submit" name="create" class="btn btn-primary">
                                Create Template
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" onclick="hideForm()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Templates List -->
            <div class="table-card">
                <h3>
                    Templates List
                    <span class="badge badge-primary" style="font-size: 11px;"><?php echo count($templates); ?> templates</span>
                </h3>

                <?php if (empty($templates)): ?>
                    <div class="alert alert-info">
                        No templates found. Click "Add Template" to create one.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Document Type</th>
                                    <th>Version</th>
                                    <th>Ref. No.</th>
                                    <th>Effective Date</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $temp): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($temp->template_title); ?></strong>
                                            <?php if (!empty($temp->template_file_path)): ?>
                                                <a href="<?php echo htmlspecialchars($temp->template_file_path); ?>" target="_blank" title="Download">
                                                    Download
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($temp->document_type_name ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($temp->template_version); ?></td>
                                        <td><?php echo htmlspecialchars($temp->document_reference_number ?? '—'); ?></td>
                                        <td><?php echo $temp->effective_date ? date('M j, Y', strtotime($temp->effective_date)) : '—'; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $temp->template_status; ?>">
                                                <?php echo ucfirst($temp->template_status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($temp->created_by_email); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($temp->created_at)); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $temp->id; ?>" class="btn btn-outline-primary" title="Edit">
                                                    Edit
                                                </a>
                                                <form method="POST" class="delete-form" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?php echo $temp->id; ?>">
                                                    <button type="button" class="btn btn-outline-danger delete-btn" 
                                                            data-id="<?php echo $temp->id; ?>"
                                                            data-title="<?php echo htmlspecialchars($temp->template_title); ?>"
                                                            title="Delete">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 Data -->
    <?php if ($error || $success): ?>
    <script>
        window._swalData = {
            type: <?php echo $error ? "'error'" : "'success'"; ?>,
            title: <?php echo $error ? "JSON.stringify('Error')" : "JSON.stringify('Success')"; ?>,
            text: <?php echo json_encode($error ?: $success); ?>
        };
    </script>
    <?php endif; ?>

    <script>
        function hideForm() {
            document.getElementById('templateForm').style.display = 'none';
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        }

        <?php if ($show_form): ?>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('templateForm').style.display = 'block';
        });
        <?php endif; ?>

        function showSwal() {
            if (!window._swalData) return;
            const {type, title, text} = window._swalData;
            Swal.fire({
                icon: type,
                title: title.replace(/"/g, ''),
                text: text,
                confirmButtonColor: '#2c5aa0'
            }).then(() => {
                if (type === 'success') hideForm();
            });
        }

        function removeFile() {
            Swal.fire({
                title: 'Remove this file?',
                text: "The file will be removed when you save.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove it!'
            }).then(result => {
                if (result.isConfirmed) {
                    document.getElementById('remove_file').value = '1';
                    const el = document.getElementById('currentFileInfo');
                    if (el) {
                        el.innerHTML = '<span style="color: #dc3545;">File will be removed on save.</span>';
                        el.style.background = '#fee2e2';
                    }
                }
            });
        }

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const title = this.dataset.title;
                Swal.fire({
                    title: 'Delete this template?',
                    text: `"${title}" will be permanently deleted!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then(result => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `<input type="hidden" name="id" value="${id}"><input type="hidden" name="delete" value="1">`;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });

        document.getElementById('template_file')?.addEventListener('change', function () {
            if (this.files[0]) {
                const size = (this.files[0].size / 1024 / 1024).toFixed(2);
                if (this.files[0].size > 10485760) {
                    Swal.fire({icon: 'error', title: 'Too Large', text: `File is ${size} MB. Max 10MB.`});
                    this.value = '';
                }
            }
        });

        document.addEventListener('DOMContentLoaded', showSwal);
    </script>
</body>
</html>