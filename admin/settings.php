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

// Fetch dashboard statistics
try {
    // User statistics
    $total_users = $pdo->query("SELECT COUNT(*) FROM tbl_hm_users")->fetchColumn();
    $active_users = $pdo->query("SELECT COUNT(*) FROM tbl_hm_users WHERE user_status = 1")->fetchColumn();
    
    // Application statistics (adjust table names as needed)
    $total_applications = $pdo->query("SELECT COUNT(*) FROM tbl_applications")->fetchColumn();
    $pending_applications = $pdo->query("SELECT COUNT(*) FROM tbl_applications WHERE status = 'pending'")->fetchColumn();
    
    // Recent activity
    $recent_users = $pdo->query("SELECT user_email, created_at FROM tbl_hm_users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_OBJ);
    
} catch (Exception $e) {
    error_log('Error fetching dashboard data: ' . $e->getMessage());
    $total_users = $active_users = $total_applications = $pending_applications = 0;
    $recent_users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>MA - Administration Dashboard</title>
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
            background-color: #0f5e8a;
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
        .icon-button {
            padding: 6px;
            border-radius: 50%;
            background: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .icon-button:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .notification-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 6px;
            height: 6px;
            background-color: #e53e3e;
            border-radius: 50%;
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
            color: #0f5e8a;
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
        }
        .content-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
        }
        .content-header p {
            font-size: 12px;
            color: #6b7a86;
            margin-top: 5px;
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e7eef6;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card-title {
            font-size: 12px;
            color: #6b7a86;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 5px;
        }
        .card-change {
            font-size: 11px;
            color: #10b981;
        }
        .card-change.negative {
            color: #ef4444;
        }
        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .card-icon.users {
            background-color: #e6f2ff;
            color: #0f5e8a;
        }
        .card-icon.applications {
            background-color: #e6fff2;
            color: #0f8a5e;
        }
        .card-icon.pending {
            background-color: #fff2e6;
            color: #8a5e0f;
        }
        .card-icon.completed {
            background-color: #f2e6ff;
            color: #5e0f8a;
        }
        
        /* Recent Activity */
        .activity-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .activity-section {
                grid-template-columns: 1fr;
            }
        }
        .activity-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e7eef6;
        }
        .activity-card h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e7eef6;
        }
        .activity-list {
            list-style: none;
        }
        .activity-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: #f0f6fb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: #0f5e8a;
        }
        .activity-content {
            flex: 1;
        }
        .activity-text {
            font-size: 12px;
            color: #374151;
            margin-bottom: 2px;
        }
        .activity-time {
            font-size: 10px;
            color: #6b7280;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .quick-action-btn {
            background: white;
            border: 1px solid #e7eef6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .quick-action-btn:hover {
            background: #f8fafc;
            border-color: #0f5e8a;
            transform: translateY(-1px);
        }
        .quick-action-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #e6f2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #0f5e8a;
        }
        .quick-action-text {
            font-size: 11px;
            font-weight: 600;
            color: #374151;
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
    <header class="header">
        <div class="header-content">
            <div class="branding">
                <button class="mobile-menu-toggle">
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
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-name"><?php echo $user_email; ?></div>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header">
                <h2>Dashboard Overview</h2>
                <p>Welcome back! Here's what's happening with your system today.</p>
            </div>

            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-title">
                        <span>Total Users</span>
                    </div>
                    <div class="card-value"><?php echo $total_users; ?></div>
                    <div class="card-change positive">+<?php echo $active_users; ?> active</div>
                </div>
                <div class="card">
                    <div class="card-icon applications">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="card-title">
                        <span>Total Applications</span>
                    </div>
                    <div class="card-value"><?php echo $total_applications; ?></div>
                    <div class="card-change">All time</div>
                </div>
                <div class="card">
                    <div class="card-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="card-title">
                        <span>Pending Actions</span>
                    </div>
                    <div class="card-value"><?php echo $pending_applications; ?></div>
                    <div class="card-change">Require attention</div>
                </div>
                <div class="card">
                    <div class="card-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-title">
                        <span>System Status</span>
                    </div>
                    <div class="card-value">Online</div>
                    <div class="card-change positive">All systems operational</div>
                </div>
            </div>

            <div class="activity-section">
                <div class="activity-card">
                    <h3>Recent Activity</h3>
                    <ul class="activity-list">
                        <?php if (count($recent_users) > 0): ?>
                            <?php foreach ($recent_users as $user): ?>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text">New user registered: <?php echo htmlspecialchars($user->user_email); ?></div>
                                        <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($user->created_at)); ?></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="activity-item">
                                <div class="activity-content">
                                    <div class="activity-text">No recent activity</div>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="activity-card">
                    <h3>Quick Actions</h3>
                    <div class="quick-actions">
                        <a href="all_users.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="quick-action-text">Add User</div>
                        </a>
                        <a href="applications.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i class="fas fa-file-import"></i>
                            </div>
                            <div class="quick-action-text">New Application</div>
                        </a>
                        <a href="reports.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="quick-action-text">Generate Report</div>
                        </a>
                        <a href="settings.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="quick-action-text">System Settings</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>