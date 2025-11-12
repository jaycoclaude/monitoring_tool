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

$user_access = $_SESSION['user_access'];

if ($user_access != '100') {
    echo '<div class="alert alert-danger"><p>Not allowed to access ADMIN page. Insufficient permissions.</p> <a> href="../index.php">Click here</a></div>';
    exit();
}

// ... rest of your all_users.php code ...

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>MA - All Users</title>
    <!-- Include all the same meta tags and styles -->
</head>
<body>
    <header class="header">
        <!-- Same header as dashboard -->
    </header>

    <div class="main-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <!-- Your all users content here -->
            <div class="content-header">
                <div>
                    <h2>All Users</h2>
                    <p>Manage system users, access levels, and permissions</p>
                </div>
            </div>

            <!-- Rest of your all users content -->
        </div>
    </div>

    <!-- Your modals and scripts -->
</body>
</html>