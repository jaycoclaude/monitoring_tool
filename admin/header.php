    <header class="header">
        <div class="header-content">
            <div class="branding">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="../landing_page.php" style="text-decoration:none;">
                <div class="logo">A</div>
                <div class="brand-text">
                    <h1>ADMINISTRATION</h1>
                    <p>Manage the Administration of the Monitoring tool</p>
                </div>
                </a>
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