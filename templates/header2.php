<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userEmail = $isLoggedIn ? $_SESSION['user_email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Rwanda FDA Monitoring Tool'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #008751;
            --primary-dark: #006b41;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #6c757d;
            --text-dark: #333;
            --border-color: #dee2e6;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        .main-header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-top {
            background-color: var(--primary-color);
            color: white;
            padding: 0.25rem 0;
            font-size: 0.8rem;
        }
        
        .header-content {
            padding: 0.5rem 0;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .logo {
            max-width: 140px;
            height: auto;
        }
        
        .system-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-left: 0.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        
        .user-welcome {
            margin-right: 1rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .btn-logout {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .btn-logout:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Navigation */
        .main-nav {
            background-color: white;
            border-top: 1px solid var(--border-color);
        }
        
        .nav-link {
            color: var(--text-dark);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background-color: rgba(0, 135, 81, 0.05);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }
        
        /* Footer Styles */
        .main-footer {
            background-color: white;
            border-top: 1px solid var(--border-color);
            margin-top: auto;
        }
        
        .footer-content {
            padding: 2rem 0;
        }
        
        .footer-bottom {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 0;
            font-size: 0.85rem;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .system-title {
                font-size: 1.1rem;
                margin-left: 0.5rem;
            }
            
            .user-welcome {
                display: none;
            }
            
            .nav-link {
                padding: 0.4rem 0.75rem;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-top">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <i class="fas fa-phone-alt me-1"></i> 9707
                    </div>
                    <div class="col-md-6 text-end">
                        <i class="fas fa-envelope me-1"></i> info@rwandafda.gov.rw
                    </div>
                </div>
            </div>
        </div>
        
        <div class="header-content">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="logo-container">
                            <img src="assets/images/logo.png" alt="Rwanda FDA Logo" class="logo">
                            <div class="system-title">Monitoring Tool</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php if ($isLoggedIn): ?>
                        <div class="user-info">
                            <div class="user-welcome">
                                <i class="fas fa-user-circle me-1"></i> Welcome, <?php echo htmlspecialchars(explode('@', $userEmail)[0]); ?>
                            </div>
                            <a href="logout.php" class="btn-logout">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($isLoggedIn): ?>
        <nav class="main-nav">
            <div class="container">
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-chart-line me-1"></i> Monitoring
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-file-alt me-1"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-cog me-1"></i> Settings
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        <?php endif; ?>
    </header>

    <main class="main-content">
        <div class="container">
            <!-- Removed breadcrumb and page header section -->
