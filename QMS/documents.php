<?php
session_start();
require_once '../includes/config.php';

// ------------------- SESSION & AUTH -------------------
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_access'])) {
    echo '<div class="alert alert-danger"><p>Not allowed to access. Please login. <a href="../index.php">Click here</a></p></div>';
    exit();
}

$user_id    = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

$user_access = $_SESSION['user_access'];

if ($user_access !== '100') {
    include '../under_development.php';

    exit();
}

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

// ------------------- INITIALIZE VARIABLES -------------------
$id = $document_title = $document_description = "";
$document_status = "enabled";
$error = $success = "";
$show_form = false;

// ------------------- EDIT / ADD MODE -------------------
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $show_form = true;
    $edit_id   = $_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM tbl_qms_documents WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_document = $stmt->fetch(PDO::FETCH_OBJ);

        if ($edit_document) {
            $id                  = $edit_document->id;
            $document_title      = $edit_document->document_title;
            $document_description= $edit_document->document_description;
            $document_status     = $edit_document->document_status;
        }
    } catch (PDOException $e) {
        $error = "Error fetching document: " . $e->getMessage();
    }
}

if (isset($_GET['add'])) {
    $show_form = true;
}

// ------------------- FORM SUBMISSION -------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ---------- CREATE ----------
    if (isset($_POST['create'])) {
        $document_title       = trim($_POST['document_title']);
        $document_description = trim($_POST['document_description']);
        $document_status      = $_POST['document_status'];

        if (empty($document_title)) {
            $error = "Please fill in all required fields!";
            $show_form = true;
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM tbl_qms_documents WHERE document_title = ?");
                $stmt->execute([$document_title]);
                if ($stmt->fetch()) {
                    $error = "Document title already exists!";
                    $show_form = true;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tbl_qms_documents (document_title, document_description, document_status, created_by) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$document_title, $document_description, $document_status, $user_id]);
                    $success = "Document created successfully!";
                    $show_form = false;

                    $document_title = $document_description = "";
                    $document_status = "enabled";
                }
            } catch (PDOException $e) {
                $error = "Error creating document: " . $e->getMessage();
                $show_form = true;
            }
        }

    // ---------- UPDATE ----------
    } elseif (isset($_POST['update'])) {
        $id                  = $_POST['id'];
        $document_title      = trim($_POST['document_title']);
        $document_description= trim($_POST['document_description']);
        $document_status     = $_POST['document_status'];

        if (empty($document_title)) {
            $error = "Please fill in all required fields!";
            $show_form = true;
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM tbl_qms_documents WHERE document_title = ? AND id != ?");
                $stmt->execute([$document_title, $id]);
                if ($stmt->fetch()) {
                    $error = "Document title already exists!";
                    $show_form = true;
                } else {
                    $stmt = $pdo->prepare("UPDATE tbl_qms_documents SET document_title = ?, document_description = ?, document_status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$document_title, $document_description, $document_status, $user_id, $id]);
                    $success = "Document updated successfully!";
                    $show_form = false;
                }
            } catch (PDOException $e) {
                $error = "Error updating document: " . $e->getMessage();
                $show_form = true;
            }
        }

    // ---------- DISABLE (Soft Delete) ----------
   // ---------- DISABLE (Soft Delete) ----------
} elseif (isset($_POST['disable'])) {
        $id = (int)$_POST['id'];

        // ---- DEBUG: log what we receive ----
        error_log("DISABLE REQUEST – id: $id, user_id: $user_id");

        try {
            $sql = "UPDATE tbl_qms_documents 
                    SET document_status = ?, 
                        updated_by = ?, 
                        updated_at = NOW() 
                    WHERE id = ? 
                      AND document_status = 'enabled'";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['disabled', $user_id, $id]);

            // ---- DEBUG: log result ----
            error_log("DISABLE SQL: " . $stmt->queryString);
            error_log("DISABLE rows affected: " . $stmt->rowCount());

            if ($stmt->rowCount() > 0) {
                $success = "Document disabled successfully!";
            } else {
                // No row was updated → already disabled or wrong ID
                $error = "Document is already disabled or not found.";
            }
        } catch (PDOException $e) {
            // ---- REAL ERROR MESSAGE ----
            $error = "DB Error: " . $e->getMessage();
            error_log("DISABLE PDO ERROR: " . $e->getMessage());
        }
    }

    /* ---------- ENABLE (optional) ---------- */
    elseif (isset($_POST['enable'])) {
        $id = (int)$_POST['id'];

        error_log("ENABLE REQUEST – id: $id, user_id: $user_id");

        try {
            $sql = "UPDATE tbl_qms_documents 
                    SET document_status = ?, 
                        updated_by = ?, 
                        updated_at = NOW() 
                    WHERE id = ? 
                      AND document_status = 'disabled'";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['enabled', $user_id, $id]);

            error_log("ENABLE SQL: " . $stmt->queryString);
            error_log("ENABLE rows affected: " . $stmt->rowCount());

            if ($stmt->rowCount() > 0) {
                $success = "Document enabled successfully!";
            } else {
                $error = "Document is already enabled or not found.";
            }
        } catch (PDOException $e) {
            $error = "DB Error: " . $e->getMessage();
            error_log("ENABLE PDO ERROR: " . $e->getMessage());
        }
    }
}

// ------------------- FETCH DOCUMENTS -------------------
try {
    $stmt = $pdo->query("
        SELECT d.*, u1.user_email as created_by_email 
        FROM tbl_qms_documents d 
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

    <!-- Bootstrap 3 -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <style>
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
        .status-enabled { background: #d1fae5; color: #065f46; }
        .status-disabled { background: #fee2e2; color: #991b1b; }
        .alert { font-size: 12px; padding: 10px 15px; margin-bottom: 15px; }
        @media (min-width: 640px) { .user-name { display: block; } }
        .mobile-menu-toggle { background: none; border: none; font-size: 18px; color: #4a5568; cursor: pointer; padding: 5px 10px; }
        @media (min-width: 769px) { .mobile-menu-toggle { display: none; } }
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
                    <p>Create, view, edit, and manage QMS document types</p>
                </div>
                <div class="header-actions">
                    <a href="?add=true" class="btn btn-add">
                        <i class="fas fa-plus"></i> Add Document
                    </a>
                </div>
            </div>

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
                                <label for="document_status">Status</label>
                                <select class="form-control" id="document_status" name="document_status">
                                    <option value="enabled" <?php echo $document_status == 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                                    <option value="disabled" <?php echo $document_status == 'disabled' ? 'selected' : ''; ?>>Disabled</option>
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
                        <?php else: ?>
                            <button type="submit" name="create" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Document
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" onclick="hideForm()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
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
                        <i class="fas fa-info-circle"></i> No documents found. Click "Add Document" to create one.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doc->document_title); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $doc->document_status; ?>">
                                                <?php echo ucfirst($doc->document_status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($doc->created_by_email); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($doc->created_at)); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <!-- Edit -->
                                                <a href="?edit=<?php echo $doc->id; ?>" class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <!-- Disable / Enable -->
                                                <!-- Disable / Enable -->
<?php if ($doc->document_status === 'enabled'): ?>
    <form method="POST" action="" class="disable-form" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo $doc->id; ?>">
        <button type="button" class="btn btn-outline-warning disable-btn" 
                data-id="<?php echo $doc->id; ?>" 
                data-title="<?php echo htmlspecialchars($doc->document_title); ?>" 
                title="Disable">
            <i class="fas fa-toggle-off"></i>
        </button>
    </form>
<?php else: ?>
    <form method="POST" action="" class="enable-form" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo $doc->id; ?>">
        <button type="button" class="btn btn-outline-success enable-btn" 
                data-id="<?php echo $doc->id; ?>" 
                data-title="<?php echo htmlspecialchars($doc->document_title); ?>" 
                title="Enable">
            <i class="fas fa-toggle-on"></i>
        </button>
    </form>
<?php endif; ?>
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
        // Hide form
        function hideForm() {
            document.getElementById('documentForm').style.display = 'none';
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        }

        // Show form if needed
        <?php if ($show_form): ?>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('documentForm').style.display = 'block';
        });
        <?php endif; ?>

        // SweetAlert2 Handler
        function showSwal() {
            if (!window._swalData) return;
            const {type, title, text} = window._swalData;
            Swal.fire({
                icon: type,
                title: title.replace(/"/g, ''),
                text: text,
                confirmButtonColor: '#2c5aa0',
                allowOutsideClick: false
            }).then(() => {
                if (type === 'success') hideForm();
            });
        }

        // Disable Confirmation
        // Disable Confirmation
document.querySelectorAll('.disable-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const docId = this.getAttribute('data-id');
        const docTitle = this.getAttribute('data-title');
        
        Swal.fire({
            title: 'Disable this document?',
            text: `"${docTitle}" will be marked as disabled but can be re-enabled later.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, disable it!',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                // Create and submit form programmatically
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = docId;
                
                const disableInput = document.createElement('input');
                disableInput.type = 'hidden';
                disableInput.name = 'disable';
                disableInput.value = '1';
                
                form.appendChild(idInput);
                form.appendChild(disableInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});

// Enable Confirmation
document.querySelectorAll('.enable-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const docId = this.getAttribute('data-id');
        const docTitle = this.getAttribute('data-title');
        
        Swal.fire({
            title: 'Re-enable this document?',
            text: `"${docTitle}" will be re-enabled.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, enable it!',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                // Create and submit form programmatically
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = docId;
                
                const enableInput = document.createElement('input');
                enableInput.type = 'hidden';
                enableInput.name = 'enable';
                enableInput.value = '1';
                
                form.appendChild(idInput);
                form.appendChild(enableInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});

        // Fire SweetAlert2 on load
        document.addEventListener('DOMContentLoaded', showSwal);
    </script>
</body>
</html>