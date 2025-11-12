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

// Check if stage flow exists for this application type and current stage
$flowCheckQuery = "SELECT COUNT(*) as flow_count 
                   FROM tbl_stage_flow 
                   WHERE application_type = :app_type 
                   AND (to_stage_id = :current_stage OR from_stage_id = :current_stage)";
$flowCheckStmt = $pdo->prepare($flowCheckQuery);
$flowCheckStmt->execute([
    'app_type' => $application_type,
    'current_stage' => $current_stage_id
]);
$flowExists = $flowCheckStmt->fetchColumn() > 0;

$required_stages = [];
$stages_info = [];
$stage_flow_pairs = [];

if ($flowExists) {
    // Get the stage flow path (all stages leading to current stage)
    $stageFlowQuery = "
        WITH RECURSIVE stage_path AS (
            SELECT 
                sf.from_stage_id as stage_id,
                sf.to_stage_id,
                sf.sequence_order,
                1 as depth,
                CAST(sf.from_stage_id AS CHAR(200)) as path
            FROM tbl_stage_flow sf
            WHERE sf.to_stage_id = :current_stage
                AND sf.application_type = :app_type
            
            UNION ALL
            
            SELECT 
                sf.from_stage_id,
                sf.to_stage_id,
                sf.sequence_order,
                sp.depth + 1,
                CONCAT(CAST(sf.from_stage_id AS CHAR(200)), ',', sp.path)
            FROM tbl_stage_flow sf
            INNER JOIN stage_path sp ON sf.to_stage_id = sp.stage_id
            WHERE sf.application_type = :app_type
                AND sp.depth < 20
        )
        SELECT DISTINCT stage_id, to_stage_id, sequence_order
        FROM stage_path
        ORDER BY sequence_order ASC
    ";

    try {
        $stageFlowStmt = $pdo->prepare($stageFlowQuery);
        $stageFlowStmt->execute([
            'current_stage' => $current_stage_id,
            'app_type' => $application_type
        ]);
        $stage_flow_data = $stageFlowStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Store stage flow pairs for from/to mapping
        foreach ($stage_flow_data as $flow) {
            if ($flow['stage_id'] != 0) { // Skip initial stage (0)
                $required_stages[] = [
                    'stage_id' => $flow['stage_id'],
                    'sequence_order' => $flow['sequence_order']
                ];
                $stage_flow_pairs[$flow['stage_id']] = $flow['to_stage_id'];
            }
        }
    } catch (PDOException $e) {
        // Fallback for MySQL < 8.0 (no CTE support) - manual recursive approach
        error_log("CTE not supported, using fallback: " . $e->getMessage());
        $required_stages = [];
        $visited = [];
        $current_check = $current_stage_id;
        
        while ($current_check && !in_array($current_check, $visited)) {
            $visited[] = $current_check;
            
            $fallbackQuery = "SELECT from_stage_id, to_stage_id, sequence_order 
                            FROM tbl_stage_flow 
                            WHERE to_stage_id = :stage_id 
                            AND application_type = :app_type 
                            ORDER BY sequence_order DESC LIMIT 1";
            $fallbackStmt = $pdo->prepare($fallbackQuery);
            $fallbackStmt->execute(['stage_id' => $current_check, 'app_type' => $application_type]);
            $prev = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($prev && $prev['from_stage_id'] && $prev['from_stage_id'] != 0) {
                array_unshift($required_stages, [
                    'stage_id' => $prev['from_stage_id'],
                    'sequence_order' => $prev['sequence_order']
                ]);
                $stage_flow_pairs[$prev['from_stage_id']] = $prev['to_stage_id'];
                $current_check = $prev['from_stage_id'];
            } else {
                break;
            }
        }
    }

    // Also add the current stage to the list if it's not the initial stage
    if ($current_stage_id != 0) {
        $required_stages[] = ['stage_id' => $current_stage_id, 'sequence_order' => 999];
    }

    // Get stage details for all required stages
    $stage_ids = array_column($required_stages, 'stage_id');
    if (!empty($stage_ids)) {
        $stage_ids_placeholder = implode(',', array_fill(0, count($stage_ids), '?'));
        
        $stagesQuery = "SELECT status_id, status_description 
                        FROM tbl_hm_applications_status 
                        WHERE status_id IN ($stage_ids_placeholder)
                        AND status = 1
                        ORDER BY status_order ASC";
        $stagesStmt = $pdo->prepare($stagesQuery);
        $stagesStmt->execute($stage_ids);
        $stages_info = $stagesStmt->fetchAll(PDO::FETCH_KEY_PAIR);
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
    if (!isset($logs_by_stage[$log['stage_id']])) {
        $logs_by_stage[$log['stage_id']] = [];
    }
    $logs_by_stage[$log['stage_id']][] = $log;
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

// Create staff lookup array for easier access
$staff_lookup = [];
foreach ($staff_list as $staff) {
    $staff_lookup[$staff['staff_id']] = $staff['staff_names'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $stages_to_process = $_POST['stages'] ?? [];
        
        foreach ($stages_to_process as $stage_id => $stage_data) {
            // Skip if stage_data is not an array
            if (!is_array($stage_data)) {
                continue;
            }
            
            $assignment_id = $stage_data['assignment_id'] ?? null;
            
            // Skip if this is a read-only existing log
            if (isset($stage_data['is_readonly']) && $stage_data['is_readonly'] == '1') {
                continue;
            }
            
            // Skip if no data entered
            if (empty($stage_data['staff_id'] ?? '') && empty($stage_data['assignment_date'] ?? '')) {
                continue;
            }
            
            // NEW: Handle NULL values for current stage (last stage)
            $is_current_stage = ($stage_id == $current_stage_id);
            
            // For current stage, allow empty submission_date and submitted_to_stage_id to be NULL
            $submission_date = $stage_data['submission_date'] ?? null;
            $submitted_to_stage_id = $stage_data['submitted_to_stage_id'] ?? null;
            
            // Convert empty strings to NULL for current stage
            if ($is_current_stage) {
                if ($submission_date === '') {
                    $submission_date = null;
                }
                if ($submitted_to_stage_id === '') {
                    $submitted_to_stage_id = null;
                }
            }
            
            $data = [
                'application_id' => $app_id,
                'stage_id' => $stage_id,
                'from_stage_id' => $stage_data['from_stage_id'] ?? null,
                'staff_id' => $stage_data['staff_id'] ?? null,
                'assigned_by' => $user_id,
                'assignment_date' => $stage_data['assignment_date'] ?? date('Y-m-d H:i:s'),
                'submission_date' => $submission_date,
                'submitted_to_stage_id' => $submitted_to_stage_id,
                'assignment_status' => $stage_data['assignment_status'] ?? 'in-review',
                'application_type' => $application_type
            ];
            
            if ($assignment_id) {
                // Update existing record
                $data['assignment_id'] = $assignment_id;
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
        
        $pdo->commit();
        echo "<script>
                alert('Assignment logs updated successfully!');
                window.location.href='hmdr_page.php?stage_id={$current_stage_id}';
              </script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Create a mapping of stage sequence for auto-filling dates
$stage_sequence_map = [];
foreach ($required_stages as $index => $stage) {
    $stage_sequence_map[$stage['stage_id']] = [
        'sequence' => $index + 1,
        'data' => $stage
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Assignment Logs</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
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
    .no-flow-warning {
      background: linear-gradient(135deg, #fff3cd, #ffeaa7);
      border: 2px solid var(--warning-color);
      border-radius: 10px;
      padding: 30px;
      text-align: center;
      margin: 30px 0;
      box-shadow: var(--card-shadow);
    }
    .no-flow-warning i {
      font-size: 4rem;
      color: var(--warning-color);
      margin-bottom: 20px;
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
    .stage-card.readonly-stage {
      background: linear-gradient(to right, #f8f9fa, white);
      border-left-color: #6c757d;
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
      animation: pulse 2s infinite;
    }
    .stage-card.readonly-stage .stage-number {
      background-color: #6c757d;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
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
    .form-control:disabled, .form-select:disabled {
      background-color: #e9ecef;
      cursor: not-allowed;
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
    .existing-log-indicator {
      display: inline-block;
      padding: 5px 12px;
      background-color: #d4edda;
      color: #155724;
      border-radius: 20px;
      font-size: 0.85rem;
      margin-left: 10px;
    }
    .readonly-indicator {
      display: inline-block;
      padding: 5px 12px;
      background-color: #e2e3e5;
      color: #383d41;
      border-radius: 20px;
      font-size: 0.85rem;
      margin-left: 10px;
    }
    .required::after {
      content: " *";
      color: var(--accent-color);
    }
    .log-metadata {
      background-color: #f8f9fa;
      padding: 10px;
      border-radius: 5px;
      margin-top: 10px;
      font-size: 0.9rem;
      color: #6c757d;
    }
    .is-invalid {
      border-color: #dc3545;
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    .stage-flow-info {
      background-color: #e7f3ff;
      padding: 10px 15px;
      border-radius: 6px;
      margin-bottom: 15px;
      border-left: 4px solid var(--secondary-color);
    }
    .auto-filled-notice {
      background-color: #e8f5e8;
      border: 1px solid #28a745;
      border-radius: 4px;
      padding: 8px 12px;
      margin-top: 5px;
      font-size: 0.85rem;
      color: #155724;
    }
    .auto-fill-badge {
      display: inline-block;
      padding: 3px 8px;
      background-color: #28a745;
      color: white;
      border-radius: 12px;
      font-size: 0.7rem;
      margin-left: 8px;
      vertical-align: middle;
    }
    .readonly-field {
      background-color: #f8f9fa !important;
      border: 1px solid #dee2e6 !important;
      color: #6c757d !important;
    }
    .current-stage-info {
      background-color: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 6px;
      padding: 12px 15px;
      margin-bottom: 15px;
      font-size: 0.9rem;
      color: #856404;
    }
    /* Select2 customization */
    .select2-container--bootstrap-5 .select2-selection {
      border-radius: 6px;
      border: 1px solid #ced4da;
      transition: all 0.3s;
      min-height: 38px;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
      line-height: 36px;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
      height: 36px;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
      border-color: var(--secondary-color);
      box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
    .select2-container--bootstrap-5 .select2-dropdown {
      border-radius: 6px;
      border: 1px solid var(--secondary-color);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .select2-search--dropdown .select2-search__field {
      border-radius: 4px;
      border: 1px solid #ced4da;
    }
    .staff-select-wrapper {
      position: relative;
    }
  </style>
</head>
<body>
<div class="container mt-4 mb-5">
  <!-- Page Header -->
  <div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h2><i class="fas fa-clipboard-list me-2"></i>Update Assignment Logs</h2>
        <p class="mb-0 opacity-75">Track application progress through stages</p>
      </div>
      <a href="hmdr_page.php?stage_id=<?php echo $current_stage_id; ?>" class="btn btn-light">
        <i class="fas fa-arrow-left me-2"></i>Back to List
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
          <span class="badge bg-success"><?php echo htmlspecialchars($app_data['current_stage_name']); ?></span>
        </div>
      </div>
    </div>
  </div>

  <?php if (!$flowExists): ?>
    <!-- No Flow Warning -->
    <div class="no-flow-warning">
      <i class="fas fa-exclamation-triangle"></i>
      <h3 class="text-warning mb-3">No Stage Flow Configured</h3>
      <p class="lead mb-4">
        There is no stage flow configured for application type 
        <strong>"<?php echo htmlspecialchars($application_type); ?>"</strong> 
        with current stage <strong>"<?php echo htmlspecialchars($app_data['current_stage_name']); ?>"</strong>.
      </p>
      <p class="mb-4">
        Please configure the stage flow in <code>tbl_stage_flow</code> table to enable assignment log tracking.
      </p>
      <div class="alert alert-info text-start mx-auto" style="max-width: 600px;">
        <strong><i class="fas fa-lightbulb me-2"></i>Configuration Required:</strong>
        <ul class="mb-0 mt-2">
          <li>Define the stage progression path in <code>tbl_stage_flow</code></li>
          <li>Set <code>application_type</code> = "<?php echo htmlspecialchars($application_type); ?>"</li>
          <li>Map stages from initial to current stage (<?php echo $current_stage_id; ?>)</li>
        </ul>
      </div>
      <div class="mt-4">
        <a href="hmdr_page.php?stage_id=<?php echo $current_stage_id; ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left me-2"></i>Go Back
        </a>
      </div>
    </div>
  <?php else: ?>
    <!-- Assignment Logs Form -->
    <form method="POST" id="assignmentForm">
      <?php 
      $stage_counter = 1;
      $total_stages = count($required_stages);
      foreach ($required_stages as $req_stage): 
        $stage_id = $req_stage['stage_id'];
        $stage_name = $stages_info[$stage_id] ?? "Stage $stage_id";
        $is_current = ($stage_id == $current_stage_id);
        $is_last_stage = ($stage_counter == $total_stages);
        
        // Get the "to stage" from stage flow
        $to_stage_id = $stage_flow_pairs[$stage_id] ?? null;
        $to_stage_name = $stages_info[$to_stage_id] ?? "Stage $to_stage_id";
        
        // Get existing log for this stage (take the most recent one)
        $existing_log = null;
        $is_readonly = false;
        if (isset($logs_by_stage[$stage_id]) && count($logs_by_stage[$stage_id]) > 0) {
          $existing_log = end($logs_by_stage[$stage_id]);
          // Make it readonly if it's a completed/submitted log and not the current stage
          $is_readonly = !$is_current && 
                        ($existing_log['assignment_status'] == 'Completed' || 
                         !empty($existing_log['submission_date']));
        }
        
        // Calculate previous stage submission date for auto-fill
        $auto_fill_date = '';
        $show_auto_fill_notice = false;
        $previous_stage_name = '';
        
        // Only for stages greater than 1 (not the first stage)
        if ($stage_counter > 1) {
            $previous_stage_index = $stage_counter - 2; // Get previous stage from required_stages array
            if (isset($required_stages[$previous_stage_index])) {
                $previous_stage_id = $required_stages[$previous_stage_index]['stage_id'];
                $previous_stage_name = $stages_info[$previous_stage_id] ?? "Stage $previous_stage_id";
                
                // Check if previous stage has a submission date in existing logs
                if (isset($logs_by_stage[$previous_stage_id]) && count($logs_by_stage[$previous_stage_id]) > 0) {
                    $previous_log = end($logs_by_stage[$previous_stage_id]);
                    if (!empty($previous_log['submission_date'])) {
                        $auto_fill_date = date('Y-m-d\TH:i', strtotime($previous_log['submission_date']));
                        $show_auto_fill_notice = true;
                    }
                }
            }
        }

        // Determine if assignment date should be readonly
        $is_assignment_date_readonly = ($stage_counter > 1) && !$is_readonly;
      ?>
      
      <div class="stage-card <?php echo $is_current ? 'current-stage' : ''; ?> <?php echo $is_readonly ? 'readonly-stage' : ''; ?>" data-stage-id="<?php echo $stage_id; ?>" data-stage-sequence="<?php echo $stage_counter; ?>">
        <div class="stage-header">
          <div class="stage-number"><?php echo $stage_counter; ?></div>
          <div class="flex-grow-1">
            <h5 class="mb-1">
              <?php echo htmlspecialchars($stage_name); ?>
              <?php if ($stage_counter > 1 && !$existing_log): ?>
                <span class="auto-fill-badge" title="Assignment date will be auto-filled from previous stage submission">AUTO-FILL</span>
              <?php endif; ?>
            </h5>
            <small class="text-muted">Stage ID: <?php echo $stage_id; ?></small>
            <?php if ($existing_log): ?>
              <span class="existing-log-indicator">
                <i class="fas fa-check-circle me-1"></i>Existing Log
              </span>
            <?php endif; ?>
            <?php if ($is_readonly): ?>
              <span class="readonly-indicator">
                <i class="fas fa-lock me-1"></i>Read Only
              </span>
            <?php endif; ?>
            <?php if ($is_current): ?>
              <span class="badge bg-success ms-2">Current Stage</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Current Stage Information -->
        <?php if ($is_current && $is_last_stage): ?>
          <div class="current-stage-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Current Stage Notice:</strong> This is the current active stage. 
            Submission date can be left empty as the application is still in progress.
          </div>
        <?php endif; ?>

        <!-- Stage Flow Information -->
        <div class="stage-flow-info">
          <div class="row">
            <div class="col-md-6">
              <strong>From Stage:</strong> 
              <span class="badge bg-primary"><?php echo htmlspecialchars($stage_name); ?></span>
              <input type="hidden" name="stages[<?php echo $stage_id; ?>][from_stage_id]" value="<?php echo $stage_id; ?>">
            </div>
            <div class="col-md-6">
              <strong>To Stage:</strong> 
              <span class="badge bg-info"><?php echo htmlspecialchars($to_stage_name); ?></span>
              <?php if ($is_current && $is_last_stage): ?>
                <!-- For current/last stage, submitted_to_stage_id can be empty/NULL -->
                <input type="hidden" name="stages[<?php echo $stage_id; ?>][submitted_to_stage_id]" value="">
              <?php else: ?>
                <input type="hidden" name="stages[<?php echo $stage_id; ?>][submitted_to_stage_id]" value="<?php echo $to_stage_id; ?>">
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if ($existing_log): ?>
          <input type="hidden" name="stages[<?php echo $stage_id; ?>][assignment_id]" value="<?php echo $existing_log['assignment_id']; ?>">
          <?php if ($is_readonly): ?>
            <input type="hidden" name="stages[<?php echo $stage_id; ?>][is_readonly]" value="1">
          <?php endif; ?>
        <?php endif; ?>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label <?php echo !$is_readonly ? 'required' : ''; ?>">Assigned Staff</label>
            <div class="staff-select-wrapper">
              <select name="stages[<?php echo $stage_id; ?>][staff_id]" 
                      class="form-select staff-select searchable-select" 
                      <?php echo $is_readonly ? 'disabled' : ''; ?>
                      data-stage-id="<?php echo $stage_id; ?>">
                <option value="">Select Staff</option>
                <?php foreach ($staff_list as $staff): ?>
                  <option value="<?php echo $staff['staff_id']; ?>" 
                    <?php echo ($existing_log && $existing_log['staff_id'] == $staff['staff_id']) ? 'selected' : ''; ?>
                    data-email="<?php echo htmlspecialchars($staff['staff_email']); ?>">
                    <?php 
                      echo htmlspecialchars($staff['staff_names']); 
                      if (!empty($staff['staff_email'])) {
                        echo " (" . htmlspecialchars($staff['staff_email']) . ")";
                      }
                    ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label <?php echo !$is_readonly ? 'required' : ''; ?>">Assignment Date</label>
            <input type="datetime-local" 
                   name="stages[<?php echo $stage_id; ?>][assignment_date]" 
                   class="form-control assignment-date <?php echo $is_assignment_date_readonly ? 'readonly-field' : ''; ?>" 
                   value="<?php 
                     // Use auto-filled date if available and no existing log
                     if ($existing_log) {
                       echo date('Y-m-d\TH:i', strtotime($existing_log['assignment_date']));
                     } elseif ($auto_fill_date && !$existing_log) {
                       echo $auto_fill_date;
                     }
                   ?>"
                   <?php echo ($is_readonly || $is_assignment_date_readonly) ? 'readonly' : ''; ?>
                   data-stage-id="<?php echo $stage_id; ?>"
                   data-stage-sequence="<?php echo $stage_counter; ?>"
                   style="<?php echo $is_assignment_date_readonly ? 'background-color: #f8f9fa; color: #6c757d; cursor: not-allowed;' : ''; ?>">
            
            <?php if ($show_auto_fill_notice && !$existing_log): ?>
              <div class="auto-filled-notice">
                <i class="fas fa-info-circle me-1"></i>
                Auto-filled from previous stage "<?php echo htmlspecialchars($previous_stage_name); ?>" submission date
              </div>
            <?php elseif ($is_assignment_date_readonly && !$existing_log): ?>
              <div class="auto-filled-notice">
                <i class="fas fa-info-circle me-1"></i>
                Assignment date will be auto-filled from previous stage submission date
              </div>
            <?php endif; ?>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">
              Submission Date
              <?php if ($is_current && $is_last_stage): ?>
                <small class="text-muted">(Optional for current stage)</small>
              <?php endif; ?>
            </label>
            <input type="datetime-local" 
                   name="stages[<?php echo $stage_id; ?>][submission_date]" 
                   class="form-control submission-date" 
                   value="<?php echo $existing_log && $existing_log['submission_date'] ? date('Y-m-d\TH:i', strtotime($existing_log['submission_date'])) : ''; ?>"
                   <?php echo $is_readonly ? 'disabled' : ''; ?>
                   data-stage-id="<?php echo $stage_id; ?>"
                   data-stage-sequence="<?php echo $stage_counter; ?>"
                   <?php if ($is_current && $is_last_stage): ?>
                     placeholder="Leave empty for current stage"
                   <?php endif; ?>>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Status</label>
            <select name="stages[<?php echo $stage_id; ?>][assignment_status]" 
                    class="form-select status-select"
                    <?php echo $is_readonly ? 'disabled' : ''; ?>
                    data-stage-id="<?php echo $stage_id; ?>">
              <option value="in-review" <?php echo ($existing_log && $existing_log['assignment_status'] == 'in-review') ? 'selected' : 'selected'; ?>>In Review</option>
              <option value="query" <?php echo ($existing_log && $existing_log['assignment_status'] == 'query') ? 'selected' : ''; ?>>Query</option>
              <option value="completed" <?php echo ($existing_log && $existing_log['assignment_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Application Type</label>
            <input type="text" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($application_type); ?>" 
                   disabled>
            <input type="hidden" 
                   name="stages[<?php echo $stage_id; ?>][application_type]" 
                   value="<?php echo htmlspecialchars($application_type); ?>">
          </div>
        </div>

        <?php if ($existing_log): ?>
          <!-- Show existing log metadata -->
          <div class="log-metadata">
            <div class="row">
              <div class="col-md-4">
                <small><strong>Assigned By:</strong> 
                  <?php 
                  if ($existing_log['assigned_by']) {
                    echo htmlspecialchars($staff_lookup[$existing_log['assigned_by']] ?? 'Unknown');
                  } else {
                    echo 'N/A';
                  }
                  ?>
                </small>
              </div>
              <div class="col-md-4">
                <small><strong>Last Updated:</strong> 
                  <?php echo $existing_log['message_date'] ? date('M j, Y g:i A', strtotime($existing_log['message_date'])) : 'N/A'; ?>
                </small>
              </div>
              <div class="col-md-4">
                <small><strong>Message By:</strong> 
                  <?php 
                  if ($existing_log['message_by']) {
                    echo htmlspecialchars($staff_lookup[$existing_log['message_by']] ?? 'Unknown');
                  } else {
                    echo 'N/A';
                  }
                  ?>
                </small>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <?php $stage_counter++; ?>
      <?php endforeach; ?>

      <!-- Form Actions -->
      <div class="row mt-4">
        <div class="col-12">
          <div class="d-flex justify-content-between">
            <a href="hmdr_page.php?stage_id=<?php echo $current_stage_id; ?>" class="btn btn-secondary">
              <i class="fas fa-times me-2"></i>Cancel
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-2"></i>Update Assignment Logs
            </button>
          </div>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('assignmentForm');
    const stageCards = document.querySelectorAll('.stage-card');
    
    // Initialize Select2 for staff dropdowns
    $('.searchable-select').select2({
      theme: 'bootstrap-5',
      width: '100%',
      placeholder: 'Select Staff',
      allowClear: true,
      searchInputPlaceholder: 'Search by name or email...',
      templateResult: formatStaffOption,
      templateSelection: formatStaffSelection
    });

    // Format staff options in dropdown
    function formatStaffOption(staff) {
      if (!staff.id) return staff.text;
      
      const $option = $(
        '<div class="staff-option">' +
          '<div class="staff-name">' + staff.text + '</div>' +
        '</div>'
      );
      return $option;
    }

    // Format selected staff
    function formatStaffSelection(staff) {
      if (!staff.id) return staff.text;
      
      // Extract just the name part (remove email)
      const text = staff.text;
      const nameOnly = text.split(' (')[0];
      return nameOnly;
    }

    // Function to auto-fill assignment dates based on previous stage submissions
    function autoFillAssignmentDates() {
      stageCards.forEach((card, index) => {
        const stageSequence = parseInt(card.getAttribute('data-stage-sequence'));
        
        // Only process stages greater than 1 (not the first stage)
        if (stageSequence > 1) {
          const assignmentDateInput = card.querySelector('.assignment-date');
          const previousCard = stageCards[stageSequence - 2]; // Get previous stage card
          
          if (previousCard && assignmentDateInput && !assignmentDateInput.disabled) {
            const previousSubmissionInput = previousCard.querySelector('.submission-date');
            
            if (previousSubmissionInput && previousSubmissionInput.value) {
              // Auto-fill the assignment date
              assignmentDateInput.value = previousSubmissionInput.value;
              
              // Show auto-fill notice if not already present
              let notice = assignmentDateInput.parentNode.querySelector('.auto-filled-notice');
              if (!notice && !assignmentDateInput.disabled) {
                const previousStageName = previousCard.querySelector('h5').textContent.trim();
                notice = document.createElement('div');
                notice.className = 'auto-filled-notice';
                notice.innerHTML = `<i class="fas fa-info-circle me-1"></i>Auto-filled from previous stage "${previousStageName}" submission date`;
                assignmentDateInput.parentNode.appendChild(notice);
              }
            }
          }
        }
      });
    }
    
    // Function to clear assignment date if previous stage submission is cleared
    function clearAssignmentDateIfNoPreviousSubmission() {
      stageCards.forEach((card, index) => {
        const stageSequence = parseInt(card.getAttribute('data-stage-sequence'));
        
        // Only process stages greater than 1 (not the first stage)
        if (stageSequence > 1) {
          const assignmentDateInput = card.querySelector('.assignment-date');
          const previousCard = stageCards[stageSequence - 2];
          
          if (previousCard && assignmentDateInput && !assignmentDateInput.disabled) {
            const previousSubmissionInput = previousCard.querySelector('.submission-date');
            
            // If previous submission is cleared, clear the assignment date too
            if (!previousSubmissionInput.value || previousSubmissionInput.value === '') {
              assignmentDateInput.value = '';
              
              // Remove auto-fill notice
              const notice = assignmentDateInput.parentNode.querySelector('.auto-filled-notice');
              if (notice) {
                notice.remove();
              }
            }
          }
        }
      });
    }

    // Function to update assignment date readonly status
    function updateAssignmentDateReadonlyStatus() {
      stageCards.forEach((card, index) => {
        const stageSequence = parseInt(card.getAttribute('data-stage-sequence'));
        const assignmentDateInput = card.querySelector('.assignment-date');
        const isReadonlyStage = card.classList.contains('readonly-stage');
        
        if (assignmentDateInput) {
          // Stage 1: Never readonly unless it's a readonly stage
          // Stage 2+: Always readonly unless it's unlocked
          const shouldBeReadonly = (stageSequence > 1) && !isReadonlyStage;
          
          if (shouldBeReadonly) {
            assignmentDateInput.readOnly = true;
            assignmentDateInput.classList.add('readonly-field');
            assignmentDateInput.style.backgroundColor = '#f8f9fa';
            assignmentDateInput.style.color = '#6c757d';
            assignmentDateInput.style.cursor = 'not-allowed';
          } else {
            assignmentDateInput.readOnly = false;
            assignmentDateInput.classList.remove('readonly-field');
            assignmentDateInput.style.backgroundColor = '';
            assignmentDateInput.style.color = '';
            assignmentDateInput.style.cursor = '';
          }
        }
      });
    }
    
    // Initial setup
    updateAssignmentDateReadonlyStatus();
    autoFillAssignmentDates();
    
    // Add event listeners to submission date inputs to trigger auto-fill when they change
    document.querySelectorAll('.submission-date').forEach(input => {
      input.addEventListener('change', function() {
        setTimeout(() => {
          autoFillAssignmentDates();
        }, 100);
      });
      
      input.addEventListener('input', function() {
        setTimeout(() => {
          if (this.value) {
            autoFillAssignmentDates();
          } else {
            clearAssignmentDateIfNoPreviousSubmission();
          }
        }, 100);
      });
    });
    
    // Also trigger auto-fill when staff is selected (in case they edit stages out of order)
    document.querySelectorAll('.staff-select').forEach(select => {
      select.addEventListener('change', function() {
        setTimeout(autoFillAssignmentDates, 100);
      });
    });
    
    // Add form validation
    form.addEventListener('submit', function(e) {
      let isValid = true;
      const errorMessages = [];
      
      // Validate required fields for non-readonly stages
      const requiredSelects = document.querySelectorAll('.staff-select:not([disabled])');
      const requiredInputs = document.querySelectorAll('.assignment-date:not([disabled])');
      
      requiredSelects.forEach(select => {
        const label = select.closest('.mb-3').querySelector('.form-label');
        if (label && label.classList.contains('required') && !select.value) {
          isValid = false;
          select.classList.add('is-invalid');
          const stageName = select.closest('.stage-card').querySelector('h5').textContent;
          errorMessages.push(`Please select staff for stage: ${stageName}`);
        } else {
          select.classList.remove('is-invalid');
        }
      });
      
      requiredInputs.forEach(input => {
        const label = input.closest('.mb-3').querySelector('.form-label');
        if (label && label.classList.contains('required') && !input.value) {
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
      }
    });
    
    // Add real-time validation
    const inputs = document.querySelectorAll('.form-select:not([disabled]), .form-control:not([disabled])');
    inputs.forEach(input => {
      input.addEventListener('change', function() {
        if (this.value) {
          this.classList.remove('is-invalid');
        }
      });
      
      input.addEventListener('input', function() {
        if (this.value) {
          this.classList.remove('is-invalid');
        }
      });
    });
    
    // Auto-enable fields when user tries to interact with readonly stages
    const readonlyCards = document.querySelectorAll('.readonly-stage');
    readonlyCards.forEach(card => {
      card.addEventListener('click', function(e) {
        if (e.target.type !== 'button' && !e.target.classList.contains('btn') && 
            !e.target.classList.contains('form-control') && !e.target.classList.contains('form-select')) {
          const unlock = confirm('This stage log is read-only. Would you like to unlock it for editing?');
          if (unlock) {
            const inputs = this.querySelectorAll('input:disabled, select:disabled, textarea:disabled');
            inputs.forEach(input => {
              input.disabled = false;
              input.classList.add('bg-warning', 'bg-opacity-10');
            });
            // Remove readonly indicator
            this.classList.remove('readonly-stage');
            const readonlyIndicator = this.querySelector('.readonly-indicator');
            if (readonlyIndicator) {
              readonlyIndicator.remove();
            }
            // Update assignment date readonly status
            updateAssignmentDateReadonlyStatus();
            // Re-run auto-fill after unlocking
            setTimeout(autoFillAssignmentDates, 100);
          }
        }
      });
    });

    // Manual trigger for auto-fill (for debugging)
    window.triggerAutoFill = function() {
      autoFillAssignmentDates();
      alert('Auto-fill triggered manually');
    };
  });
</script>
</body>
</html>