<?php
session_start();
require_once '../includes/config.php';

// Check login session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_access'])) {
    echo '<div class="alert alert-danger"><p>Not allowed to access. Please login. <a href="../index.php">Click here</a></p></div>';
    exit();
}

$user_id    = $_SESSION['user_id'];
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
    error_log('Error in dashboard: ' . $e->getMessage());
    echo '<div class="alert alert-danger"><p>An error occurred. Please contact the administrator.</p></div>';
    exit();
}

// Initialise variables
$id = $description = $start_to_no = $end_to_no = "";
$error = $success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $description = trim($_POST['description']);
        $start_to_no = $_POST['start_to_no'];
        $end_to_no   = $_POST['end_to_no'];

        if (empty($description) || empty($start_to_no) || empty($end_to_no)) {
            $error = "All fields are required!";
        } elseif ($start_to_no >= $end_to_no) {
            $error = "Start number must be less than end number!";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO tbl_access_definitions (description, start_to_no, end_to_no) VALUES (?, ?, ?)");
                $stmt->execute([$description, $start_to_no, $end_to_no]);
                $success = "Access definition created successfully!";
                // Clear form
                $description = $start_to_no = $end_to_no = "";
            } catch (PDOException $e) {
                $error = "Error creating record: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update'])) {
        $id          = $_POST['id'];
        $description = trim($_POST['description']);
        $start_to_no = $_POST['start_to_no'];
        $end_to_no   = $_POST['end_to_no'];

        if (empty($description) || empty($start_to_no) || empty($end_to_no)) {
            $error = "All fields are required!";
        } elseif ($start_to_no >= $end_to_no) {
            $error = "Start number must be less than end number!";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE tbl_access_definitions SET description = ?, start_to_no = ?, end_to_no = ? WHERE id = ?");
                $stmt->execute([$description, $start_to_no, $end_to_no, $id]);
                $success = "Access definition updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating record: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_access_definitions WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Access definition deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting record: " . $e->getMessage();
        }
    }
}

// Fetch all records
try {
    $stmt = $pdo->query("SELECT * FROM tbl_access_definitions ORDER BY created_at DESC");
    $access_definitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching records: " . $e->getMessage();
}

// Edit record
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM tbl_access_definitions WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_record = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($edit_record) {
            $id          = $edit_record['id'];
            $description = $edit_record['description'];
            $start_to_no = $edit_record['start_to_no'];
            $end_to_no   = $edit_record['end_to_no'];
        }
    } catch (PDOException $e) {
        $error = "Error fetching record: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Definitions - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0f5e8a;
            --primary-light: #e6f2ff;
            --hover-color: #f0f6fb;
        }
        * { box-sizing: border-box; margin:0; padding:0; }

        body {
            font-family: 'Nunito', sans-serif;
            background:#f5f7f9;
            color:#333;
            line-height:1.5;
            font-size:12px;
            display:flex;
            flex-direction:column;
            min-height:100vh;
        }

        /* Header */
        .header{
            position:sticky;top:0;z-index:30;
            backdrop-filter:blur(4px);
            background:rgba(255,255,255,.8);
            border-bottom:1px solid #e7eef6;
            box-shadow:0 1px 8px rgba(0,0,0,.04);
        }
        .header-content{
            display:flex;align-items:center;justify-content:space-between;
            padding:10px 20px;
        }
        .branding{display:flex;align-items:center;gap:10px;}
        .logo{
            width:28px;height:28px;border-radius:6px;
            background:#0f5e8a;color:#fff;display:flex;
            align-items:center;justify-content:center;
            font-weight:600;font-size:14px;
        }
        .brand-text h1{font-size:12px;font-weight:600;color:#1a202c;}
        .brand-text p{font-size:10px;color:#6b7a86;margin-top:-2px;}
        .header-actions{display:flex;align-items:center;gap:10px;}
        .icon-button{
            padding:6px;border-radius:50%;background:none;
            border:none;cursor:pointer;transition:background .2s;
        }
        .icon-button:hover{background:rgba(0,0,0,.05);}
        .notification-badge{
            position:absolute;top:6px;right:6px;
            width:6px;height:6px;background:#e53e3e;
            border-radius:50%;
        }
        .user-profile{
            display:flex;align-items:center;gap:6px;
            background:#fff;border:1px solid #e8f1f8;
            padding:5px 10px;border-radius:6px;
            box-shadow:0 1px 2px rgba(0,0,0,.05);
        }
        .user-avatar{
            width:24px;height:24px;border-radius:50%;
            background:#f0f6fb;display:flex;align-items:center;
            justify-content:center;color:#0f5e8a;font-size:12px;
        }
        .user-name{font-size:11px;font-weight:500;display:none;}

        /* Layout */
        .main-container{display:flex;flex:1;overflow:hidden;}
        .sidebar{
            width:250px;background:#fff;border-right:1px solid #e7eef6;
            flex-shrink:0;transition:transform .3s ease;position:relative;
            z-index:20;
        }
        .sidebar.collapsed{transform:translateX(-100%);}
        .main-content{
            flex:1;padding:20px;background:#f8fafc;overflow-y:auto;
            width:100%;
        }
        .container-fluid-full{width:100%;padding:0;margin:0;}

        .card{
            border:none;box-shadow:0 .125rem .25rem rgba(0,0,0,.075);
            margin-bottom:1rem;
        }
        .table-responsive{
            background:#fff;border-radius:.375rem;
            box-shadow:0 .125rem .25rem rgba(0,0,0,.075);
        }
        .btn-primary{
            background:var(--primary-color);border-color:var(--primary-color);
        }
        .btn-primary:hover{
            background:#0c4d70;border-color:#0c4d70;
        }

        @media (min-width:640px){.user-name{display:block;}}
        @media (max-width:768px){
            .sidebar{position:absolute;height:100%;}
            .main-content{padding:15px;}
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="branding">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">A</div>
                <div class="brand-text">
                    <h1>ADMINISTRATION</h1>
                    <p>Manage the Administration of the Monitoring tool</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="icon-button">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"></span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <div class="user-name"><?php echo htmlspecialchars($user_email); ?></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="main-container">
        <!-- Sidebar (collapsible on mobile) -->
        <aside class="sidebar" id="sidebar">
            <?php include 'sidebar.php'; ?>
        </aside>

        <!-- Full-width content -->
        <main class="main-content container-fluid-full">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 mb-0">Access Definitions</h1>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearForm()">
                    <i class="fas fa-plus"></i> Add New
                </button>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas <?php echo $id ? 'fa-edit' : 'fa-plus'; ?>"></i>
                        <?php echo $id ? 'Edit Access Definition' : 'Create New Access Definition'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="description" class="form-label">Description *</label>
                                <input type="text" class="form-control" id="description" name="description"
                                       value="<?php echo htmlspecialchars($description); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="start_to_no" class="form-label">Start Number *</label>
                                <input type="number" class="form-control" id="start_to_no" name="start_to_no"
                                       value="<?php echo $start_to_no; ?>" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="end_to_no" class="form-label">End Number *</label>
                                <input type="number" class="form-control" id="end_to_no" name="end_to_no"
                                       value="<?php echo $end_to_no; ?>" min="1" required>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <?php if ($id): ?>
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update
                                </button>
                                <button type="button" onclick="clearForm()" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            <?php else: ?>
                                <button type="submit" name="create" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i> Access Definitions List
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($access_definitions)): ?>
                        <div class="alert alert-info m-3">
                            <i class="fas fa-info-circle"></i> No access definitions found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Description</th>
                                        <th>Start Number</th>
                                        <th>End Number</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($access_definitions as $definition): ?>
                                        <tr>
                                            <td><?php echo $definition['id']; ?></td>
                                            <td><?php echo htmlspecialchars($definition['description']); ?></td>
                                            <td><?php echo $definition['start_to_no']; ?></td>
                                            <td><?php echo $definition['end_to_no']; ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($definition['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?edit=<?php echo $definition['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="id" value="<?php echo $definition['id']; ?>">
                                                        <button type="submit" name="delete" class="btn btn-outline-danger"
                                                                onclick="return confirm('Are you sure you want to delete this access definition?')">
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
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearForm() {
            window.location.href = 'access_definition.php';
        }
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const start = parseInt(document.getElementById('start_to_no').value);
                    const end   = parseInt(document.getElementById('end_to_no').value);
                    if (start >= end) {
                        e.preventDefault();
                        alert('Start number must be less than end number!');
                    }
                });
            }
        });
    </script>
</body>
</html>