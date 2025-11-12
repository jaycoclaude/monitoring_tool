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
    <title>QMS Dashboard - Quality Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --sidebar-width: 300px;
            --sidebar-collapsed-width: 70px;
            --primary-color: #2c5aa0;
            --primary-light: #e8efff;
            --hover-color: #f0f7ff;
            --text-color: #334155;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --transition-speed: 0.3s;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --radius: 8px;
            --locked-color: #94a3b8;
            --locked-bg: #f8fafc;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fbff 0%, #f0f7ff 100%);
            color: #334155;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            padding: 20px 0;
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
            padding: 0 20px 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
        }
        
        .sidebar-header h3 {
            font-size: 16px;
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
            padding: 6px;
            border-radius: 6px;
            transition: all 0.2s;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toggle-sidebar:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }
        
        .sidebar-menu li {
            margin-bottom: 2px;
        }
        
        .sidebar-menu a, .accordion-header {
            display: flex;
            align-items: center;
            padding: 12px 20px;
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
            background: linear-gradient(90deg, var(--primary-light) 0%, rgba(232, 239, 255, 0.5) 100%);
            color: var(--primary-color);
            font-weight: 600;
            border-right: 3px solid var(--primary-color);
        }
        
        .sidebar-menu i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
            transition: margin var(--transition-speed);
            font-size: 15px;
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
            padding: 0 0 15px;
        }
        
        .sidebar.collapsed .sidebar-menu i {
            margin-right: 0;
        }

        /* Accordion Dropdown Styles */
        .accordion-item {
            position: relative;
        }
        
        .accordion-header {
            justify-content: space-between;
            padding-right: 15px;
        }
        
        .accordion-header span {
            display: flex;
            align-items: center;
        }
        
        .accordion-indicator {
            transition: transform 0.3s ease;
            font-size: 11px;
            color: var(--text-light);
        }
        
        .accordion-header.active .accordion-indicator {
            transform: rotate(90deg);
            color: var(--primary-color);
        }
        
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: #fafbff;
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
            padding: 10px 20px 10px 52px;
            font-size: 13px;
            color: var(--text-light);
            position: relative;
            transition: all 0.2s;
        }
        
        .accordion-content a:hover {
            background-color: rgba(255, 255, 255, 0.8);
            color: var(--primary-color);
        }
        
        .accordion-content a.active {
            background-color: white;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .accordion-content a i {
            font-size: 11px;
            width: 14px;
        }

        /* Badge for notifications */
        .menu-badge {
            background: #ef4444;
            color: white;
            border-radius: 8px;
            padding: 1px 6px;
            font-size: 10px;
            margin-left: auto;
        }

        /* Section dividers */
        .menu-section {
            padding: 15px 20px 5px;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-light);
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .sidebar.collapsed .menu-section {
            display: none;
        }

        /* Locked menu item styles */
        .locked {
            color: var(--locked-color) !important;
            background-color: var(--locked-bg) !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            position: relative;
        }
        
        .locked:hover {
            background-color: var(--locked-bg) !important;
            color: var(--locked-color) !important;
        }
        
        .locked::after {
            content: "\f023";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: 15px;
            font-size: 11px;
            color: var(--locked-color);
        }
        
        .accordion-header.locked::after {
            right: 35px;
        }
        
        .sidebar.collapsed .locked::after {
            display: none;
        }
        
        .locked .menu-text {
            opacity: 0.7;
        }
        
        .locked i {
            color: var(--locked-color) !important;
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
                font-size: 18px;
                color: white;
                cursor: pointer;
                padding: 8px 12px;
                border-radius: 6px;
            }
        }

        /* Scrollbar styling */
        .sidebar::-webkit-scrollbar {
            width: 4px;
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
            padding: 15px 20px 0;
            margin-top: auto;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-footer .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        .user-info h4 {
            font-size: 13px;
            font-weight: 600;
        }
        
        .user-info p {
            font-size: 11px;
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
            <h3><i class="fas fa-award"></i> <span class="menu-text">QMS Dashboard</span></h3>
            <button class="toggle-sidebar" id="toggleSidebar" aria-label="Toggle Sidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> <span class="menu-text">Dashboard</span>
            </a></li>
            
            <div class="menu-section">Document Management</div>
            
            <li class="accordion-item">
                <button class="accordion-header <?php echo in_array(basename($_SERVER['PHP_SELF']), ['documents.php', 'templates.php', 'document_workflow.php']) ? 'active' : ''; ?>">
                    <span>
                        <i class="fas fa-file-alt"></i> 
                        <span class="menu-text">Document Control</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content <?php echo in_array(basename($_SERVER['PHP_SELF']), ['documents.php', 'templates.php', 'document_workflow.php']) ? 'open' : ''; ?>">
                    <ul>
                        <li><a href="#" class="<?php echo basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-file"></i> All Documents
                        </a></li>
                        <li><a href="documents.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : ''; ?>">
                            <i class="fas fa-file"></i> Documents Types
                        </a></li>
                        <li><a href="templates.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'templates.php' ? 'active' : ''; ?>">
                            <i class="fas fa-layer-group"></i> QMS Templates
                        </a></li>
                        <li><a href="document_workflow.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'document_workflow.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-sitemap"></i> Approval Workflows
                        </a></li>
                        <li><a href="version_control.php" class="locked">
                            <i class="fas fa-code-branch"></i> Version Management
                        </a></li>
                        <li><a href="document_search.php" class="locked">
                            <i class="fas fa-search"></i> Search & Retrieval
                        </a></li>
                    </ul>
                </div>
            </li>
            
            <div class="menu-section">Quality Processes</div>
            
            <li class="accordion-item">
                <button class="accordion-header <?php echo in_array(basename($_SERVER['PHP_SELF']), ['audits.php', 'audit_checklists.php', 'audit_findings.php']) ? 'active' : ''; ?> locked">
                    <span>
                        <i class="fas fa-clipboard-check"></i> 
                        <span class="menu-text">Quality Audits</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content <?php echo in_array(basename($_SERVER['PHP_SELF']), ['audits.php', 'audit_checklists.php', 'audit_findings.php']) ? 'open' : ''; ?>">
                    <ul>
                        <li><a href="audits.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'audits.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-calendar-alt"></i> Audit Planning
                        </a></li>
                        <li><a href="audit_checklists.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'audit_checklists.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-tasks"></i> Audit Checklists
                        </a></li>
                        <li><a href="audit_findings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'audit_findings.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-exclamation-triangle"></i> Audit Findings
                        </a></li>
                        <li><a href="audit_reports.php" class="locked">
                            <i class="fas fa-chart-bar"></i> Audit Reports
                        </a></li>
                    </ul>
                </div>
            </li>
            
            <li class="accordion-item">
                <button class="accordion-header <?php echo in_array(basename($_SERVER['PHP_SELF']), ['capa.php', 'capa_tracking.php']) ? 'active' : ''; ?> locked">
                    <span>
                        <i class="fas fa-tools"></i> 
                        <span class="menu-text">CAPA Managements</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content <?php echo in_array(basename($_SERVER['PHP_SELF']), ['capa.php', 'capa_tracking.php']) ? 'open' : ''; ?>">
                    <ul>
                        <li><a href="capa.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'capa.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-plus-circle"></i> New CAPA
                        </a></li>
                        <li><a href="capa_tracking.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'capa_tracking.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-list-ol"></i> CAPA Tracking
                        </a></li>
                        <li><a href="capa_reports.php" class="locked">
                            <i class="fas fa-chart-pie"></i> CAPA Analytics
                        </a></li>
                    </ul>
                </div>
            </li>
            
            <li class="accordion-item">
                <button class="accordion-header <?php echo in_array(basename($_SERVER['PHP_SELF']), ['risks.php', 'risk_assessment.php']) ? 'active' : ''; ?> locked">
                    <span>
                        <i class="fas fa-exclamation-triangle"></i> 
                        <span class="menu-text">Risk Management</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content <?php echo in_array(basename($_SERVER['PHP_SELF']), ['risks.php', 'risk_assessment.php']) ? 'open' : ''; ?>">
                    <ul>
                        <li><a href="risks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'risks.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-clipboard-list"></i> Risk Register
                        </a></li>
                        <li><a href="risk_assessment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'risk_assessment.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-chart-line"></i> Risk Assessment
                        </a></li>
                        <li><a href="risk_mitigation.php" class="locked">
                            <i class="fas fa-shield-alt"></i> Mitigation Actions
                        </a></li>
                    </ul>
                </div>
            </li>
            
            <div class="menu-section">Management & Compliance</div>
            
            <li class="accordion-item">
                <button class="accordion-header <?php echo in_array(basename($_SERVER['PHP_SELF']), ['management_reviews.php', 'review_actions.php']) ? 'active' : ''; ?> locked">
                    <span>
                        <i class="fas fa-users"></i> 
                        <span class="menu-text">Management Reviews</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content <?php echo in_array(basename($_SERVER['PHP_SELF']), ['management_reviews.php', 'review_actions.php']) ? 'open' : ''; ?>">
                    <ul>
                        <li><a href="management_reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'management_reviews.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-calendar"></i> Review Meetings
                        </a></li>
                        <li><a href="review_actions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'review_actions.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-tasks"></i> Action Items
                        </a></li>
                        <li><a href="review_decisions.php" class="locked">
                            <i class="fas fa-gavel"></i> Decisions Tracking
                        </a></li>
                    </ul>
                </div>
            </li>
            
            <li><a href="compliance_tracking.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'compliance_tracking.php' ? 'active' : ''; ?> locked">
                <i class="fas fa-clipboard-list"></i> <span class="menu-text">Compliance Tracking</span>
                <span class="menu-badge">ISO 9001:2015</span>
            </a></li>
            
            <li><a href="training_records.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'training_records.php' ? 'active' : ''; ?> locked">
                <i class="fas fa-graduation-cap"></i> <span class="menu-text">Training Records</span>
            </a></li>
            
            <div class="menu-section">System & Administration</div>
            
            <li class="accordion-item">
                <button class="accordion-header <?php echo in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'roles.php', 'access_definition.php']) ? 'active' : ''; ?> locked">
                    <span>
                        <i class="fas fa-user-cog"></i> 
                        <span class="menu-text">User Management</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content <?php echo in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'roles.php', 'access_definition.php']) ? 'open' : ''; ?>">
                    <ul>
                        <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-users"></i> All Users
                        </a></li>
                        <li><a href="roles.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'roles.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-user-shield"></i> Role Permissions
                        </a></li>
                        <li><a href="access_definition.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'access_definition.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-key"></i> Access Definitions
                        </a></li>
                    </ul>
                </div>
            </li>
            
            <li class="accordion-item">
                <button class="accordion-header <?php echo in_array(basename($_SERVER['PHP_SELF']), ['workflows.php', 'notifications.php']) ? 'active' : ''; ?> locked">
                    <span>
                        <i class="fas fa-cogs"></i> 
                        <span class="menu-text">System Configuration</span>
                    </span>
                    <i class="fas fa-chevron-right accordion-indicator"></i>
                </button>
                <div class="accordion-content <?php echo in_array(basename($_SERVER['PHP_SELF']), ['workflows.php', 'notifications.php']) ? 'open' : ''; ?>">
                    <ul>
                        <li><a href="workflows.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'workflows.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-sitemap"></i> Workflow Automation
                        </a></li>
                        <li><a href="notifications.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?> locked">
                            <i class="fas fa-bell"></i> Notifications & Alerts
                        </a></li>
                        <li><a href="system_settings.php" class="locked">
                            <i class="fas fa-sliders-h"></i> System Settings
                        </a></li>
                    </ul>
                </div>
            </li>
            
            <li><a href="reports_dashboards.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports_dashboards.php' ? 'active' : ''; ?> locked">
                <i class="fas fa-chart-bar"></i> <span class="menu-text">Reports & Dashboards</span>
            </a></li>
            
            <li><a href="audit_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'audit_logs.php' ? 'active' : ''; ?> locked">
                <i class="fas fa-history"></i> <span class="menu-text">Audit Logs</span>
            </a></li>
            
            <li><a href="data_backup.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'data_backup.php' ? 'active' : ''; ?> locked">
                <i class="fas fa-database"></i> <span class="menu-text">Data Security & Backup</span>
            </a></li>
            
            <li><a href="help_support.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'help_support.php' ? 'active' : ''; ?> locked">
                <i class="fas fa-question-circle"></i> <span class="menu-text">Help & Support</span>
            </a></li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-avatar"><?php echo strtoupper(substr($user_email, 0, 2)); ?></div>
            <div class="user-info">
                <h4><?php echo explode('@', $user_email)[0]; ?></h4>
                <p>QMS User</p>
            </div>
        </div>
    </div>

    <script>
        // Sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Toggle sidebar collapse/expand
            if (toggleSidebar) {
                toggleSidebar.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-chevron-left');
                    icon.classList.toggle('fa-chevron-right');
                });
            }

            // Accordion functionality - prevent locked accordions from opening
            const accordionHeaders = document.querySelectorAll('.accordion-header');
            accordionHeaders.forEach(header => {
                header.addEventListener('click', function(e) {
                    // Don't allow locked accordions to open
                    if (this.classList.contains('locked')) {
                        e.stopPropagation();
                        return;
                    }
                    
                    e.stopPropagation();
                    
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
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.add('open');
                    sidebarOverlay.classList.add('active');
                });
            }

            // Close sidebar when clicking overlay on mobile
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    this.classList.remove('active');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    if (!event.target.closest('.sidebar') && !event.target.closest('.mobile-menu-toggle')) {
                        sidebar.classList.remove('open');
                        if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                    }
                }
            });
            
            // Prevent navigation for locked links
            const lockedLinks = document.querySelectorAll('.locked');
            lockedLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('This feature is not yet available. Please check back later.');
                });
            });
        });
    </script>
</body>
</html>