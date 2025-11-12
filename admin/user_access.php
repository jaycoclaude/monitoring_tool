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

// Your user access specific code here...

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>MA - User Access</title>
    <!-- Include all the same meta tags and styles -->
</head>
<body>
    <header class="header">
        <!-- Same header as dashboard -->
    </header>

    <div class="main-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <!-- Your user access content here -->
            <div class="content-header">
                <div>
                    <h2>User Access Management</h2>
                    <p>Manage user access levels and permissions</p>
                </div>
            </div>

            <!-- Rest of your user access content -->
        </div>
    </div>

    <!-- Your modals and scripts -->
</body>
</html>