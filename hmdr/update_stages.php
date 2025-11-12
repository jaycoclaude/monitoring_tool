<?php
session_start();
require_once '../includes/config.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check if app_id is provided
if (!isset($_GET['app_id'])) {
    die("Application ID is missing.");
}

$app_id = intval($_GET['app_id']);
$user_id = $_SESSION['user_id'];
$access = $_SESSION['user_access'];

if ($access <> 100) {
    die('You do not have permission to access this page. <br><a href="javascript:history.back()">Click here to go back</a>');
}
 

// Fetch application basic info and current stage
$sql = "SELECT 
            ha.hm_application_id,
            ha.reference_no,
            ha.tracking_no,
            ha.brand_name,
            ha.application_current_stage,
            ha.application_type,
            st.status_description as current_stage_name
        FROM tbl_hm_applications ha
        LEFT JOIN tbl_hm_applications_status st ON ha.application_current_stage = st.status_id
        WHERE ha.hm_application_id = :app_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['app_id' => $app_id]);
$app_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app_data) {
    die("No application found.");
}

$current_stage_id = $app_data['application_current_stage'];
$application_type = $app_data['application_type'] ?? 'GENERAL';

// Fetch all available stages for dropdown
$stagesQuery = "SELECT status_id, status_description 
                FROM tbl_hm_applications_status 
                WHERE status = 1 
                ORDER BY status_order ASC";
$stagesStmt = $pdo->prepare($stagesQuery);
$stagesStmt->execute();
$all_stages = $stagesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch stage flow for this application type
$stageFlowQuery = "SELECT sf.from_stage_id, sf.to_stage_id, sf.sequence_order,
                          from_stage.status_description as from_stage_name,
                          to_stage.status_description as to_stage_name
                   FROM tbl_stage_flow sf
                   LEFT JOIN tbl_hm_applications_status from_stage ON sf.from_stage_id = from_stage.status_id
                   LEFT JOIN tbl_hm_applications_status to_stage ON sf.to_stage_id = to_stage.status_id
                   WHERE sf.application_type = :app_type AND sf.is_active = 1
                   ORDER BY sf.sequence_order ASC";
$stageFlowStmt = $pdo->prepare($stageFlowQuery);
$stageFlowStmt->execute(['app_type' => $application_type]);
$stage_flow_data = $stageFlowStmt->fetchAll(PDO::FETCH_ASSOC);

// Build stage flow graph as an adjacency list (multiple possible next stages)
$stage_flow_graph = [];
$stage_names = [];
foreach ($stage_flow_data as $flow) {
    if (!isset($stage_flow_graph[$flow['from_stage_id']])) {
        $stage_flow_graph[$flow['from_stage_id']] = [];
    }
    $stage_flow_graph[$flow['from_stage_id']][] = $flow['to_stage_id'];
    
    $stage_names[$flow['from_stage_id']] = $flow['from_stage_name'];
    $stage_names[$flow['to_stage_id']] = $flow['to_stage_name'];
}

// Function to find ALL reachable stages from current stage using BFS
function findAllReachableStages($graph, $start) {
    $visited = [];
    $queue = [$start];
    $reachable = [];
    
    while (!empty($queue)) {
        $current = array_shift($queue);
        
        if (isset($visited[$current])) {
            continue;
        }
        
        $visited[$current] = true;
        
        if (isset($graph[$current])) {
            foreach ($graph[$current] as $next) {
                if (!isset($visited[$next])) {
                    $queue[] = $next;
                    $reachable[] = $next;
                }
            }
        }
    }
    
    return $reachable;
}

// Function to find path between two stages using BFS
function findStagePath($graph, $start, $end) {
    if ($start == $end) {
        return [$start];
    }
    
    $queue = [[$start, [$start]]];
    $visited = [$start => true];
    
    while (!empty($queue)) {
        list($current, $path) = array_shift($queue);
        
        if (isset($graph[$current])) {
            foreach ($graph[$current] as $next) {
                if (!isset($visited[$next])) {
                    $visited[$next] = true;
                    $new_path = array_merge($path, [$next]);
                    
                    if ($next == $end) {
                        return $new_path;
                    }
                    
                    $queue[] = [$next, $new_path];
                }
            }
        }
    }
    
    return null; // No path found
}

// Handle AJAX request for getting path
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_path' && isset($_GET['target_stage'])) {
    $target_stage_id = intval($_GET['target_stage']);
    $path = findStagePath($stage_flow_graph, $current_stage_id, $target_stage_id);
    
    if ($path) {
        $path_data = [];
        foreach ($path as $stage_id) {
            $path_data[] = [
                'id' => $stage_id,
                'name' => $stage_names[$stage_id] ?? "Stage $stage_id"
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($path_data);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No path found']);
        exit;
    }
}

// Get all reachable stages from current stage
$reachable_stages = findAllReachableStages($stage_flow_graph, $current_stage_id);

// Filter available stages to only show reachable ones
$potential_target_stages = [];
foreach ($all_stages as $stage) {
    if ($stage['status_id'] != $current_stage_id && in_array($stage['status_id'], $reachable_stages)) {
        $potential_target_stages[] = $stage;
    }
}

// Handle form submission for stage movement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_stage'])) {
    try {
        $pdo->beginTransaction();
        
        $target_stage_id = intval($_POST['target_stage_id']);
        $stages_to_process = $_POST['stages'] ?? [];
        
        // Validate target stage
        if ($target_stage_id == $current_stage_id) {
            throw new Exception("Target stage cannot be the same as current stage.");
        }
        
        if (!in_array($target_stage_id, $reachable_stages)) {
            throw new Exception("Invalid target stage selected. Stage is not reachable from current stage.");
        }
        
        // Find path from current stage to target stage
        $path = findStagePath($stage_flow_graph, $current_stage_id, $target_stage_id);
        
        if (!$path) {
            throw new Exception("No valid path found from current stage to target stage in the stage flow.");
        }
        
        // Verify all required stages in the path have assignment data
        $missing_stages = [];
        foreach ($path as $stage_id) {
            if (!isset($stages_to_process[$stage_id]) || 
                empty($stages_to_process[$stage_id]['staff_id']) || 
                empty($stages_to_process[$stage_id]['assignment_date'])) {
                $missing_stages[] = $stage_names[$stage_id] ?? "Stage $stage_id";
            }
        }
        
        if (!empty($missing_stages)) {
            throw new Exception("Missing assignment data for stages: " . implode(', ', $missing_stages));
        }
        
        // Process each stage in the path
        foreach ($path as $index => $stage_id) {
            $stage_data = $stages_to_process[$stage_id];
            
            // Determine from_stage_id and to_stage_id
            $from_stage_id = ($index > 0) ? $path[$index - 1] : $stage_id;
            $to_stage_id = ($index < count($path) - 1) ? $path[$index + 1] : null;
            
            $data = [
                'application_id' => $app_id,
                'stage_id' => $stage_id,
                'from_stage_id' => $from_stage_id,
                'staff_id' => $stage_data['staff_id'],
                'assigned_by' => $user_id,
                'assignment_date' => $stage_data['assignment_date'],
                'submission_date' => $stage_data['submission_date'] ?? null,
                'submitted_to_stage_id' => $to_stage_id,
                'assignment_status' => $stage_data['assignment_status'] ?? 'completed',
                'application_type' => $application_type
            ];
            
            // Check if assignment already exists
            $checkQuery = "SELECT assignment_id FROM tbl_application_assignment 
                          WHERE application_id = :application_id AND stage_id = :stage_id";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute(['application_id' => $app_id, 'stage_id' => $stage_id]);
            $existing_assignment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_assignment) {
                // Update existing record
                $data['assignment_id'] = $existing_assignment['assignment_id'];
                $updateQuery = "UPDATE tbl_application_assignment SET
                    from_stage_id = :from_stage_id,
                    staff_id = :staff_id,
                    assigned_by = :assigned_by,
                    assignment_date = :assignment_date,
                    submission_date = :submission_date,
                    submitted_to_stage_id = :submitted_to_stage_id,
                    assignment_status = :assignment_status,
                    application_type = :application_type
                  WHERE assignment_id = :assignment_id";
                
                $stmt = $pdo->prepare($updateQuery);
                $stmt->execute($data);
            } else {
                // Insert new record
                $insertQuery = "INSERT INTO tbl_application_assignment 
                    (application_id, stage_id, from_stage_id, staff_id, assigned_by, 
                     assignment_date, submission_date, submitted_to_stage_id, 
                     assignment_status, application_type)
                  VALUES 
                    (:application_id, :stage_id, :from_stage_id, :staff_id, :assigned_by, 
                     :assignment_date, :submission_date, :submitted_to_stage_id, 
                     :assignment_status, :application_type)";
                
                $stmt = $pdo->prepare($insertQuery);
                $stmt->execute($data);
            }
        }
        
        // Update application current stage
        $updateAppQuery = "UPDATE tbl_hm_applications 
                          SET application_current_stage = :new_stage 
                          WHERE hm_application_id = :app_id";
        $updateAppStmt = $pdo->prepare($updateAppQuery);
        $updateAppStmt->execute([
            'new_stage' => $target_stage_id,
            'app_id' => $app_id
        ]);
        
        $pdo->commit();
        
        echo "<script>
                alert('Application successfully moved to new stage!');
                window.location.href='hmdr_page.php?stage_id={$current_stage_id}';
              </script>";
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// Fetch existing assignment logs for this application
$logsQuery = "SELECT * FROM tbl_application_assignment 
              WHERE application_id = :app_id 
              ORDER BY assignment_date ASC";
$logsStmt = $pdo->prepare($logsQuery);
$logsStmt->execute(['app_id' => $app_id]);
$existing_logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// Index existing logs by stage_id for easy access
$logs_by_stage = [];
foreach ($existing_logs as $log) {
    $logs_by_stage[$log['stage_id']] = $log;
}

// Fetch all staff for dropdown
$staffQuery = "SELECT staff_id, staff_names, staff_email 
               FROM tbl_staff 
               WHERE staff_status = 1 
               ORDER BY staff_names ASC";
try {
    $staffStmt = $pdo->query($staffQuery);
    $staff_list = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $staff_list = [];
    error_log("Error fetching staff list: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Move Application to Another Stage</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #3498db;
      --accent-color: #e74c3c;
      --success-color: #27ae60;
      --warning-color: #f39c12;
      --light-bg: #f8f9fa;
      --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    body {
      background-color: var(--light-bg);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .page-header {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 30px;
      box-shadow: var(--card-shadow);
    }
    .app-info-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 30px;
      box-shadow: var(--card-shadow);
    }
    .info-badge {
      display: inline-block;
      padding: 8px 15px;
      background-color: #e3f2fd;
      border-left: 4px solid var(--secondary-color);
      margin: 5px 10px 5px 0;
      border-radius: 4px;
    }
    .stage-card {
      background: white;
      border-radius: 10px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: var(--card-shadow);
      border-left: 5px solid var(--secondary-color);
      transition: all 0.3s ease;
    }
    .stage-card:hover {
      transform: translateX(5px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    .stage-card.current-stage {
      border-left-color: var(--success-color);
      background: linear-gradient(to right, #f0fff4, white);
    }
    .stage-card.intermediate-stage {
      border-left-color: var(--secondary-color);
      background: linear-gradient(to right, #e7f3ff, white);
    }
    .stage-card.target-stage {
      border-left-color: var(--warning-color);
      background: linear-gradient(to right, #fff3cd, white);
    }
    .stage-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e9ecef;
    }
    .stage-number {
      width: 40px;
      height: 40px;
      background-color: var(--secondary-color);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      margin-right: 15px;
      flex-shrink: 0;
    }
    .stage-card.current-stage .stage-number {
      background-color: var(--success-color);
    }
    .stage-card.target-stage .stage-number {
      background-color: var(--warning-color);
    }
    .form-label {
      font-weight: 600;
      color: var(--primary-color);
      margin-bottom: 8px;
    }
    .form-control, .form-select {
      border-radius: 6px;
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
      padding: 12px 30px;
      border-radius: 6px;
      font-weight: 600;
    }
    .btn-secondary {
      background-color: #6c757d;
      border: none;
      padding: 12px 30px;
      border-radius: 6px;
      font-weight: 600;
    }
    .required::after {
      content: " *";
      color: var(--accent-color);
    }
    .is-invalid {
      border-color: #dc3545;
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    .stage-flow-path {
      background-color: #e7f3ff;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid var(--secondary-color);
    }
    .path-stage {
      display: inline-block;
      padding: 8px 15px;
      background-color: white;
      border: 2px solid var(--secondary-color);
      border-radius: 20px;
      margin: 0 5px;
      font-weight: 600;
    }
    .path-arrow {
      display: inline-block;
      margin: 0 10px;
      color: var(--secondary-color);
      font-weight: bold;
    }
    .current-stage-indicator {
      background-color: var(--success-color) !important;
      color: white;
      border-color: var(--success-color) !important;
    }
    .target-stage-indicator {
      background-color: var(--warning-color) !important;
      color: white;
      border-color: var(--warning-color) !important;
    }
    .intermediate-stage-indicator {
      background-color: var(--secondary-color) !important;
      color: white;
      border-color: var(--secondary-color) !important;
    }
    .alert-custom {
      border-radius: 10px;
      border: none;
      box-shadow: var(--card-shadow);
    }
    .loading-spinner {
      display: none;
      text-align: center;
      padding: 20px;
    }
  </style>
</head>
<body>
<div class="container mt-4 mb-5">
  <!-- Page Header -->
  <div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h2><i class="fas fa-exchange-alt me-2"></i>Move Application to Another Stage</h2>
        <p class="mb-0 opacity-75">Update application stage with proper assignment logs</p>
      </div>
      <a href="hmdr_page.php?stage_id=<?php echo $current_stage_id; ?>" class="btn btn-light">
        <i class="fas fa-arrow-left me-2"></i>Back to Assignment Logs
      </a>
    </div>
  </div>

  <!-- Application Information Card -->
  <div class="app-info-card">
    <h4 class="mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Application Information</h4>
    <div class="row">
      <div class="col-md-6">
        <div class="info-badge">
          <strong>Reference No:</strong> <?php echo htmlspecialchars($app_data['reference_no']); ?>
        </div>
        <div class="info-badge">
          <strong>Tracking No:</strong> <?php echo htmlspecialchars($app_data['tracking_no']); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="info-badge">
          <strong>Brand Name:</strong> <?php echo htmlspecialchars($app_data['brand_name']); ?>
        </div>
        <div class="info-badge">
          <strong>Current Stage:</strong> 
          <span class="badge bg-success"><?php echo htmlspecialchars($app_data['current_stage_name']); ?> (Stage <?php echo $current_stage_id; ?>)</span>
        </div>
      </div>
    </div>
  </div>

  <?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-custom">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
    </div>
  <?php endif; ?>

  <!-- Stage Movement Form -->
  <form method="POST" id="moveStageForm">
    <input type="hidden" name="move_stage" value="1">
    
    <!-- Target Stage Selection -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-bullseye me-2"></i>Select Target Stage</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <label class="form-label required">Move Application To</label>
            <select name="target_stage_id" id="targetStageSelect" class="form-select" required>
              <option value="">Select Target Stage</option>
              <?php foreach ($potential_target_stages as $stage): ?>
                <option value="<?php echo $stage['status_id']; ?>">
                  <?php echo htmlspecialchars($stage['status_description']); ?> (Stage <?php echo $stage['status_id']; ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">
              Available stages: <?php echo count($potential_target_stages); ?> reachable stages from current stage
            </small>
          </div>
          <div class="col-md-6">
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Note:</strong> Moving the application will require you to fill assignment logs for all intermediate stages in the path.
            </div>
          </div>
        </div>
        
        <!-- Stage Flow Path Display -->
        <div id="stagePathContainer" class="stage-flow-path" style="display: none;">
          <h6 class="mb-3"><i class="fas fa-route me-2"></i>Stage Flow Path</h6>
          <div id="stagePathDisplay"></div>
          <small class="text-muted mt-2 d-block">You need to provide assignment data for all stages in this path</small>
        </div>
      </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="loading-spinner">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading stage path and assignment forms...</p>
    </div>

    <!-- Assignment Logs for Stages in Path -->
    <div id="assignmentLogsContainer" style="display: none;">
      <div class="card">
        <div class="card-header bg-warning text-dark">
          <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Assignment Logs for Stages in Path</h5>
        </div>
        <div class="card-body">
          <div id="stagesAssignmentContainer"></div>
        </div>
      </div>
    </div>

    <!-- Form Actions -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="d-flex justify-content-between">
          <a href="hmdr_page.php?stage_id=<?php echo $current_stage_id; ?>" class="btn btn-secondary">
            <i class="fas fa-times me-2"></i>Cancel
          </a>
          <button type="submit" class="btn btn-primary" id="submitButton" disabled>
            <i class="fas fa-exchange-alt me-2"></i>Move Application to New Stage
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const targetStageSelect = document.getElementById('targetStageSelect');
    const stagePathContainer = document.getElementById('stagePathContainer');
    const stagePathDisplay = document.getElementById('stagePathDisplay');
    const assignmentLogsContainer = document.getElementById('assignmentLogsContainer');
    const stagesAssignmentContainer = document.getElementById('stagesAssignmentContainer');
    const submitButton = document.getElementById('submitButton');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const form = document.getElementById('moveStageForm');
    
    const currentStageId = <?php echo $current_stage_id; ?>;
    const currentStageName = "<?php echo htmlspecialchars($app_data['current_stage_name']); ?>";
    const staffList = <?php echo json_encode($staff_list); ?>;
    const existingLogs = <?php echo json_encode($logs_by_stage); ?>;
    const appId = <?php echo $app_id; ?>;
    
    // Function to update stage path display
    function updateStagePathDisplay(pathStages) {
      let pathHTML = '';
      pathStages.forEach((stage, index) => {
        const stageClass = index === 0 ? 'current-stage-indicator' : 
                          index === pathStages.length - 1 ? 'target-stage-indicator' : 'intermediate-stage-indicator';
        
        pathHTML += `<span class="path-stage ${stageClass}">${stage.name} (Stage ${stage.id})</span>`;
        
        if (index < pathStages.length - 1) {
          pathHTML += '<span class="path-arrow"><i class="fas fa-arrow-right"></i></span>';
        }
      });
      
      stagePathDisplay.innerHTML = pathHTML;
      stagePathContainer.style.display = 'block';
    }
    
    // Function to create assignment form for a stage
    function createStageAssignmentForm(stage, index, totalStages) {
      const stageId = stage.id;
      const stageName = stage.name;
      const existingLog = existingLogs[stageId] || null;
      const staffOptions = staffList.map(staff => 
        `<option value="${staff.staff_id}" ${existingLog && existingLog.staff_id == staff.staff_id ? 'selected' : ''}>
          ${staff.staff_names} ${staff.staff_email ? '(' + staff.staff_email + ')' : ''}
        </option>`
      ).join('');
      
      let stageType = 'intermediate-stage';
      let stageBadge = '';
      
      if (index === 0) {
        stageType = 'current-stage';
        stageBadge = '<span class="badge bg-success ms-2">Current Stage</span>';
      } else if (index === totalStages - 1) {
        stageType = 'target-stage';
        stageBadge = '<span class="badge bg-warning ms-2">Target Stage</span>';
      } else {
        stageBadge = '<span class="badge bg-info ms-2">Intermediate Stage</span>';
      }
      
      return `
        <div class="stage-card ${stageType}" data-stage-id="${stageId}">
          <div class="stage-header">
            <div class="stage-number">${index + 1}</div>
            <div class="flex-grow-1">
              <h5 class="mb-1">${stageName}</h5>
              <small class="text-muted">Stage ID: ${stageId}</small>
              ${existingLog ? '<span class="badge bg-secondary ms-2">Existing Log</span>' : ''}
              ${stageBadge}
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label required">Assigned Staff</label>
              <select name="stages[${stageId}][staff_id]" class="form-select staff-select" required>
                <option value="">Select Staff</option>
                ${staffOptions}
              </select>
            </div>
            
            <div class="col-md-4 mb-3">
              <label class="form-label required">Assignment Date</label>
              <input type="datetime-local" 
                     name="stages[${stageId}][assignment_date]" 
                     class="form-control assignment-date" 
                     value="${existingLog ? existingLog.assignment_date.substring(0, 16) : ''}"
                     required>
            </div>
            
            <div class="col-md-4 mb-3">
              <label class="form-label">Submission Date</label>
              <input type="datetime-local" 
                     name="stages[${stageId}][submission_date]" 
                     class="form-control submission-date" 
                     value="${existingLog && existingLog.submission_date ? existingLog.submission_date.substring(0, 16) : ''}">
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <select name="stages[${stageId}][assignment_status]" class="form-select status-select">
                <option value="completed" ${existingLog && existingLog.assignment_status == 'completed' ? 'selected' : 'selected'}>Completed</option>
                <option value="in-review" ${existingLog && existingLog.assignment_status == 'in-review' ? 'selected' : ''}>In Review</option>
                <option value="query" ${existingLog && existingLog.assignment_status == 'query' ? 'selected' : ''}>Query</option>
              </select>
            </div>
          </div>
          
          ${existingLog ? `
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              Existing assignment log found. You can update the information if needed.
            </div>
          ` : ''}
        </div>
      `;
    }
    
    // Function to fetch stage path and update forms
    async function fetchStagePathAndUpdateForms(targetStageId) {
      try {
        loadingSpinner.style.display = 'block';
        assignmentLogsContainer.style.display = 'none';
        submitButton.disabled = true;
        
        const response = await fetch(`?app_id=${appId}&ajax=get_path&target_stage=${targetStageId}`);
        const pathData = await response.json();
        
        if (pathData.error) {
          throw new Error(pathData.error);
        }
        
        // Update path display
        updateStagePathDisplay(pathData);
        
        // Create assignment forms for all stages in path
        let assignmentFormsHTML = '';
        pathData.forEach((stage, index) => {
          assignmentFormsHTML += createStageAssignmentForm(stage, index, pathData.length);
        });
        
        stagesAssignmentContainer.innerHTML = assignmentFormsHTML;
        assignmentLogsContainer.style.display = 'block';
        submitButton.disabled = false;
        
      } catch (error) {
        console.error('Error fetching stage path:', error);
        alert('Error loading stage path: ' + error.message);
        stagePathContainer.style.display = 'none';
        assignmentLogsContainer.style.display = 'none';
        submitButton.disabled = true;
      } finally {
        loadingSpinner.style.display = 'none';
      }
    }
    
    // Event listener for target stage selection
    targetStageSelect.addEventListener('change', function() {
      if (this.value) {
        fetchStagePathAndUpdateForms(this.value);
      } else {
        stagePathContainer.style.display = 'none';
        assignmentLogsContainer.style.display = 'none';
        submitButton.disabled = true;
      }
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
      let isValid = true;
      const errorMessages = [];
      
      // Validate target stage selection
      if (!targetStageSelect.value) {
        isValid = false;
        errorMessages.push('Please select a target stage');
      }
      
      // Validate assignment forms
      const staffSelects = document.querySelectorAll('.staff-select');
      const assignmentDates = document.querySelectorAll('.assignment-date');
      
      staffSelects.forEach(select => {
        if (!select.value) {
          isValid = false;
          select.classList.add('is-invalid');
          const stageName = select.closest('.stage-card').querySelector('h5').textContent;
          errorMessages.push(`Please select staff for stage: ${stageName}`);
        } else {
          select.classList.remove('is-invalid');
        }
      });
      
      assignmentDates.forEach(input => {
        if (!input.value) {
          isValid = false;
          input.classList.add('is-invalid');
          const stageName = input.closest('.stage-card').querySelector('h5').textContent;
          errorMessages.push(`Please enter assignment date for stage: ${stageName}`);
        } else {
          input.classList.remove('is-invalid');
        }
      });
      
      if (!isValid) {
        e.preventDefault();
        const errorMessage = errorMessages.join('\n');
        alert('Please fix the following errors:\n\n' + errorMessage);
        
        // Scroll to first error
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else {
        // Confirm action
        const targetStageName = targetStageSelect.options[targetStageSelect.selectedIndex].text.split(' (Stage')[0];
        const confirmMessage = `Are you sure you want to move this application from "${currentStageName}" to "${targetStageName}"? This action will update all assignment logs and change the current stage.`;
        
        if (!confirm(confirmMessage)) {
          e.preventDefault();
        }
      }
    });
    
    // Real-time validation
    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('staff-select') || e.target.classList.contains('assignment-date')) {
        if (e.target.value) {
          e.target.classList.remove('is-invalid');
        }
      }
    });
  });
</script>
</body>
</html>