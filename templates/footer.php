        </div> <!-- Close container from header -->
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-4 mb-md-0">
                        <h5 class="mb-3">Rwanda FDA</h5>
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i> 
                            Kigali, Rwanda
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-phone me-2"></i> 
                            9707
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-envelope me-2"></i> 
                            sdt@rwandafda.gov.rw
                        </p>
                    </div>
                    <div class="col-md-4 mb-4 mb-md-0">
                        <h5 class="mb-3">Quick Links</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="#" class="text-decoration-none text-dark">
                                    <i class="fas fa-arrow-right me-1"></i> Dashboard
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="#" class="text-decoration-none text-dark">
                                    <i class="fas fa-arrow-right me-1"></i> Monitoring
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="#" class="text-decoration-none text-dark">
                                    <i class="fas fa-arrow-right me-1"></i> Reports
                                </a>
                            </li>
                            <li class="mb-0">
                                <a href="#" class="text-decoration-none text-dark">
                                    <i class="fas fa-arrow-right me-1"></i> Settings
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5 class="mb-3">System Info</h5>
                        <div class="system-info">
                            <p class="mb-2">
                                <i class="fas fa-shield-alt me-2 text-primary"></i> 
                                Secure Monitoring System
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-database me-2 text-primary"></i> 
                                Version 2.1.0
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-clock me-2 text-primary"></i> 
                                Last updated: <?php echo date('M j, Y'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        &copy; <?php echo date('Y'); ?> Rwanda Food and Drugs Authority. All rights reserved.
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="me-3">
                            <i class="fas fa-lock me-1"></i> Secure Connection
                        </span>
                        <span>
                            <i class="fas fa-user-shield me-1"></i> Staff Only
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Active nav link highlighting
        document.addEventListener('DOMContentLoaded', function() {
            var currentPage = window.location.pathname.split('/').pop();
            var navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(function(link) {
                var linkHref = link.getAttribute('href');
                if (linkHref === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>