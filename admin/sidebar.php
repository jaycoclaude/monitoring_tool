<?php 
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_email = $_SESSION['user_email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Modern Sidebar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --primary-color: #6366f1;
            --primary-light: #e0e7ff;
            --hover-color: #f8fafc;
            --text-color: #334155;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --transition-speed: 0.3s;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --radius: 12px;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: #334155;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            padding: 25px 0;
            transition: all var(--transition-speed) ease;
            box-shadow: var(--shadow);
            overflow-y: auto;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-header {
            padding: 0 25px 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
        }
        
        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            transition: opacity var(--transition-speed);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header h3 i {
            color: var(--primary-color);
        }
        
        .toggle-sidebar {
            background: var(--primary-light);
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 14px;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toggle-sidebar:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a, .accordion-header {
            display: flex;
            align-items: center;
            padding: 14px 25px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 14px;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            position: relative;
            border-radius: 0;
        }
        
        .sidebar-menu a:hover, .accordion-header:hover {
            background-color: var(--hover-color);
            color: var(--primary-color);
        }
        
        .sidebar-menu a.active, .accordion-header.active {
            background: linear-gradient(90deg, var(--primary-light) 0%, rgba(224, 231, 255, 0.5) 100%);
            color: var(--primary-color);
            font-weight: 600;
            border-right: 4px solid var(--primary-color);
        }
        
        .sidebar-menu i {
            width: 20px;
            margin-right: 15px;
            text-align: center;
            transition: margin var(--transition-speed);
            font-size: 16px;
        }
        
        .sidebar.collapsed .menu-text {
            opacity: 0;
            visibility: hidden;
            transition: opacity var(--transition-speed), visibility var(--transition-speed);
        }
        
        .sidebar.collapsed .sidebar-header h3 {
            opacity: 0;
            visibility: hidden;
        }
        
        .sidebar.collapsed .sidebar-header {
            justify-content: center;
            padding: 0 0 20px;
        }
        
        .sidebar.collapsed .sidebar-menu i {
            margin-right: 0;
        }

        /* New Accordion Dropdown Styles */
        .accordion-item {
            position: relative;
        }
        
        .accordion-header {
            justify-content: space-between;
            padding-right: 20px;
        }
        
        .accordion-header span {
            display: flex;
            align-items: center;
        }
        
        .accordion-indicator {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 12px;
            color: var(--text-light);
        }
        
        .accordion-header.active .accordion-indicator {
            transform: rotate(90deg);
            color: var(--primary-color);
        }
        
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--hover-color);
        }
        
        .accordion-content.open {
            max-height: 500px;
        }
        
        .accordion-content ul {
            list-style: none;
            padding: 0;
        }
        
        .accordion-content li {
            margin: 0;
        }
        
        .accordion-content a {
            padding: 12px 25px 12px 60px;
            font-size: 13.5px;
            color: var(--text-light);
            position: relative;
            transition: all 0.2s;
        }
        
        .accordion-content a:hover {
            background-color: rgba(255, 255, 255, 0.7);
            color: var(--primary-color);
            padding-left: 65px;
        }
        
        .accordion-content a.active {
            background-color: white;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .accordion-content a i {
            font-size: 12px;
            width: 15px;
        }

        /* Badge for notifications */
        .menu-badge {
            background: #ef4444;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 11px;
            margin-left: auto;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .content-header h1 {
            color: #1e293b;
            font-size: 28px;
            font-weight: 700;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            border-top: 4px solid var(--primary-color);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .content-section {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }
        
        .content-section h2 {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .content-section h2 i {
            color: var(--primary-color);
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                height: 100%;
                transform: translateX(-100%);
                box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
                z-index: 1000;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .mobile-menu-toggle {
                display: block;
                background: var(--primary-color);
                border: none;
                font-size: 20px;
                color: white;
                cursor: pointer;
                padding: 10px 15px;
                border-radius: 8px;
            }
        }

        /* Scrollbar styling */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Mobile menu toggle button */
        .mobile-menu-toggle {
            display: none;
        }
        
        /* Footer */
        .sidebar-footer {
            padding: 20px 25px 0;
            margin-top: auto;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-footer .user-avatar {
            width: 36px;
            height: 36px;
            font-size: 14px;
        }
        
        .user-info h4 {
            font-size: 14px;
            font-weight: 600;
        }
        
        .user-info p {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .sidebar.collapsed .user-info {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-th-large"></i> <span class="menu-text">Admin Panel</span></h3>
            <button class="toggle-sidebar" id="toggleSidebar" aria-label="Toggle Sidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active">
                <i class="fas fa-home"></i> <span class="menu-text">Dashboard</span>
            </a></li>
            
            <li class="accordion-item">
                <button class="accordion-header">
                    <span>
                        <i class="fas fa-users"></i> 
                        <span class="menu-text">User Management</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content">
                    <ul>
                        <li><a href="#">
                            <i class="fas fa-list"></i> All Users
                        </a></li>
                        <li><a href="#">
                            <i class="fas fa-user-shield"></i> User Access
                        </a></li>
                        <li><a href="access_definition.php">
                            <i class="fas fa-user-shield"></i> Access Definitions
                        </a></li>
                        <li><a href="#">
                            <i class="fas fa-user-clock"></i> Blocked Users <span class="menu-badge">3</span>
                        </a></li>
                    </ul>
                </div>
            </li>
            
            <li><a href="#">
                <i class="fas fa-file-alt"></i> <span class="menu-text">Applications</span> <span class="menu-badge">12</span>
            </a></li>
            
            <li class="accordion-item">
                <button class="accordion-header">
                    <span>
                        <i class="fas fa-sitemap"></i> 
                        <span class="menu-text">Workflow</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content">
                    <ul>
                        <li><a href="#">
                            <i class="fas fa-list-ol"></i> Stages
                        </a></li>
                        <li><a href="#">
                            <i class="fas fa-exchange-alt"></i> Transitions
                        </a></li>
                        <li><a href="#">
                            <i class="fas fa-cogs"></i> Automation
                        </a></li>
                        <li><a href="#">
                            <i class="fas fa-history"></i> Workflow History
                        </a></li>
                    </ul>
                </div>
            </li>

            <li class="accordion-item">
                <button class="accordion-header">
                    <span>
                        <i class="fas fa-cog"></i> 
                        <span class="menu-text">System Settings</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content">
                     <ul>
                        <li><a href="#">
                            <i class="fas fa-sliders-h"></i> General
                        </a></li>
                        <li><a href="#" class="active">
                            <i class="fas fa-database"></i> Data Management
                        </a></li>
                        <li><a href="#">
                            <i class="fas fa-shield-alt"></i> Security
                        </a></li>
                        <li><a href="#">
                            <i class="fas fa-bell"></i> Notifications
                        </a></li>
                    </ul>
                </div>
            </li>
            
            <li><a href="#">
                <i class="fas fa-chart-bar"></i> <span class="menu-text">Analytics</span>
            </a></li>
            

            
            <li><a href="#">
                <i class="fas fa-history"></i> <span class="menu-text">Audit Logs</span>
            </a></li>
            <li><a href="#">
                <i class="fas fa-question-circle"></i> <span class="menu-text">Help Center</span>
            </a></li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-avatar">JD</div>
            <div class="user-info">
                <h4><?php echo $user_email; ?></h4>
                <p>Administrator</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <!-- <div class="main-content">
        <div class="content-header">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Dashboard Overview</h1>
            <div class="user-profile">
                <div class="user-avatar">JD</div>
                <div>
                    <div style="font-weight: 600;">John Doe</div>
                    <div style="font-size: 13px; color: var(--text-light);">Administrator</div>
                </div>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value">1,254</div>
            </div>
            <div class="stat-card">
                <h3>Active Applications</h3>
                <div class="value">342</div>
            </div>
            <div class="stat-card">
                <h3>Pending Approvals</h3>
                <div class="value">28</div>
            </div>
            <div class="stat-card">
                <h3>System Uptime</h3>
                <div class="value">99.8%</div>
            </div>
        </div>

        <div class="content-section">
            <h2><i class="fas fa-bell"></i> Recent Activity</h2>
            <p>User John Doe submitted a new application at 10:30 AM</p>
            <p>Application #1234 was approved by Admin User at 9:45 AM</p>
            <p>System backup completed successfully at 3:00 AM</p>
        </div>

        <div class="content-section">
            <h2><i class="fas fa-lightbulb"></i> Quick Actions</h2>
            <p>Use the sidebar to navigate to different sections of the admin panel.</p>
            <p>Try clicking on the accordion menus to see the new dropdown functionality.</p>
        </div>
    </div> -->

    <script>
        // Sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            
            // Toggle sidebar collapse/expand
            toggleSidebar.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-chevron-left');
                icon.classList.toggle('fa-chevron-right');
            });

            // Accordion functionality
            const accordionHeaders = document.querySelectorAll('.accordion-header');
            accordionHeaders.forEach(header => {
                header.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const accordionItem = this.parentElement;
                    const accordionContent = this.nextElementSibling;
                    
                    // Toggle active class on header
                    this.classList.toggle('active');
                    
                    // Toggle accordion content
                    if (accordionContent.classList.contains('open')) {
                        accordionContent.classList.remove('open');
                    } else {
                        accordionContent.classList.add('open');
                    }
                });
            });

            // Mobile menu toggle
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.add('open');
                sidebarOverlay.classList.add('active');
            });

            // Close sidebar when clicking overlay on mobile
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                this.classList.remove('active');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    if (!event.target.closest('.sidebar') && !event.target.closest('.mobile-menu-toggle')) {
                        sidebar.classList.remove('open');
                        sidebarOverlay.classList.remove('active');
                    }
                }
            });
        });
    </script>
</body>
</html>