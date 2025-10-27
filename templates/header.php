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
            width: 320px;
            flex-shrink: 0;
            padding: 16px;
            display: none;
            overflow-y: auto;
        }
        
        @media (min-width: 768px) {
            .sidebar {
                display: block;
            }
        }
        
        .sidebar-content {
            height: 100%;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(13, 40, 63, 0.06);
            border: 1px solid #e7f0f6;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        
        .roadmap h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 12px;
            margin-top: 16px;
        }
        
        .roadmap h3:first-child {
            margin-top: 0;
        }
        
        .roadmap-list {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
            margin-bottom: 20px;
        }
        
        .roadmap-list li {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            background-color: #f8fafc;
            border-left: 4px solid #0f5e8a;
            font-size: 14px;
        }
        
        .roadmap-list li.completed {
            background-color: #f0fff4;
            border-left-color: #38a169;
            color: #2d3748;
        }
        
        .roadmap-list li i {
            margin-right: 10px;
            color: #0f5e8a;
        }
        
        .roadmap-list li.completed i {
            color: #38a169;
        }
        
        details {
            margin-bottom: 12px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        details[open] {
            background-color: #f8fafc;
        }
        
        summary {
            padding: 12px 16px;
            background-color: #f1f5f9;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            border-radius: 8px;
        }
        
        summary:hover {
            background-color: #e2e8f0;
        }
        
        summary i {
            margin-right: 10px;
            color: #0f5e8a;
        }
        
        details ul {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }
        
        details ul li {
            padding: 8px 16px 8px 32px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        details ul li:last-child {
            border-bottom: none;
        }
        
        details ul li a {
            text-decoration: none;
            color: #4a5568;
            font-size: 13px;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }
        
        details ul li a:hover {
            color: #0f5e8a;
        }
        
        details ul li a i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        /* Nested details */
        details details {
            margin: 8px 0;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        
        details details summary {
            background-color: #f8fafc;
            font-size: 13px;
            padding: 10px 14px;
        }
        
        details details ul li {
            padding-left: 48px;
        }
        
        .sidebar-bottom {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
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
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #0f5e8a;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7a86;
            margin-top: 4px;
        }
        
        .btn {
            padding: 8px 16px;
            background-color: #0f5e8a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn:hover {
            background-color: #0a4568;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #0f5e8a;
            color: #0f5e8a;
        }
        
        .btn-outline:hover {
            background-color: #f6fbff;
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
                <div class="roadmap">
                    <h3>Application Roadmap</h3>
                    <ul class="roadmap-list">
                        <li class="completed"><i class="fas fa-check-circle"></i> 1. Received Application</li>
                        <li class="completed"><i class="fas fa-search"></i> 2. Screening</li>
                        <li class="completed"><i class="fas fa-tasks"></i> 3. 1st Assessment</li>
                        <li class="completed"><i class="fas fa-clipboard-list"></i> 4. 2nd Assessment</li>
                        <li class="completed"><i class="fas fa-users"></i> 5. Peer Review</li>
                        <li class="completed"><i class="fas fa-check-double"></i> 6. Approval</li>
                    </ul>

                    <h3>Filters & Actions</h3>
                    <details>
                        <summary><i class="fas fa-filter"></i> All Applications</summary>
                        <ul>
                            <li><a href="#"><i class="fas fa-history"></i> Backlog</a></li>
                            <li><a href="#"><i class="fas fa-box"></i> Registered Products</a></li>
                            <li><a href="#"><i class="fas fa-times-circle"></i> Rejected</a></li>
                            <li><a href="#"><i class="fas fa-calendar-times"></i> Expired</a></li>
                        </ul>
                    </details>

                    <details open>
                        <summary><i class="fas fa-sync-alt"></i> Under Process</summary>
                        <ul>
                            <li>
                                <details>
                                    <summary><i class="fas fa-search"></i> Screening</summary>
                                    <ul>
                                        <li><a href="#"><i class="fas fa-hourglass-start"></i> Pending Screening</a></li>
                                        <li><a href="#"><i class="fas fa-spinner"></i> Under Screening</a></li>
                                    </ul>
                                </details>
                            </li>
                            <li>
                                <details>
                                    <summary><i class="fas fa-tasks"></i> Assessment</summary>
                                    <ul>
                                        <li><a href="#"><i class="fas fa-hourglass-half"></i> Pending Assessment</a></li>
                                        <li><a href="#"><i class="fas fa-clipboard-check"></i> Under 1st Assessment</a></li>
                                        <li><a href="#"><i class="fas fa-clipboard-list"></i> Pending 2nd Assessment</a></li>
                                        <li><a href="#"><i class="fas fa-tasks"></i> Under 2nd Assessment</a></li>
                                        <li><a href="#"><i class="fas fa-folder-plus"></i> Pending ADD. DATA 1st Assessment</a></li>
                                        <li><a href="#"><i class="fas fa-file-medical"></i> ADD. DATA, Under 1st Assessment</a></li>
                                        <li><a href="#"><i class="fas fa-folder-plus"></i> Pending ADD. DATA 2nd Assessment</a></li>
                                        <li><a href="#"><i class="fas fa-file-medical"></i> ADD. DATA, Under 2nd Assessment</a></li>
                                        <li><a href="#"><i class="fas fa-user-tie"></i> Manager (1st & 2nd Reports Review)</a></li>
                                    </ul>
                                </details>
                            </li>
                            <li>
                                <details>
                                    <summary><i class="fas fa-question-circle"></i> Queries</summary>
                                    <ul>
                                        <li><a href="#"><i class="fas fa-envelope"></i> Query Letters to be Sent</a></li>
                                        <li><a href="#"><i class="fas fa-reply"></i> Awaiting Applicant's Feedback</a></li>
                                    </ul>
                                </details>
                            </li>
                            <li>
                                <details>
                                    <summary><i class="fas fa-users"></i> Peer Review</summary>
                                    <ul>
                                        <li><a href="#"><i class="fas fa-industry"></i> Pending GMP</a></li>
                                        <li><a href="#"><i class="fas fa-user-check"></i> Pending Peer Review</a></li>
                                        <li><a href="#"><i class="fas fa-check-double"></i> Passed Peer Review</a></li>
                                    </ul>
                                </details>
                            </li>
                        </ul>
                    </details>
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
                    <h1 class="page-title">Application Dashboard</h1>
                    <p class="page-subtitle">Monitor and manage application processing workflow</p>
                </div>
                <div class="cta-section">
                    <button class="btn">
                        <i class="fas fa-plus"></i> New Application
                    </button>
                    <button class="btn btn-outline">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value">24</div>
                    <div class="stat-label">Pending Screening</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">18</div>
                    <div class="stat-label">Under Assessment</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">7</div>
                    <div class="stat-label">Awaiting Feedback</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">12</div>
                    <div class="stat-label">Peer Review</div>
                </div>
            </div>
            
            <div class="content-body">
                <h2>Application Processing Overview</h2>
                <p>This dashboard provides an overview of applications currently being processed through the HMDR system.</p>
                <br>
                <p>Use the filters in the sidebar to view applications at different stages of the review process:</p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li><strong>Screening</strong> - Initial application review and validation</li>
                    <li><strong>Assessment</strong> - Technical evaluation of applications</li>
                    <li><strong>Queries</strong> - Applications awaiting additional information</li>
                    <li><strong>Peer Review</strong> - Applications undergoing expert review</li>
                </ul>
                <br>
                <p>The application roadmap shows the complete process from submission to approval.</p>
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
        <a href="/applications" class="mobile-nav-item">
            <div class="mobile-nav-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <span>Applications</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <div class="mobile-nav-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <span>Reports</span>
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
        document.querySelectorAll('.mobile-nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.mobile-nav-item').forEach(i => {
                    i.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>