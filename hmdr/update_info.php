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

// --- Fetch application data ---
$sql = "SELECT 
            reference_no,
            tracking_no,
            brand_name,
            product_strength,
            date_submitted,
            application_current_stage,
            date_screening,
            date_first_assessment1,
            date_second_assessment1,
            date_query_assessment1,
            date_response1,
            date_first_assessment2,
            date_second_assessment2,
            date_query_assessment2,
            date_response2,
            date_first_assessment3,
            date_second_assessment3,
            date_query_assessment3,
            date_response3,
            assessment_procedure
        FROM tbl_hm_applications
        WHERE hm_application_id = :app_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['app_id' => $app_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("No record found.");
}

// --- Fetch all stages ---
$stageQuery = "SELECT status_id, status_description FROM tbl_hm_applications_status ORDER BY status_id ASC";
$stageStmt = $pdo->prepare($stageQuery);
$stageStmt->execute();
$stages = $stageStmt->fetchAll(PDO::FETCH_ASSOC);

// Get current stage ID from application
$current_stage_id = $row['application_current_stage'] ?? null;

// Optional: get stage name for display
$current_stage_name = '';
if ($current_stage_id) {
    $stageNameStmt = $pdo->prepare("SELECT status_description FROM tbl_hm_applications_status WHERE status_id = :id");
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
    'brand_name' => $_POST['brand_name'],
    'product_strength' => $_POST['product_strength'],
    'date_submitted' => formatDate($_POST['date_submitted']),
    'application_current_stage' => $_POST['application_current_stage'],
    'date_screening' => formatDate($_POST['date_screening']),
    'date_first_assessment1' => formatDate($_POST['date_first_assessment1']),
    'date_second_assessment1' => formatDate($_POST['date_second_assessment1']),
    'date_query_assessment1' => formatDate($_POST['date_query_assessment1']),
    'date_response1' => formatDate($_POST['date_response1']),
    'date_first_assessment2' => formatDate($_POST['date_first_assessment2']),
    'date_second_assessment2' => formatDate($_POST['date_second_assessment2']),
    'date_query_assessment2' => formatDate($_POST['date_query_assessment2']),
    'date_response2' => formatDate($_POST['date_response2']),
    'date_first_assessment3' => formatDate($_POST['date_first_assessment3']),
    'date_second_assessment3' => formatDate($_POST['date_second_assessment3']),
    'date_query_assessment3' => formatDate($_POST['date_query_assessment3']),
    'date_response3' => formatDate($_POST['date_response3']),
    'assessment_procedure' => $_POST['assessment_procedure'],
    'hm_application_id' => $app_id,
    'updated_by' => $user_id
];

    $updateQuery = "UPDATE tbl_hm_applications SET
        reference_no = :reference_no,
        tracking_no = :tracking_no,
        brand_name = :brand_name,
        product_strength = :product_strength,
        date_submitted = :date_submitted,
        application_current_stage = :application_current_stage,
        date_screening = :date_screening,
        date_first_assessment1 = :date_first_assessment1,
        date_second_assessment1 = :date_second_assessment1,
        date_query_assessment1 = :date_query_assessment1,
        date_response1 = :date_response1,
        date_first_assessment2 = :date_first_assessment2,
        date_second_assessment2 = :date_second_assessment2,
        date_query_assessment2 = :date_query_assessment2,
        date_response2 = :date_response2,
        date_first_assessment3 = :date_first_assessment3,
        date_second_assessment3 = :date_second_assessment3,
        date_query_assessment3 = :date_query_assessment3,
        date_response3 = :date_response3,
        assessment_procedure = :assessment_procedure,
        updated_by = :updated_by
      WHERE hm_application_id = :hm_application_id";

    $stmt = $pdo->prepare($updateQuery);
    $updated = $stmt->execute($data);

    if ($updated) {
echo "<script>
            alert('Application updated successfully!');
            window.location.href='hmdr_page.php?stage_id=$stage_id';
          </script>";        exit;
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
  </style>
</head>
<body>
<div class="container mt-4 mb-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-primary"><i class="fas fa-edit me-2"></i>Update Application Information</h2>
<a href="hmdr_page.php?stage_id=<?php echo $stage_id; ?>" class="btn btn-secondary">
  <i class="fas fa-arrow-left me-2"></i>Back to List
</a>  </div>

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
        <div class="col-md-6 mb-3">
          <label class="form-label required">Brand Name</label>
          <input type="text" name="brand_name" class="form-control" value="<?php echo htmlspecialchars($row['brand_name'] ?? ''); ?>" required>
        </div>
        
        <div class="col-md-6 mb-3">
          <label class="form-label required">Product Strength</label>
          <input type="text" name="product_strength" class="form-control" value="<?php echo htmlspecialchars($row['product_strength'] ?? ''); ?>" required>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label required">Date Submitted</label>
          <input type="date" name="date_submitted" class="form-control" value="<?php echo htmlspecialchars($row['date_submitted'] ?? ''); ?>" required>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label required">Current Stage</label>
          <?php if ($current_stage_name): ?>
            <p><strong>Current Stage:</strong>
              <span class="badge bg-info text-dark"><?php echo htmlspecialchars($current_stage_name); ?></span>
            </p>
          <?php endif; ?>
          
          <select name="application_current_stage" class="form-select" required>
            <option value="">Select Stage</option>
            <?php foreach ($stages as $stage): ?>
              <option value="<?php echo htmlspecialchars($stage['status_id']); ?>" 
                <?php echo ($stage['status_id'] == $current_stage_id) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($stage['status_description']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    
    <!-- Screening Section -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-search me-2"></i>Screening</h4>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Date Screening</label>
          <input type="date" name="date_screening" class="form-control" value="<?php echo htmlspecialchars($row['date_screening'] ?? ''); ?>">
        </div>
      </div>
    </div>

    <!-- Assessment Cycles -->
    <?php
    $assessmentCycles = [
      1 => "First Assessment Cycle",
      2 => "Second Assessment Cycle",
      3 => "Third Assessment Cycle"
    ];
    foreach ($assessmentCycles as $cycle => $title): ?>
      <div class="form-section">
        <div class="assessment-group">
          <h5 class="assessment-title"><i class="fas fa-clipboard-check me-2"></i><?php echo $title; ?></h5>
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label">Date First Assessment <?php echo $cycle; ?></label>
              <input type="date" name="date_first_assessment<?php echo $cycle; ?>" class="form-control" value="<?php echo htmlspecialchars($row['date_first_assessment' . $cycle] ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Date Second Assessment <?php echo $cycle; ?></label>
              <input type="date" name="date_second_assessment<?php echo $cycle; ?>" class="form-control" value="<?php echo htmlspecialchars($row['date_second_assessment' . $cycle] ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Date Query Assessment <?php echo $cycle; ?></label>
              <input type="date" name="date_query_assessment<?php echo $cycle; ?>" class="form-control" value="<?php echo htmlspecialchars($row['date_query_assessment' . $cycle] ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Date Response <?php echo $cycle; ?></label>
              <input type="date" name="date_response<?php echo $cycle; ?>" class="form-control" value="<?php echo htmlspecialchars($row['date_response' . $cycle] ?? ''); ?>">
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    
    <!-- Assessment Procedure -->
    <div class="form-section">
      <div class="section-header">
        <h4 class="mb-0"><i class="fas fa-tasks me-2"></i>Assessment Procedure</h4>
      </div>
      <div class="mb-3">
        <label class="form-label">Assessment Procedure</label>
        <textarea name="assessment_procedure" class="form-control" rows="4"><?php echo htmlspecialchars($row['assessment_procedure'] ?? ''); ?></textarea>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
<a href="hmdr_page.php?stage_id=<?php echo $stage_id; ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Save Changes</button>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
