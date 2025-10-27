<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMDR Monitoring Tool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: #203443;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* === Topbar === */
        .header {
            position: sticky;
            top: 0;
            z-index: 30;
            backdrop-filter: blur(4px);
            background-color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid #e7eef6;
            box-shadow: 0 1px 10px rgba(0, 0, 0, 0.04);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 24px;
        }
        
        .branding {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background-color: #0f5e8a;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }
        
        .brand-text h1 {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
        }
        
        .brand-text p {
            font-size: 11px;
            color: #6b7a86;
            margin-top: -2px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .icon-button {
            position: relative;
            padding: 8px;
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
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background-color: #e53e3e;
            border-radius: 50%;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: white;
            border: 1px solid #e8f1f8;
            padding: 6px 12px;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #f0f6fb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f5e8a;
        }
        
        .user-name {
            font-size: 12px;
            font-weight: 500;
            display: none;
        }
        
        @media (min-width: 640px) {
            .user-name {
                display: block;
            }
        }
        
        /* === Layout Body === */
        .main-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            padding: 16px;
            display: none;
        }
        
        @media (min-width: 768px) {
            .sidebar {
                display: block;
            }
        }
        
        .sidebar-content {
            height: 100%;
            background-color: white;
            border-radius: 24px;
            box-shadow: 0 6px 20px rgba(13, 40, 63, 0.06);
            border: 1px solid #e7f0f6;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .sidebar-top {
            padding: 16px;
        }
        
        .module-section {
            background-color: #f9fafb;
            border-radius: 12px;
            padding: 12px;
        }
        
        .module-title {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            padding-left: 8px;
        }
        
        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: #4a5568;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .nav-item:hover {
            background-color: #f0f6fb;
            color: #0f5e8a;
        }
        
        .nav-item.active {
            background-color: #e6f2fa;
            color: #0f5e8a;
            font-weight: 500;
        }
        
        .nav-icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-bottom {
            padding: 16px;
        }
        
        .user-card {
            background-color: #f6fbff;
            border: 1px solid #e6eef6;
            padding: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-card-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #eef6fb;
            overflow: hidden;
        }
        
        .user-card-info {
            max-width: 100%;
            overflow: hidden;
        }
        
        .user-card-name {
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-card-email {
            font-size: 12px;
            color: #6b7a86;
            word-break: break-all;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            overflow: auto;
            padding: 24px;
        }
        
        .content-header {
            display: flex;
            flex-direction: column;
            margin-bottom: 16px;
            gap: 16px;
        }
        
        @media (min-width: 768px) {
            .content-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
        
        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a202c;
        }
        
        .page-subtitle {
            font-size: 12px;
            color: #6b7a86;
            margin-top: 2px;
        }
        
        .cta-section {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .content-body {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 24px;
            min-height: 400px;
        }
        
        /* === Footer === */
        .footer {
            background-color: white;
            border-top: 1px solid #e7eef6;
            box-shadow: inset 0 1px 0 rgba(0, 0, 0, 0.05);
            padding: 16px 24px;
            font-size: 14px;
            color: #6b7a86;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-links {
            display: flex;
            gap: 16px;
        }
        
        .footer-link {
            color: #6b7a86;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .footer-link:hover {
            color: #0f5e8a;
        }
        
        /* Mobile Navigation */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            border-top: 1px solid #e7eef6;
            display: flex;
            justify-content: space-around;
            padding: 8px;
            z-index: 40;
        }
        
        @media (min-width: 768px) {
            .mobile-nav {
                display: none;
            }
        }
        
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px;
            text-decoration: none;
            color: #6b7a86;
            font-size: 10px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .mobile-nav-item.active {
            color: #0f5e8a;
            background-color: #e6f2fa;
        }
        
        .mobile-nav-icon {
            margin-bottom: 4px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <!-- === Topbar === -->
    <header class="header">
        <div class="header-content">
            <!-- Left: Branding + Title -->
            <div class="branding">
                <div class="logo">H</div>
                <div class="brand-text">
                    <h1>HMDR Dashboard</h1>
                    <p>Hazard Monitoring & Data Reporting</p>
                </div>
            </div>

            <!-- Right: Quick actions -->
            <div class="header-actions">
                <button class="icon-button">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"></span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-name">Safety Officer</div>
                </div>
            </div>
        </div>
    </header>

    <!-- === Layout Body === -->
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-content">
                <div class="sidebar-top">
                    <div class="module-section">
                        <h2 class="module-title">HMDR Module</h2>
                        <nav class="nav-menu">
                            <a href="/dashboard" class="nav-item active">
                                <div class="nav-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <span>Dashboard</span>
                            </a>
                            <a href="/hazards" class="nav-item">
                                <div class="nav-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <span>Hazard Reports</span>
                            </a>
                            <a href="#" class="nav-item">
                                <div class="nav-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <span>Risk Analysis</span>
                            </a>
                            <a href="#" class="nav-item">
                                <div class="nav-icon">
                                    <i class="fas fa-file-upload"></i>
                                </div>
                                <span>Incident Reports</span>
                            </a>
                            <a href="#" class="nav-item">
                                <div class="nav-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <span>Compliance</span>
                            </a>
                            <a href="#" class="nav-item">
                                <div class="nav-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <span>Safety Protocols</span>
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="sidebar-bottom">
                    <div class="user-card">
                        <div class="user-card-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="user-card-info">
                            <div class="user-card-name">Safety Officer</div>
                            <div class="user-card-email">safety.officer@example.com</div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <div>
                    <h1 class="page-title">Hazard Monitoring Dashboard</h1>
                    <p class="page-subtitle">Monitor and manage workplace hazards and safety incidents</p>
                </div>
                <div class="cta-section">
                    <button class="icon-button">
                        <i class="fas fa-plus"></i> New Report
                    </button>
                    <button class="icon-button">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            <div class="content-body">
                <p>Welcome to the Hazard Monitoring & Data Reporting (HMDR) system. This dashboard provides an overview of current hazards, incident reports, and safety compliance metrics.</p>
                <br>
                <p>Use the navigation menu to access different modules of the system:</p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li><strong>Hazard Reports</strong> - View and manage reported hazards</li>
                    <li><strong>Risk Analysis</strong> - Analyze risk levels and trends</li>
                    <li><strong>Incident Reports</strong> - Document and track safety incidents</li>
                    <li><strong>Compliance</strong> - Monitor regulatory compliance status</li>
                    <li><strong>Safety Protocols</strong> - Access safety procedures and guidelines</li>
                </ul>
            </div>
        </main>
    </div>

    <!-- Mobile Navigation -->
    <nav class="mobile-nav">
        <a href="/dashboard" class="mobile-nav-item active">
            <div class="mobile-nav-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Dashboard</span>
        </a>
        <a href="/hazards" class="mobile-nav-item">
            <div class="mobile-nav-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <span>Hazards</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <div class="mobile-nav-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <span>Analysis</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <div class="mobile-nav-icon">
                <i class="fas fa-cog"></i>
            </div>
            <span>Settings</span>
        </a>
    </nav>

    <!-- === Footer === -->
    <footer class="footer">
        <span>
            © <span id="current-year">2023</span> HMDR Monitoring Tool. All rights reserved.
        </span>
        <div class="footer-links">
            <a href="#" class="footer-link">Privacy Policy</a>
            <a href="#" class="footer-link">Terms of Service</a>
        </div>
    </footer>

    <script>
        // Set current year in footer
        document.getElementById('current-year').textContent = new Date().getFullYear();
        
        // Simple navigation active state management
        document.querySelectorAll('.nav-item, .mobile-nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item, .mobile-nav-item').forEach(i => {
                    i.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>