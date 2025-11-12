<?php
session_start();
require_once 'includes/config.php'; // database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Optional: Fetch user info from DB
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT user_id, user_email, user_access, user_status 
                       FROM tbl_hm_users WHERE user_id = :user_id LIMIT 1");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['user_status'] != 1) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Get access level for later
$user_access = $user['user_access'];
$user_email = $user['user_email'];

// Set page variables for header
$pageTitle = "Dashboard";
$pageIcon = "fas fa-tachometer-alt";

// ===============================
// COUNT VARIABLES
// ===============================

// 1. Count applications with current_stage_id = 1
$stmt = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications WHERE application_current_stage = 1");
$in_pending_count = $stmt->fetchColumn();

// 2. Count applications with current_stage_id = 10
$stmt = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications WHERE application_current_stage = 10");
$registered_count = $stmt->fetchColumn();

// 3. Count applications where current_stage_id != 1 AND current_stage_id != 10
$stmt = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications WHERE application_current_stage NOT IN (1, 10,30,14,16,23,28)");
$in_review_count = $stmt->fetchColumn();
/////////////////////Food MA/////////////////////////////////////////
// 1. Count applications with current_stage_id = 1
$stmt_fric = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_food WHERE application_current_stage = 1");
$in_pending_count_fric = $stmt_fric->fetchColumn();

// 2. Count applications with current_stage_id = 10
$stmt_fric = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_food WHERE application_current_stage = 10");
$registered_count_fric = $stmt_fric->fetchColumn();

// 3. Count applications where current_stage_id != 1 AND current_stage_id != 10
$stmt_fric = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_food WHERE application_current_stage NOT IN (1, 10,30,14,16,23,28)");
$in_review_count_fric = $stmt_fric->fetchColumn();
/////////////////////End Food MA/////////////////////////////////////////
/////////////////////Veterinary MA/////////////////////////////////////////
// 1. Count applications with current_stage_id = 1
$stmt_vmdr = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_veterinary WHERE application_current_stage = 1");
$in_pending_count_vmdr = $stmt_vmdr->fetchColumn();

// 2. Count applications with current_stage_id = 10
$stmt_vmdr = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_veterinary WHERE application_current_stage = 10");
$registered_count_vmdr = $stmt_vmdr->fetchColumn();

// 3. Count applications where current_stage_id != 1 AND current_stage_id != 10
$stmt_vmdr = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_veterinary WHERE application_current_stage NOT IN (1,10,30,14,16,23,28)");
$in_review_count_vmdr = $stmt_vmdr->fetchColumn();
/////////////////////End Veterinary MA/////////////////////////////////////////
/////////////////////Cosmetics MA/////////////////////////////////////////
// 1. Count applications with current_stage_id = 1
$stmt_chcr = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_cosmetics WHERE application_current_stage = 1");
$in_pending_count_chcr = $stmt_chcr->fetchColumn();

// 2. Count applications with current_stage_id = 10
$stmt_chcr = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_cosmetics WHERE application_current_stage = 10");
$registered_count_chcr = $stmt_chcr->fetchColumn();

// 3. Count applications where current_stage_id != 1 AND current_stage_id != 10
$stmt_chcr = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_cosmetics WHERE application_current_stage NOT IN (1,10,30,14,16,23,28)");
$in_review_count_chcr = $stmt_chcr->fetchColumn();
/////////////////////End Veterinary MA/////////////////////////////////////////
/////////////////////Food Premise/////////////////////////////////////////
// 1. Count applications with current_stage_id = 1
$stmt_fsmil = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_premise_food WHERE application_current_stage = 1");
$in_pending_count_fsmil = $stmt_fsmil->fetchColumn();

// 2. Count applications with current_stage_id = 10
$stmt_fsmil = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_premise_food WHERE application_current_stage = 10");
$registered_count_fsmil = $stmt_fsmil->fetchColumn();

// 3. Count applications where current_stage_id != 1 AND current_stage_id != 10
$stmt_fsmil = $pdo->query("SELECT COUNT(*) FROM tbl_hm_applications_premise_food WHERE application_current_stage NOT IN (1, 10,30,14,16,23,28)");
$in_review_count_fsmil = $stmt_fsmil->fetchColumn();
/////////////////////End Food Premise/////////////////////////////////////////
// Include header
include 'templates/header2.php';
?>


<!-- Main Dashboard Content -->
<main class="main-content">
    <div class="container-fluid">
        
        <!-- Welcome Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-banner dashboard-card card border-0">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="card-title text-primary mb-2">Welcome to Rwanda FDA Monitoring Tool</h2>
                                <p class="card-text text-muted mb-0">
                                    Access and manage monitoring applications for human medicines, food products, and safety inspections.
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="bg-primary bg-opacity-10 p-4 rounded-3 d-inline-block">
                                    <i class="fas fa-shield-alt fa-3x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modules Section -->
        <div class="row">
            <div class="col-12">
                <h4 class="mb-4">Monitoring Modules</h4>
            </div>
        </div>

        <div class="row">
            
            <!-- Human Medicines MA Module -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="module-card dashboard-card card h-100">
                    <div class="card-header bg-primary text-white d-flex align-items-center">
                        <i class="fas fa-capsule me-3 fa-lg"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Human Medicines MA</h6>
                            <small>Marketing Authorization</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted mb-3">
                            Monitor and manage human medicines product applications and authorizations.
                        </p>
                        <div class="module-stats mb-3">
                            <div class="row text-center">
                                <div class="col-4 border-end">
                                    <div class="text-primary fw-bold h5 mb-1"><?php echo $in_pending_count; ?></div>
                                    <small class="text-muted">Incoming</small>
                                </div>
                                <div class="col-4 border-end">
                                    <div class="text-success fw-bold h5 mb-1"> <?php echo $registered_count; ?>  </div>
                                    <small class="text-muted">Registered</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-warning fw-bold h5 mb-1"> <?php echo $in_review_count; ?> </div>
                                    <small class="text-muted">In Review</small>
                                </div>
                            </div>
                        </div>
                        <a href="hmdr/hmdr_dashboard.php" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right me-2"></i> Access Module
                        </a>
                    </div>
                </div>
            </div>


<!-- Food MA Module (Locked) -->
<div class="col-xl-4 col-md-6 mb-4">
    <div class="module-card dashboard-card card h-100">
        <!-- onclick="showComingSoon('Food Marketing Authorization')"-->
        <div class="card-header bg-secondary text-white d-flex align-items-center">
            <i class="fas fa-utensils me-3 fa-lg"></i>
            <div>
                <h6 class="mb-0 fw-bold">Food MA</h6>
                <small>Marketing Authorization</small>
            </div>
        </div>
        <div class="card-body">
            <p class="card-text text-muted mb-3">
                Monitor and manage food product applications and marketing authorizations.
            </p>
                                    <div class="module-stats mb-3">
                            <div class="row text-center">
                                <div class="col-4 border-end">
                                    <div class="text-primary fw-bold h5 mb-1"><?php echo $in_pending_count_fric; ?></div>
                                    <small class="text-muted">Incoming</small>
                                </div>
                                <div class="col-4 border-end">
                                    <div class="text-success fw-bold h5 mb-1"> <?php echo $registered_count_fric; ?>  </div>
                                    <small class="text-muted">Registered</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-warning fw-bold h5 mb-1"> <?php echo $in_review_count_fric; ?> </div>
                                    <small class="text-muted">In Review</small>
                                </div>
                            </div>
                        </div>
            <!--button class="btn btn-secondary w-100" disabled>
                <i class="fas fa-lock me-2"></i> Coming Soon
            </button-->
             <a href="fric/fric_dashboard.php" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-right me-2"></i> Access Module
                        </a>
        </div>
    </div>
</div>
<!-- Veterinary MA Module (Locked) -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="module-card dashboard-card card h-100">
                    <div class="card-header bg-warning text-dark d-flex align-items-center">
                        <i class="fas fa-capsule me-3 fa-lg"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Veterinary Medicines MA</h6>
                            <small>Marketing Authorization</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted mb-3">
                            Monitor and manage veterinary medicines product applications and authorizations.
                        </p>
                        <div class="module-stats mb-3">
                            <div class="row text-center">
                                <div class="col-4 border-end">
                                    <div class="text-primary fw-bold h5 mb-1"><?php echo $in_pending_count_vmdr; ?></div>
                                    <small class="text-muted">Incoming</small>
                                </div>
                                <div class="col-4 border-end">
                                    <div class="text-success fw-bold h5 mb-1"> <?php echo $registered_count_vmdr; ?>  </div>
                                    <small class="text-muted">Registered</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-warning fw-bold h5 mb-1"> <?php echo $in_review_count_vmdr; ?> </div>
                                    <small class="text-muted">In Review</small>
                                </div>
                            </div>
                        </div>
                        <a href="vmdr/vmdr_dashboard.php" class="btn btn-warning w-100">
                            <i class="fas fa-arrow-right me-2"></i> Access Module
                        </a>
                    </div>
                </div>
            </div>
<!-- Cosmetics MA Module  -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="module-card dashboard-card card h-100">
                    <div class="card-header bg-info text-white d-flex align-items-center">
                        <i class="fas fa-capsule me-3 fa-lg"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Cosmetics and Household Chemicals MA</h6>
                            <small>Marketing Authorization</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted mb-3">
                            Monitor and manage cosmetics and household chemicals product applications and authorizations.
                        </p>
                        <div class="module-stats mb-3">
                            <div class="row text-center">
                                <div class="col-4 border-end">
                                    <div class="text-primary fw-bold h5 mb-1"><?php echo $in_pending_count_chcr; ?></div>
                                    <small class="text-muted">Incoming</small>
                                </div>
                                <div class="col-4 border-end">
                                    <div class="text-success fw-bold h5 mb-1"> <?php echo $registered_count_chcr; ?>  </div>
                                    <small class="text-muted">Registered</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-warning fw-bold h5 mb-1"> <?php echo $in_review_count_chcr; ?> </div>
                                    <small class="text-muted">In Review</small>
                                </div>
                            </div>
                        </div>
                        <a href="chcr/chcr_dashboard.php" class="btn btn-info w-100">
                            <i class="fas fa-arrow-right me-2"></i> Access Module
                        </a>
                    </div>
                </div>
            </div>
            <!-- Food Safety Module (Locked) -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="module-card dashboard-card card h-100 locked"
                     onclick="showComingSoon('Food Safety Monitoring, Inspections & Licensing')">
                    <div class="card-header bg-secondary text-white d-flex align-items-center">
                        <i class="fas fa-clipboard-check me-3 fa-lg"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Food Safety</h6>
                            <small>Monitoring & Inspections</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted mb-3">
                            Monitor applications for food safety inspections and licensing.
                        </p>
                                                <div class="module-stats mb-3">
                            <div class="row text-center">
                                <div class="col-4 border-end">
                                    <div class="text-primary fw-bold h5 mb-1"><?php echo $in_pending_count_fsmil; ?></div>
                                    <small class="text-muted">Incoming</small>
                                </div>
                                <div class="col-4 border-end">
                                    <div class="text-success fw-bold h5 mb-1"> <?php echo $registered_count_fsmil; ?>  </div>
                                    <small class="text-muted">Registered</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-warning fw-bold h5 mb-1"> <?php echo $in_review_count_fsmil; ?> </div>
                                    <small class="text-muted">In Review</small>
                                </div>
                            </div>
                        </div>
                        <!--button class="btn btn-secondary w-100" disabled>
                            <i class="fas fa-lock me-2"></i> Coming Soon
                        </button-->
                        <a href="fsmil/fsmil_dashboard.php" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-right me-2"></i> Access Module
                        </a>
                    </div>
                </div>
            </div>
             <!-- Pharmaceutical Inspections and Licensing Module (Locked) -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="module-card dashboard-card card h-100 locked"
                     onclick="showComingSoon('Pharmaceutical Inspections and Licensing')">
                    <div class="card-header bg-primary text-white d-flex align-items-center">
                        <i class="fas fa-prescription-bottle me-3 fa-lg"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Pharmaceutical Inspections</h6>
                            <small>Inspections & Compliance</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted mb-3">
                            Monitor pharmaceutical inspections and licensing compliance.
                        </p>
                        <button class="btn btn-primary w-100" disabled>
                            <i class="fas fa-lock me-2"></i> Coming Soon
                        </button>
                    </div>
                </div>
            </div>

            <!-- Other Locked Modules -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="module-card dashboard-card card h-100 locked"
                     onclick="showComingSoon('Medical Devices Monitoring')">
                    <div class="card-header bg-warning text-dark d-flex align-items-center">
                        <i class="fas fa-stethoscope me-3 fa-lg"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Clinical Trial</h6>
                            <small>Monitoring & Compliance</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted mb-3">
                            Monitor medical devices applications and compliance tracking.
                        </p>
                        <button class="btn btn-warning w-100 text-dark" disabled>
                            <i class="fas fa-lock me-2"></i> Coming Soon
                        </button>
                    </div>
                </div>
            </div>

           

                   <!-- QMS Module (Active) -->
<div class="col-xl-4 col-md-6 mb-4">
  <div class="module-card dashboard-card card h-100">
    <div class="card-header bg-dark text-white d-flex align-items-center">
      <i class="fas fa-flask me-3 fa-lg"></i>
      <div>
        <h6 class="mb-0 fw-bold">QMS Module</h6>
        <small>Quality Management Services</small>
      </div>
    </div>
    <div class="card-body">
      <p class="card-text text-muted mb-3">
        Manage Institutional Quality Management Services and Document Control.
      </p>
      <a href="QMS/" class="btn btn-dark w-100">
        <i class="fas fa-arrow-right me-2"></i> Access Module
      </a>
    </div>
  </div>
</div>

    </div>
</main>

<script>
function showComingSoon(moduleName) {
    Swal.fire({
        title: 'Coming Soon!',
        text: moduleName + ' module is currently under development and will be available soon!',
        icon: 'info',
        confirmButtonText: 'OK',
        confirmButtonColor: '#008751'
    });
}
</script>

<?php
// Include footer
include 'templates/footer.php';
?>