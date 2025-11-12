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
        FROM tbl_ins_applications_premise_food
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

// Optional: get stage name for display
$current_stage_name = '';
if ($current_stage_id) {
    $stageNameStmt = $pdo->prepare("SELECT status_description FROM tbl_hm_applications_status_food_ins WHERE status_id = :id");
    $stageNameStmt->execute(['id' => $current_stage_id]);
    $current_stage_name = $stageNameStmt->fetchColumn() ?: '';
}

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function formatDate($date) {
        return !empty($date) ? date('Y-m-d', strtotime($date)) : null;
    }

    $data = [
        'reference_no' => $_POST['reference_no'],
        'tracking_no' => $_POST['tracking_no'],
        'applicant_name' => $_POST['applicant_name'],
        'application_date' => formatDate($_POST['application_date']),
        'application_current_stage' => $current_stage_id, // Keep the current stage unchanged
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
        'date_assessment1' => formatDate($_POST['date_assessment1']),
        'date_assessment2' => formatDate($_POST['date_assessment2']),
        'date_assessment3' => formatDate($_POST['date_assessment3']),
        'date_inspection1' => formatDate($_POST['date_inspection1']),
        'date_inspection2' => formatDate($_POST['date_inspection2']),
        'date_inspection3' => formatDate($_POST['date_inspection3']),
        'date_query_assessment1' => formatDate($_POST['date_query_assessment1']),
        'date_query_assessment2' => formatDate($_POST['date_query_assessment2']),
        'date_query_assessment3' => formatDate($_POST['date_query_assessment3']),
        'date_response1' => formatDate($_POST['date_response1']),
        'date_response2' => formatDate($_POST['date_response2']),
        'date_response3' => formatDate($_POST['date_response3']),
        'license_issue_date' => formatDate($_POST['license_issue_date']),
        'license_expiry_date' => formatDate($_POST['license_expiry_date']),
        'application_id' => $app_id,
        'updated_by' => $user_id
    ];

    $updateQuery = "UPDATE tbl_ins_applications_premise_food SET
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
        district = :district,
        sector = :sector,
        cell = :cell,
        village = :village,
        gps_coordinates = :gps_coordinates,
        managing_director = :managing_director,
        responsible_technician = :responsible_technician,
        responsible_technician_telephone = :responsible_technician_telephone,
        date_submitted = :date_submitted,
        date_assessment1 = :date_assessment1,
        date_assessment2 = :date_assessment2,
        date_assessment3 = :date_assessment3,
        date_inspection1 = :date_inspection1,
        date_inspection2 = :date_inspection2,
        date_inspection3 = :date_inspection3,
        date_query_assessment1 = :date_query_assessment1,
        date_query_assessment2 = :date_query_assessment2,
        date_query_assessment3 = :date_query_assessment3,
        date_response1 = :date_response1,
        date_response2 = :date_response2,
        date_response3 = :date_response3,
        license_issue_date = :license_issue_date,
        license_expiry_date = :license_expiry_date,
        updated_by = :updated_by
      WHERE application_id = :application_id";

    $stmt = $pdo->prepare($updateQuery);
    $updated = $stmt->execute($data);

    if ($updated) {
        echo "<script>
            alert('Application updated successfully!');
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
    .assessment-group {
      background-color: #f1f8ff;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
      border-left: 4px solid var(--secondary-color);
    }
    .assessment-title {
      color: var(--primary-color);
      font-weight: 600;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
    }
    .assessment-title i {
      margin-right: 10px;
      color: var(--secondary-color);
    }
    .required::after {
      content: " *";
      color: var(--accent-color);
    }
    .form-section {
      margin-bottom: 30px;
    }
    .readonly-field {
      background-color: #e9ecef;
      opacity: 1;
      cursor: not-allowed;
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

  <form method="POST" class="card p-4">
    <!-- Basic Information Section -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h4>
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
          <label class="form-label required">Date Submitted</label>
          <input type="date" name="date_submitted" class="form-control" value="<?php echo htmlspecialchars($row['date_submitted'] ?? ''); ?>" required>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label required">Current Stage</label>
          <?php if ($current_stage_name): ?>
            <div class="mb-2">
              <strong>Current Stage:</strong>
              <span class="badge bg-info text-dark fs-6"><?php echo htmlspecialchars($current_stage_name); ?></span>
            </div>
          <?php endif; ?>
          
          <!-- Hidden input to preserve the current stage value -->
          <input type="hidden" name="application_current_stage" value="<?php echo htmlspecialchars($current_stage_id ?? ''); ?>">
          
          <select class="form-select readonly-field" disabled readonly>
            <option value="">Select Stage</option>
            <?php foreach ($stages as $stage): ?>
              <option value="<?php echo htmlspecialchars($stage['status_id']); ?>" 
                <?php echo ($stage['status_id'] == $current_stage_id) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($stage['status_description']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="form-text text-muted">Current stage cannot be modified from this page.</small>
        </div>
      </div>
    </div>

    <!-- Product Information Section -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-box me-2"></i>Product Information</h4>
      </div>
      
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Premise Category</label>
          <input type="text" name="premise_category" class="form-control" value="<?php echo htmlspecialchars($row['premise_category'] ?? ''); ?>">
        </div>
        
        <div class="col-md-4 mb-3">
          <label class="form-label">Product Category</label>
          <input type="text" name="product_category" class="form-control" value="<?php echo htmlspecialchars($row['product_category'] ?? ''); ?>">
        </div>
        
        <div class="col-md-4 mb-3">
          <label class="form-label">Product Type</label>
          <input type="text" name="product_type" class="form-control" value="<?php echo htmlspecialchars($row['product_type'] ?? ''); ?>">
        </div>
      </div>
    </div>

    <!-- Applicant Contact Information -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-user me-2"></i>Applicant Contact Information</h4>
      </div>
      
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">TIN Number</label>
          <input type="text" name="applicant_TIN_no" class="form-control" value="<?php echo htmlspecialchars($row['applicant_TIN_no'] ?? ''); ?>">
        </div>
        
        <div class="col-md-4 mb-3">
          <label class="form-label">Telephone</label>
          <input type="text" name="applicant_telephone" class="form-control" value="<?php echo htmlspecialchars($row['applicant_telephone'] ?? ''); ?>">
        </div>
        
        <div class="col-md-4 mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="applicant_email" class="form-control" value="<?php echo htmlspecialchars($row['applicant_email'] ?? ''); ?>">
        </div>
      </div>
    </div>

    <!-- Location Information -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Location Information</h4>
      </div>
      
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">Country</label>
          <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($row['country'] ?? ''); ?>">
        </div>
        
        <div class="col-md-3 mb-3">
          <label class="form-label">Province</label>
          <input type="text" name="province" class="form-control" value="<?php echo htmlspecialchars($row['province'] ?? ''); ?>">
        </div>
        
        <div class="col-md-3 mb-3">
          <label class="form-label">District</label>
          <input type="text" name="district" class="form-control" value="<?php echo htmlspecialchars($row['district'] ?? ''); ?>">
        </div>
        
        <div class="col-md-3 mb-3">
          <label class="form-label">Sector</label>
          <input type="text" name="sector" class="form-control" value="<?php echo htmlspecialchars($row['sector'] ?? ''); ?>">
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Cell</label>
          <input type="text" name="cell" class="form-control" value="<?php echo htmlspecialchars($row['cell'] ?? ''); ?>">
        </div>
        
        <div class="col-md-4 mb-3">
          <label class="form-label">Village</label>
          <input type="text" name="village" class="form-control" value="<?php echo htmlspecialchars($row['village'] ?? ''); ?>">
        </div>
        
        <div class="col-md-4 mb-3">
          <label class="form-label">GPS Coordinates</label>
          <input type="text" name="gps_coordinates" class="form-control" value="<?php echo htmlspecialchars($row['gps_coordinates'] ?? ''); ?>">
        </div>
      </div>
    </div>

    <!-- Company Personnel -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-users me-2"></i>Company Personnel</h4>
      </div>
      
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Managing Director</label>
          <input type="text" name="managing_director" class="form-control" value="<?php echo htmlspecialchars($row['managing_director'] ?? ''); ?>">
        </div>
        
        <div class="col-md-4 mb-3">
          <label class="form-label">Responsible Technician</label>
          <input type="text" name="responsible_technician" class="form-control" value="<?php echo htmlspecialchars($row['responsible_technician'] ?? ''); ?>">
        </div>
        
        <div class="col-md-4 mb-3">
          <label class="form-label">Technician Telephone</label>
          <input type="text" name="responsible_technician_telephone" class="form-control" value="<?php echo htmlspecialchars($row['responsible_technician_telephone'] ?? ''); ?>">
        </div>
      </div>
    </div>

    <!-- Assessment Timeline -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Assessment Timeline</h4>
      </div>
      
      <!-- Assessment 1 -->
      <div class="assessment-group">
        <h5 class="assessment-title"><i class="fas fa-clipboard-check me-2"></i>First Assessment Cycle</h5>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Assessment 1</label>
            <input type="date" name="date_assessment1" class="form-control" value="<?php echo htmlspecialchars($row['date_assessment1'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Inspection 1</label>
            <input type="date" name="date_inspection1" class="form-control" value="<?php echo htmlspecialchars($row['date_inspection1'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Query Assessment 1</label>
            <input type="date" name="date_query_assessment1" class="form-control" value="<?php echo htmlspecialchars($row['date_query_assessment1'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Response 1</label>
            <input type="date" name="date_response1" class="form-control" value="<?php echo htmlspecialchars($row['date_response1'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- Assessment 2 -->
      <div class="assessment-group">
        <h5 class="assessment-title"><i class="fas fa-clipboard-check me-2"></i>Second Assessment Cycle</h5>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Assessment 2</label>
            <input type="date" name="date_assessment2" class="form-control" value="<?php echo htmlspecialchars($row['date_assessment2'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Inspection 2</label>
            <input type="date" name="date_inspection2" class="form-control" value="<?php echo htmlspecialchars($row['date_inspection2'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Query Assessment 2</label>
            <input type="date" name="date_query_assessment2" class="form-control" value="<?php echo htmlspecialchars($row['date_query_assessment2'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Response 2</label>
            <input type="date" name="date_response2" class="form-control" value="<?php echo htmlspecialchars($row['date_response2'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- Assessment 3 -->
      <div class="assessment-group">
        <h5 class="assessment-title"><i class="fas fa-clipboard-check me-2"></i>Third Assessment Cycle</h5>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Assessment 3</label>
            <input type="date" name="date_assessment3" class="form-control" value="<?php echo htmlspecialchars($row['date_assessment3'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Inspection 3</label>
            <input type="date" name="date_inspection3" class="form-control" value="<?php echo htmlspecialchars($row['date_inspection3'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Query Assessment 3</label>
            <input type="date" name="date_query_assessment3" class="form-control" value="<?php echo htmlspecialchars($row['date_query_assessment3'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Response 3</label>
            <input type="date" name="date_response3" class="form-control" value="<?php echo htmlspecialchars($row['date_response3'] ?? ''); ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- License Information -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-file-certificate me-2"></i>License Information</h4>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">License Issue Date</label>
          <input type="date" name="license_issue_date" class="form-control" value="<?php echo htmlspecialchars($row['license_issue_date'] ?? ''); ?>">
        </div>
        
        <div class="col-md-6 mb-3">
          <label class="form-label">License Expiry Date</label>
          <input type="date" name="license_expiry_date" class="form-control" value="<?php echo htmlspecialchars($row['license_expiry_date'] ?? ''); ?>">
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
      <a href="fsmil_page.php?stage_id=<?php echo $stage_id; ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Save Changes</button>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>