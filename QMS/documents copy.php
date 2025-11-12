<?php
session_start();
require_once '../includes/config.php';

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
    error_log('Error in documents: ' . $e->getMessage());
    echo '<div class="alert alert-danger"><p>An error occurred. Please contact the administrator.</p></div>';
    exit();
}

// Initialize variables
$id = $document_title = $document_version = $effective_date = $document_ref_no = $document_description = $document_type = "";
$document_status = "draft";
$error = $success = "";
$show_form = false;

// Check if we're in edit mode or should show form
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $show_form = true;
    $edit_id = $_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM tbl_documents WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_document = $stmt->fetch(PDO::FETCH_OBJ);
        
        if ($edit_document) {
            $id = $edit_document->id;
            $document_title = $edit_document->document_title;
            $document_version = $edit_document->document_version;
            $effective_date = $edit_document->effective_date;
            $document_ref_no = $edit_document->document_ref_no;
            $document_description = $edit_document->document_description;
            $document_type = $edit_document->document_type;
            $document_status = $edit_document->document_status;
        }
    } catch (PDOException $e) {
        $error = "Error fetching document: " . $e->getMessage();
    }
}

// Check if add button was clicked
if (isset($_GET['add'])) {
    $show_form = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        // Create new document
        $document_title = trim($_POST['document_title']);
        $document_version = trim($_POST['document_version']);
        $effective_date = $_POST['effective_date'];
        $document_ref_no = trim($_POST['document_ref_no']);
        $document_description = trim($_POST['document_description']);
        $document_type = $_POST['document_type'];
        $document_status = $_POST['document_status'];
        
        // Validation
        if (empty($document_title) || empty($document_version) || empty($effective_date) || empty($document_ref_no)) {
            $error = "Please fill in all required fields!";
            $show_form = true;
        } else {
            try {
                // Check if reference number already exists
                $stmt = $pdo->prepare("SELECT id FROM tbl_documents WHERE document_ref_no = ?");
                $stmt->execute([$document_ref_no]);
                if ($stmt->fetch()) {
                    $error = "Document reference number already exists!";
                    $show_form = true;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tbl_documents (document_title, document_version, effective_date, document_ref_no, document_description, document_type, document_status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$document_title, $document_version, $effective_date, $document_ref_no, $document_description, $document_type, $document_status, $user_id]);
                    $success = "Document created successfully!";
                    $show_form = false;
                    // Clear form
                    $document_title = $document_version = $effective_date = $document_ref_no = $document_description = "";
                    $document_type = "Procedure";
                    $document_status = "draft";
                }
            } catch (PDOException $e) {
                $error = "Error creating document: " . $e->getMessage();
                $show_form = true;
            }
        }
    } elseif (isset($_POST['update'])) {
        // Update document
        $id = $_POST['id'];
        $document_title = trim($_POST['document_title']);
        $document_version = trim($_POST['document_version']);
        $effective_date = $_POST['effective_date'];
        $document_ref_no = trim($_POST['document_ref_no']);
        $document_description = trim($_POST['document_description']);
        $document_type = $_POST['document_type'];
        $document_status = $_POST['document_status'];
        
        if (empty($document_title) || empty($document_version) || empty($effective_date) || empty($document_ref_no)) {
            $error = "Please fill in all required fields!";
            $show_form = true;
        } else {
            try {
                // Check if reference number already exists (excluding current document)
                $stmt = $pdo->prepare("SELECT id FROM tbl_documents WHERE document_ref_no = ? AND id != ?");
                $stmt->execute([$document_ref_no, $id]);
                if ($stmt->fetch()) {
                    $error = "Document reference number already exists!";
                    $show_form = true;
                } else {
                    $stmt = $pdo->prepare("UPDATE tbl_documents SET document_title = ?, document_version = ?, effective_date = ?, document_ref_no = ?, document_description = ?, document_type = ?, document_status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$document_title, $document_version, $effective_date, $document_ref_no, $document_description, $document_type, $document_status, $user_id, $id]);
                    $success = "Document updated successfully!";
                    $show_form = false;
                }
            } catch (PDOException $e) {
                $error = "Error updating document: " . $e->getMessage();
                $show_form = true;
            }
        }
    } elseif (isset($_POST['delete'])) {
        // Delete document
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_documents WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Document deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting document: " . $e->getMessage();
        }
    }
}

// Fetch all documents for display
try {
    $stmt = $pdo->query("
        SELECT d.*, u1.user_email as created_by_email 
        FROM tbl_documents d 
        LEFT JOIN tbl_hm_users u1 ON d.created_by = u1.user_id 
        ORDER BY d.created_at DESC
    ");
    $documents = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $error = "Error fetching documents: " . $e->getMessage();
    $documents = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>QMS - Document Management</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f5f7f9;
            color: #333;
            line-height: 1.5;
            padding: 0;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        /* Header Styles */
        .header {
            position: sticky;
            top: 0;
            z-index: 30;
            backdrop-filter: blur(4px);
            background-color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid #e7eef6;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.04);
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
        }
        .branding {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background-color: #2c5aa0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        .brand-text h1 {
            font-size: 12px;
            font-weight: 600;
            color: #1a202c;
        }
        .brand-text p {
            font-size: 10px;
            color: #6b7a86;
            margin-top: -2px;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 6px;
            background-color: white;
            border: 1px solid #e8f1f8;
            padding: 5px 10px;
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .user-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #f0f6fb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2c5aa0;
            font-size: 12px;
        }
        .user-name {
            font-size: 11px;
            font-weight: 500;
            display: none;
        }
        
        /* Main Container */
        .main-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f8fafc;
        }
        .content-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e7eef6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .content-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin: 0;
        }
        .content-header p {
            font-size: 12px;
            color: #6b7a86;
            margin-top: 5px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn-add {
            background-color: #2c5aa0;
            border-color: #2c5aa0;
            color: white;
            font-size: 12px;
            padding: 6px 12px;
        }
        .btn-add:hover {
            background-color: #1e3d72;
            border-color: #1e3d72;
            color: white;
        }
        
        /* Form Styles */
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e7eef6;
            margin-bottom: 20px;
            display: <?php echo $show_form ? 'block' : 'none'; ?>;
        }
        .form-card h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e7eef6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-close {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
        }
        .btn-close:hover {
            color: #374151;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }
        .form-control {
            font-size: 12px;
            height: 32px;
        }
        textarea.form-control {
            height: auto;
        }
        .btn {
            font-size: 12px;
            padding: 6px 12px;
        }
        .btn-primary {
            background-color: #2c5aa0;
            border-color: #2c5aa0;
        }
        .btn-primary:hover {
            background-color: #1e3d72;
            border-color: #1e3d72;
        }
        
        /* Table Styles */
        .table-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e7eef6;
        }
        .table-card h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e7eef6;
        }
        .table {
            font-size: 12px;
        }
        .table th {
            font-weight: 600;
            background-color: #f8fafc;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .status-draft { background-color: #f3f4f6; color: #374151; }
        .status-pending_review { background-color: #fef3c7; color: #92400e; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .status-archived { background-color: #e5e7eb; color: #374151; }
        
        /* Alert Styles */
        .alert {
            font-size: 12px;
            padding: 10px 15px;
            margin-bottom: 15px;
        }

        @media (min-width: 640px) {
            .user-name {
                display: block;
            }
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            background: none;
            border: none;
            font-size: 18px;
            color: #4a5568;
            cursor: pointer;
            padding: 5px 10px;
        }
        @media (min-width: 769px) {
            .mobile-menu-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="main-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header">
                <div>
                    <h2>Document Management</h2>
                    <p>Create, view, edit, and manage QMS documents</p>
                </div>
                <div class="header-actions">
                    <a href="?add=true" class="btn btn-add">
                        <i class="fas fa-plus"></i> Add Document
                    </a>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Document Form -->
            <div class="form-card" id="documentForm">
                <h3>
                    <i class="fas <?php echo $id ? 'fa-edit' : 'fa-plus'; ?>"></i>
                    <?php echo $id ? 'Edit Document' : 'Create New Document'; ?>
                    <button type="button" class="btn-close" onclick="hideForm()">
                        <i class="fas fa-times"></i>
                    </button>
                </h3>
                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="document_title">Document Title *</label>
                                <input type="text" class="form-control" id="document_title" name="document_title" 
                                       value="<?php echo htmlspecialchars($document_title); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="document_version">Version *</label>
                                <input type="text" class="form-control" id="document_version" name="document_version" 
                                       value="<?php echo htmlspecialchars($document_version); ?>" required placeholder="e.g., 1.0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="effective_date">Effective Date *</label>
                                <input type="date" class="form-control" id="effective_date" name="effective_date" 
                                       value="<?php echo $effective_date; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="document_ref_no">Reference Number *</label>
                                <input type="text" class="form-control" id="document_ref_no" name="document_ref_no" 
                                       value="<?php echo htmlspecialchars($document_ref_no); ?>" required placeholder="e.g., QP-001">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="document_type">Document Type</label>
                                <select class="form-control" id="document_type" name="document_type">
                                    <option value="Procedure" <?php echo $document_type == 'Procedure' ? 'selected' : ''; ?>>Procedure</option>
                                    <option value="Policy" <?php echo $document_type == 'Policy' ? 'selected' : ''; ?>>Policy</option>
                                    <option value="Work Instruction" <?php echo $document_type == 'Work Instruction' ? 'selected' : ''; ?>>Work Instruction</option>
                                    <option value="Form" <?php echo $document_type == 'Form' ? 'selected' : ''; ?>>Form</option>
                                    <option value="Manual" <?php echo $document_type == 'Manual' ? 'selected' : ''; ?>>Manual</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="document_status">Status</label>
                                <select class="form-control" id="document_status" name="document_status">
                                    <option value="draft" <?php echo $document_status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="pending_review" <?php echo $document_status == 'pending_review' ? 'selected' : ''; ?>>Pending Review</option>
                                    <option value="approved" <?php echo $document_status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $document_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="archived" <?php echo $document_status == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="document_description">Description</label>
                        <textarea class="form-control" id="document_description" name="document_description" rows="3"><?php echo htmlspecialchars($document_description); ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <?php if ($id): ?>
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Document
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="hideForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        <?php else: ?>
                            <button type="submit" name="create" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Document
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="hideForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Documents List -->
            <div class="table-card">
                <h3>
                    <i class="fas fa-list"></i> Documents List
                    <span class="badge badge-primary" style="font-size: 11px;"><?php echo count($documents); ?> documents</span>
                </h3>
                
                <?php if (empty($documents)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No documents found. Click "Add Document" to create your first document.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Ref No</th>
                                    <th>Title</th>
                                    <th>Version</th>
                                    <th>Type</th>
                                    <th>Effective Date</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($doc->document_ref_no); ?></strong></td>
                                        <td><?php echo htmlspecialchars($doc->document_title); ?></td>
                                        <td><?php echo htmlspecialchars($doc->document_version); ?></td>
                                        <td><?php echo htmlspecialchars($doc->document_type); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($doc->effective_date)); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $doc->document_status; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $doc->document_status)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($doc->created_by_email); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($doc->created_at)); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $doc->id; ?>" class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="id" value="<?php echo $doc->id; ?>">
                                                    <button type="submit" name="delete" class="btn btn-outline-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this document?')" title="Delete">
                                                        <i class="fas fa-trash"></i>
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

    <script>
        // Auto-generate reference number based on document type
        document.addEventListener('DOMContentLoaded', function() {
            const docTypeSelect = document.getElementById('document_type');
            const refNoInput = document.getElementById('document_ref_no');
            
            if (docTypeSelect && refNoInput && !refNoInput.value) {
                docTypeSelect.addEventListener('change', function() {
                    if (!refNoInput.value) {
                        const prefix = this.value.substring(0, 2).toUpperCase();
                        refNoInput.value = prefix + '-001';
                    }
                });
            }
            
            // Set default effective date to today if empty
            const effectiveDateInput = document.getElementById('effective_date');
            if (effectiveDateInput && !effectiveDateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                effectiveDateInput.value = today;
            }
        });

        // Function to hide the form
        function hideForm() {
            document.getElementById('documentForm').style.display = 'none';
            // Clear any edit parameters from URL
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname + '?');
            }
        }

        // Show form if we have errors or are in edit mode
        <?php if ($show_form): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('documentForm').style.display = 'block';
            });
        <?php endif; ?>
    </script>
</body>
</html>