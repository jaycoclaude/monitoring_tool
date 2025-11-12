<?php
session_start();
require_once '../includes/config.php'; // your PDO connection file

// --- Check if app_id is provided ---
if (!isset($_GET['app_id'])) {
    die("Application ID is missing.");
}

$app_id = intval($_GET['app_id']);
$stage_id = $_GET['stage_id'] ?? '';
$user_id = $_SESSION['user_id'];

$access = $_SESSION['user_access'];

if ($access <> 100) {
    die('You do not have permission to access this page. <br><a href="javascript:history.back()">Click here to go back</a>');
}

// --- Fetch application data using standardized query with all fields ---
$sql = "SELECT 
            reference_no,
            tracking_no,
            applicant_name,
            application_date,
            application_current_stage,
            premise_category,
            product_category,
            product_type,
            applicant_TIN_no,
            applicant_telephone,
            applicant_email,
            country,
            province,
            district,
            sector,
            cell,
            village,
            gps_coordinates,
            managing_director,
            responsible_technician,
            responsible_technician_telephone,
            date_submitted,
            date_assessment1,
            date_assessment2,
            date_assessment3,
            date_inspection1,
            date_inspection2,
            date_inspection3,
            date_query_assessment1,
            date_query_assessment2,
            date_query_assessment3,
            date_response1,
            date_response2,
            date_response3,
            license_issue_date,
            license_expiry_date
        FROM tbl_hm_applications_premise_food
        WHERE application_id = :app_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['app_id' => $app_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("No record found.");
}

// --- Fetch all stages ---
$stageQuery = "SELECT status_id, status_description FROM tbl_hm_applications_status_food_ins ORDER BY status_id ASC";
$stageStmt = $pdo->prepare($stageQuery);
$stageStmt->execute();
$stages = $stageStmt->fetchAll(PDO::FETCH_ASSOC);

// Get current stage ID from application
$current_stage_id = $row['application_current_stage'] ?? null;

// --- Check if application exists in rdb_osc_applications ---
$checkRdbQuery = "SELECT * FROM rdb_osc_applications 
                  WHERE application_reference = :app_id 
                  AND application_type = 'premise_food'";
$checkRdbStmt = $pdo->prepare($checkRdbQuery);
$checkRdbStmt->execute(['app_id' => $app_id]);
$rdbApplication = $checkRdbStmt->fetch(PDO::FETCH_ASSOC);

$application_exists_in_rdb = !empty($rdbApplication);
$current_rdb_status = $rdbApplication['current_status'] ?? '';

// --- Function to determine RDB status based on stage ---
function getRdbStatus($stage_id) {
    $stage_id = intval($stage_id);
    
    if ($stage_id == 1) {
        return 'pending';
    } elseif (in_array($stage_id, [2, 3, 4, 6, 7, 8])) {
        return 'under-review';
    } elseif (in_array($stage_id, [5, 11])) {
        return 'awaiting-applicant-response';
    } elseif (in_array($stage_id, [10, 14, 16, 17, 23, 24, 29, 30, 40])) {
        return 'completed';
    } else {
        return 'pending'; // default fallback
    }
}

// --- Function to get badge color for RDB status ---
function getRdbStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return 'bg-secondary';
        case 'under-review':
            return 'bg-warning text-dark';
        case 'awaiting-applicant-response':
            return 'bg-info text-dark';
        case 'completed':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}

// --- Handle form submission (when application exists in RDB) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_update'])) {
    function formatDate($date) {
        return !empty($date) ? date('Y-m-d', strtotime($date)) : null;
    }

    $new_stage_id = $_POST['application_current_stage'];
    $rdb_status = getRdbStatus($new_stage_id);

    // Update main application table
    $data = [
        'reference_no' => $_POST['reference_no'],
        'tracking_no' => $_POST['tracking_no'],
        'applicant_name' => $_POST['applicant_name'],
        'application_date' => formatDate($_POST['application_date']),
        'application_current_stage' => $new_stage_id,
        'premise_category' => $_POST['premise_category'],
        'product_category' => $_POST['product_category'],
        'product_type' => $_POST['product_type'],
        'applicant_TIN_no' => $_POST['applicant_TIN_no'],
        'applicant_telephone' => $_POST['applicant_telephone'],
        'applicant_email' => $_POST['applicant_email'],
        'country' => $_POST['country'],
        'province' => $_POST['province'],
        'district' => $_POST['district'],
        'sector' => $_POST['sector'],
        'cell' => $_POST['cell'],
        'village' => $_POST['village'],
        'gps_coordinates' => $_POST['gps_coordinates'],
        'managing_director' => $_POST['managing_director'],
        'responsible_technician' => $_POST['responsible_technician'],
        'responsible_technician_telephone' => $_POST['responsible_technician_telephone'],
        'date_submitted' => formatDate($_POST['date_submitted']),
        'application_id' => $app_id,
        'updated_by' => $user_id
    ];

    $updateQuery = "UPDATE tbl_hm_applications_premise_food SET
        reference_no = :reference_no,
        tracking_no = :tracking_no,
        applicant_name = :applicant_name,
        application_date = :application_date,
        application_current_stage = :application_current_stage,
        premise_category = :premise_category,
        product_category = :product_category,
        product_type = :product_type,
        applicant_TIN_no = :applicant_TIN_no,
        applicant_telephone = :applicant_telephone,
        applicant_email = :applicant_email,
        country = :country,
        province = :province,
        district :district,
        sector = :sector,
        cell = :cell,
        village = :village,
        gps_coordinates = :gps_coordinates,
        managing_director = :managing_director,
        responsible_technician = :responsible_technician,
        responsible_technician_telephone = :responsible_technician_telephone,
        date_submitted = :date_submitted,
        updated_by = :updated_by
      WHERE application_id = :application_id";

    $stmt = $pdo->prepare($updateQuery);
    $updated = $stmt->execute($data);

    if ($updated) {
        // Update existing rdb_osc_applications record
        $updateRdbQuery = "UPDATE rdb_osc_applications 
                          SET current_status = :current_status, 
                              applicant_name = :applicant_name,
                              tracking_no = :tracking_no,
                              application_date = :application_date,
                              updated_at = NOW() 
                          WHERE application_reference = :application_id 
                          AND application_type = 'premise_food'";
        
        $rdbStmt = $pdo->prepare($updateRdbQuery);
        $rdbUpdated = $rdbStmt->execute([
            'current_status' => $rdb_status,
            'applicant_name' => $_POST['applicant_name'],
            'tracking_no' => $_POST['tracking_no'],
            'application_date' => formatDate($_POST['application_date']),
            'application_id' => $app_id
        ]);

        $message = "Application updated successfully! RDB status updated to: " . strtoupper($rdb_status);
        
        echo "<script>
            alert('$message');
            window.location.href='fsmil_page.php?stage_id=$stage_id';
        </script>";
        exit;
    } else {
        echo "<script>alert('Error updating record.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Application Information</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #3498db;
      --accent-color: #e74c3c;
      --light-bg: #f8f9fa;
      --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    body {
      background-color: var(--light-bg);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .card {
      border: none;
      border-radius: 10px;
      box-shadow: var(--card-shadow);
      transition: transform 0.3s ease;
    }
    .card:hover {
      transform: translateY(-5px);
    }
    .section-header {
      background-color: var(--primary-color);
      color: white;
      padding: 12px 15px;
      border-radius: 8px 8px 0 0;
      margin-bottom: 20px;
    }
    .form-label {
      font-weight: 600;
      color: var(--primary-color);
      margin-bottom: 8px;
    }
    .form-control, .form-select {
      border-radius: 6px;
      padding: 10px 15px;
      border: 1px solid #ced4da;
      transition: all 0.3s;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--secondary-color);
      box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
    .btn-primary {
      background-color: var(--secondary-color);
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
    }
    .btn-secondary {
      background-color: #6c757d;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
    }
    .btn-success {
      background-color: #28a745;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
    }
    .btn-warning {
      background-color: #ffc107;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
      color: #000;
    }
    .required::after {
      content: " *";
      color: var(--accent-color);
    }
    .form-section {
      margin-bottom: 30px;
    }
    .status-badge {
      font-size: 0.9em;
      padding: 5px 10px;
    }
    .rdb-info-alert {
      background-color: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
    .rdb-exists-alert {
      background-color: #d1ecf1;
      border: 1px solid #bee5eb;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
    .modal-content {
      border-radius: 10px;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .modal-header {
      background-color: var(--primary-color);
      color: white;
      border-radius: 10px 10px 0 0;
    }
    .status-info-box {
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
<div class="container mt-4 mb-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-primary"><i class="fas fa-edit me-2"></i>Update Application Information</h2>
    <a href="fsmil_page.php?stage_id=<?php echo $stage_id; ?>" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-2"></i>Back to List
    </a>
  </div>

  <?php if ($application_exists_in_rdb): ?>
  <div class="rdb-exists-alert">
    <h5><i class="fas fa-check-circle text-success me-2"></i>Application Found in RDB OSC System</h5>
    <p class="mb-0">This application is registered in the RDB OSC system. Current RDB Status: 
        <span class="badge <?php echo getRdbStatusBadge($current_rdb_status); ?> status-badge">
            <?php echo strtoupper($current_rdb_status); ?>
        </span>
    </p>
  </div>
  <?php else: ?>
  <div class="rdb-info-alert">
    <h5><i class="fas fa-exclamation-triangle text-warning me-2"></i>RDB OSC Application Not Found</h5>
    <p class="mb-0">This application is not yet registered in the RDB OSC system. When you update the stage, you'll be prompted to add it to RDB OSC applications.</p>
  </div>
  <?php endif; ?>

  <form method="POST" class="card p-4" id="applicationForm">
    <!-- Hidden field to identify direct update -->
    <input type="hidden" name="direct_update" value="1">
    
    <!-- Basic Information Section -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h4>
      </div>
      
      <!-- Status Information Box -->
      <div class="status-info-box">
        <div class="row">
          <div class="col-md-6">
            <h6><i class="fas fa-info-circle me-2 text-primary"></i>Current Status Information</h6>
            <div class="mb-2">
              <strong>Application Stage:</strong>
              <span class="badge bg-info text-dark status-badge"><?php echo htmlspecialchars($current_stage_id); ?></span>
            </div>
            <div class="mb-2">
              <strong>RDB Status:</strong>
              <?php if ($application_exists_in_rdb): ?>
                <span class="badge <?php echo getRdbStatusBadge($current_rdb_status); ?> status-badge">
                  <?php echo strtoupper($current_rdb_status); ?>
                </span>
                <small class="text-muted ms-2">(Current in RDB system)</small>
              <?php else: ?>
                <span class="badge bg-secondary status-badge">NOT IN RDB</span>
              <?php endif; ?>
            </div>
            <div class="mb-0">
              <strong>New RDB Status:</strong>
              <span class="badge bg-warning text-dark status-badge" id="newRdbStatusDisplay">
                <?php echo strtoupper(getRdbStatus($current_stage_id)); ?>
              </span>
              <small class="text-muted ms-2">(Will be set after update)</small>
            </div>
          </div>
          
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label required">Reference No</label>
          <input type="text" name="reference_no" class="form-control" value="<?php echo htmlspecialchars($row['reference_no'] ?? ''); ?>" required>
        </div>
        
        <div class="col-md-6 mb-3">
          <label class="form-label required">Tracking No</label>
          <input type="text" name="tracking_no" class="form-control" value="<?php echo htmlspecialchars($row['tracking_no'] ?? ''); ?>" required>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-12 mb-3">
          <label class="form-label required">Applicant Name</label>
          <input type="text" name="applicant_name" class="form-control" value="<?php echo htmlspecialchars($row['applicant_name'] ?? ''); ?>" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label required">Application Date</label>
          <input type="date" name="application_date" class="form-control" value="<?php echo htmlspecialchars($row['application_date'] ?? ''); ?>" required>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label required">Date Asignment</label>
          <input type="date" name="date_submitted" class="form-control" value="<?php echo htmlspecialchars($row['date_submitted'] ?? ''); ?>" required>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label required">Current Stage</label>
          <select name="application_current_stage" class="form-select" required id="stageSelect">
            <option value="">Select Stage</option>
            <?php foreach ($stages as $stage): ?>
              <option value="<?php echo htmlspecialchars($stage['status_id']); ?>" 
                <?php echo ($stage['status_id'] == $current_stage_id) ? 'selected' : ''; ?>
                data-rdb-status="<?php echo getRdbStatus($stage['status_id']); ?>">
                <?php echo htmlspecialchars($stage['status_id'] . ' - ' . $stage['status_description']); ?>
                (RDB: <?php echo strtoupper(getRdbStatus($stage['status_id'])); ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <small class="form-text text-muted">Changing the stage will update both the application and RDB OSC status.</small>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
      <a href="fsmil_page.php?stage_id=<?php echo $stage_id; ?>" class="btn btn-secondary">Cancel</a>
      <button type="button" class="btn btn-success" id="saveButton">
        <i class="fas fa-save me-2"></i>Save Changes
      </button>
    </div>
  </form>
</div>

<!-- RDB Application Confirmation Modal -->
<div class="modal fade" id="rdbConfirmationModal" tabindex="-1" aria-labelledby="rdbModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="rdbModalLabel">
          <i class="fas fa-plus-circle me-2"></i>Add to RDB OSC Applications
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          This application is not yet registered in the RDB OSC system. Please review and confirm the information below to add it.
        </div>
        
        <h6 class="mb-3">Application Information to be added to RDB OSC:</h6>
        
        <div class="table-responsive">
          <table class="table table-bordered">
            <tbody>
              <tr>
                <th width="30%">Applicant Name:</th>
                <td id="modal_applicant_name"></td>
              </tr>
              <tr>
                <th>Tracking No:</th>
                <td id="modal_tracking_no"></td>
              </tr>
              <tr>
                <th>Application Date:</th>
                <td id="modal_application_date"></td>
              </tr>
              <tr>
                <th>Service Requested:</th>
                <td>Food Safety Premise License</td>
              </tr>
              <tr>
                <th>Application Reference:</th>
                <td><?php echo $app_id; ?></td>
              </tr>
              <tr>
                <th>Application Type:</th>
                <td>premise_food</td>
              </tr>
              <tr>
                <th>Current Status:</th>
                <td id="modal_current_status"></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mb-3">
          <label for="service_requested" class="form-label">Service Requested (Editable):</label>
          <input type="text" class="form-control" id="service_requested" name="service_requested" value="Food Safety Premise License">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="confirmAddToRdb">
          <i class="fas fa-check me-2"></i>Confirm & Add to RDB
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('applicationForm');
    const saveButton = document.getElementById('saveButton');
    const stageSelect = document.getElementById('stageSelect');
    const modal = new bootstrap.Modal(document.getElementById('rdbConfirmationModal'));
    const newRdbStatusDisplay = document.getElementById('newRdbStatusDisplay');
    
    const applicationExistsInRdb = <?php echo $application_exists_in_rdb ? 'true' : 'false'; ?>;
    const appId = <?php echo $app_id; ?>;
    const currentRdbStatus = '<?php echo $current_rdb_status; ?>';

    // Function to get RDB status based on stage
    function getRdbStatus(stageId) {
        stageId = parseInt(stageId);
        if (stageId === 1) return 'pending';
        if ([2, 3, 4, 6, 7, 8].includes(stageId)) return 'under-review';
        if ([5, 11].includes(stageId)) return 'awaiting-applicant-response';
        if ([10, 14, 16, 17, 23, 24, 29, 30, 40].includes(stageId)) return 'completed';
        return 'pending';
    }

    // Function to get badge class for RDB status
    function getRdbStatusBadgeClass(status) {
        switch (status) {
            case 'pending': return 'bg-secondary';
            case 'under-review': return 'bg-warning text-dark';
            case 'awaiting-applicant-response': return 'bg-info text-dark';
            case 'completed': return 'bg-success';
            default: return 'bg-secondary';
        }
    }

    // Update RDB status display when stage changes
    stageSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const rdbStatus = selectedOption.getAttribute('data-rdb-status') || getRdbStatus(this.value);
        
        if (this.value) {
            const badgeClass = getRdbStatusBadgeClass(rdbStatus);
            newRdbStatusDisplay.textContent = rdbStatus.toUpperCase();
            newRdbStatusDisplay.className = `badge ${badgeClass} status-badge`;
        }
    });

    saveButton.addEventListener('click', function() {
        // Validate form
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const newStageId = stageSelect.value;
        const rdbStatus = getRdbStatus(newStageId);

        if (!applicationExistsInRdb) {
            // Show confirmation modal for adding to RDB
            document.getElementById('modal_applicant_name').textContent = document.querySelector('[name="applicant_name"]').value;
            document.getElementById('modal_tracking_no').textContent = document.querySelector('[name="tracking_no"]').value;
            document.getElementById('modal_application_date').textContent = document.querySelector('[name="application_date"]').value;
            document.getElementById('modal_current_status').textContent = rdbStatus.toUpperCase();
            
            modal.show();
        } else {
            // Application exists in RDB, submit form directly to update both tables
            form.submit();
        }
    });

    // Handle confirmation to add to RDB
    document.getElementById('confirmAddToRdb').addEventListener('click', function() {
        // Create hidden inputs for RDB data
        const serviceRequestedInput = document.createElement('input');
        serviceRequestedInput.type = 'hidden';
        serviceRequestedInput.name = 'service_requested';
        serviceRequestedInput.value = document.getElementById('service_requested').value;
        form.appendChild(serviceRequestedInput);

        const rdbStatusInput = document.createElement('input');
        rdbStatusInput.type = 'hidden';
        rdbStatusInput.name = 'rdb_status';
        rdbStatusInput.value = getRdbStatus(stageSelect.value);
        form.appendChild(rdbStatusInput);

        const appRefInput = document.createElement('input');
        appRefInput.type = 'hidden';
        appRefInput.name = 'application_reference';
        appRefInput.value = appId;
        form.appendChild(appRefInput);

        // Remove the direct_update field since we're going to add_to_rdb.php
        const directUpdateInput = form.querySelector('input[name="direct_update"]');
        if (directUpdateInput) {
            directUpdateInput.remove();
        }

        // Change form action to add_to_rdb.php
        form.action = 'add_to_rdb.php';
        form.submit();
    });
});
</script>
</body>
</html>