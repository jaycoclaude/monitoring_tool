<?php
session_start();
require_once '../includes/config.php'; // PDO connection assumed

// Check login session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_access'])) {
    error_log("HMDR Details: Unauthorized access attempt - No session found");
    echo '<div class="alert alert-danger"><p>Not allowed to access. Please login. <a href="../index.php">Click here</a></p></div>';
    exit();
}

$datetoday = date("Y-m-d");
$user_id = $_SESSION['user_id'];
$user_access = $_SESSION['user_access'];
$app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;
$stage_id = $_GET['stage_id'] ?? 'all';
$by = $_GET['by'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

error_log("HMDR Details: Loading page for app_id=$app_id, user_id=$user_id, user_access=$user_access, stage_id=$stage_id");

// Function to log audit trail
function logAuditTrail($pdo, $application_id, $user_id, $user_email, $action_type, $table_name, $field_name, $old_value, $new_value) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO tbl_hm_application_audit 
            (application_id, user_id, user_email, action_type, table_name, field_name, old_value, new_value, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $application_id,
            $user_id,
            $user_email,
            $action_type,
            $table_name,
            $field_name,
            $old_value,
            $new_value,
            $ip_address,
            $user_agent
        ]);
        
        error_log("FSMIL Audit: Logged $action_type for application $application_id, field: $field_name");
        return true;
    } catch (Exception $e) {
        error_log("FSMIL Audit: ERROR logging audit trail - " . $e->getMessage());
        return false;
    }
}

// Handle form submission for date updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_dates']) && $user_access == 100) {
    error_log("FSMIL Details: Update request received for app_id=$app_id");
    
    try {
        // Get current application data for audit trail
        $stmt = $pdo->prepare("SELECT * FROM tbl_hm_applications_premise_food WHERE application_id = :app_id");
        $stmt->execute(['app_id' => $app_id]);
        $current_application = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$current_application) {
            throw new Exception("Application not found");
        }
        
        // Get user email for audit trail
        $user_stmt = $pdo->prepare("SELECT user_email FROM tbl_hm_users WHERE user_id = :user_id");
        $user_stmt->execute(['user_id' => $user_id]);
        $user_data = $user_stmt->fetch(PDO::FETCH_OBJ);
        $user_email = $user_data->user_email ?? 'Unknown';
        
        // Prepare update fields and values
        $update_fields = [];
        $update_values = ['app_id' => $app_id];
        
        // Define date fields to update
        $date_fields = [
            'date_assessment1', 'date_assessment2',
            'date_assessment3', 'date_inspection1', 'date_inspection2',
            'date_inspection3', 'date_query_assessment1', 'date_query_assessment2',
            'date_query_assessment3', 'date_response1','date_response2','date_response3','license_issue_date','license_expiry_date'
        ];
        
        // Build update query and log changes
        foreach ($date_fields as $field) {
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                $new_value = $_POST[$field];
                $old_value = $current_application->$field ?? null;
                
                // Only update if value changed
                if ($new_value != $old_value) {
                    $update_fields[] = "$field = :$field";
                    $update_values[$field] = $new_value;
                    
                    // Log the change
                    logAuditTrail(
                        $pdo, 
                        $app_id, 
                        $user_id, 
                        $user_email, 
                        'UPDATE', 
                        'tbl_hm_applications_premise_food', 
                        $field, 
                        $old_value, 
                        $new_value
                    );
                    
                    error_log("FSMIL Update: Field $field changed from '$old_value' to '$new_value'");
                }
            }
        }
        
        // Only execute update if there are changes
        if (!empty($update_fields)) {
            $update_fields[] = "updated_by = :updated_by";
            $update_values['updated_by'] = $user_id;
            
            $update_sql = "UPDATE tbl_hm_applications_premise_food SET " . implode(', ', $update_fields) . " WHERE application_id = :app_id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($update_values);
            
            error_log("FSMIL Details: Successfully updated " . count($update_fields) . " fields for application $app_id");
            
            // Show success message
            echo '<div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong><i class="fas fa-check-circle"></i> Success!</strong> Dates updated successfully.
                  </div>';
                  
            // Refresh application data
            $stmt = $pdo->prepare("
                SELECT 
                    a.*,
                    u.user_email as updated_by_email
                FROM tbl_hm_applications_premise_food a
                LEFT JOIN tbl_hm_users u ON a.updated_by = u.user_id
                WHERE a.application_id = :app_id 
                LIMIT 1
            ");
            $stmt->execute(['app_id' => $app_id]);
            $application = $stmt->fetch(PDO::FETCH_OBJ);
        } else {
            error_log("FSMIL Details: No changes detected for application $app_id");
            echo '<div class="alert alert-info alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong><i class="fas fa-info-circle"></i> Info!</strong> No changes were made.
                  </div>';
        }
        
    } catch (Exception $e) {
        error_log("FSMIL Details: ERROR updating application $app_id - " . $e->getMessage());
        echo '<div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong><i class="fas fa-exclamation-circle"></i> Error!</strong> Failed to update dates: ' . htmlspecialchars($e->getMessage()) . '
              </div>';
    }
}

// Validate user
try {
    error_log("FSMIL Details: Validating user $user_id");
    $stmt = $pdo->prepare("SELECT user_id, user_status, user_email FROM tbl_hm_users WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        error_log("FSMIL Details: User $user_id not found in database");
        session_destroy();
        echo '<div class="alert alert-danger"><p>Not allowed to access. Please login.</p></div>';
        exit();
    }

    if ($user->user_status != 1) {
        error_log("FSMIL Details: User $user_id has invalid status: " . $user->user_status);
        session_destroy();
        echo '<div class="alert alert-danger"><p>Not allowed to access. Please login.</p></div>';
        exit();
    }

    error_log("FSMIL Details: User $user_id validation successful");

} catch (Exception $e) {
    error_log('FSMIL Details: CRITICAL ERROR in user validation - ' . $e->getMessage());
    echo '<div class="alert alert-danger"><p>An error occurred. Please contact the administrator.</p></div>';
    exit();
}

// Fetch application details with user name
try {
    error_log("FSMIL Details: Fetching application details for app_id=$app_id");
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            u.user_email as updated_by_email
        FROM tbl_hm_applications_premise_food a
        LEFT JOIN tbl_hm_users u ON a.updated_by = u.user_id
        WHERE a.application_id = :app_id 
        LIMIT 1
    ");
    $stmt->execute(['app_id' => $app_id]);
    $application = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$application) {
        error_log("FSMIL Details: Application $app_id not found in database");
        echo '<div class="alert alert-danger"><p>Application not found.</p></div>';
        exit();
    }

    error_log("FSMIL Details: Successfully fetched application $app_id - " . $application->applicant_name);
    error_log("FSMIL Details: Application stage: " . $application->application_current_stage . ", pathway: " . $application->assessment_procedure);

} catch (Exception $e) {
    error_log('FSMIL Details: ERROR fetching application details for app_id=' . $app_id . ' - ' . $e->getMessage());
    echo '<div class="alert alert-danger"><p>Error fetching application details. Please contact the administrator.</p></div>';
    exit();
}

// Fetch timeline information
$days_processing_monitoring = 'N/A';
try {
    error_log("FSMIL Details: Fetching timelines for stage_id=" . $application->application_current_stage . ", pathway=" . $application->assessment_procedure);
    
    $stmt = $pdo->prepare("SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway");
    $stmt->execute([
        ':status_id' => $application->application_current_stage,
        ':assessment_pathway' => $application->assessment_procedure
    ]);
    $timelines = $stmt->fetchAll(PDO::FETCH_OBJ);

    if (empty($timelines)) {
        error_log("FSMIL Details: No timeline found for stage_id=" . $application->application_current_stage . " and pathway=" . $application->assessment_procedure);
        $days_processing_monitoring = "<strong><font color='red'>No timeline configured</font></strong>";
    } else {
        error_log("FSMIL Details: Found " . count($timelines) . " timeline configurations");
        
        foreach ($timelines as $timeline) {
            $number_of_days = intval($timeline->number_of_days);
            $days_processing = (strtotime($datetoday) - strtotime($application->date_submitted)) / 86400;

            error_log("FSMIL Details: Timeline calculation - Allowed: $number_of_days days, Actual: " . number_format($days_processing, 2) . " days");

            if ($days_processing > $number_of_days) {
                $delay_days = $days_processing - $number_of_days;
                error_log("FSMIL Details: Application $app_id is DELAYED by " . number_format($delay_days) . " days");
                $days_processing_monitoring = "<strong><font color='blue'>($number_of_days)</font><br><font color='red'>" . number_format($delay_days) . " days<br>Delay</font></strong>";
            } else {
                $remaining_days = $number_of_days - $days_processing;
                error_log("FSMIL Details: Application $app_id is ON TIME with " . number_format($remaining_days) . " days remaining");
                $days_processing_monitoring = "<strong><font color='blue'>" . number_format($remaining_days) . " days<br>On time</font></strong>";
            }
        }
    }
} catch (Exception $e) {
    error_log('FSMIL Details: ERROR fetching timelines for app_id=' . $app_id . ' - ' . $e->getMessage());
    $days_processing_monitoring = "<strong><font color='red'>Error calculating timeline</font></strong>";
}
// Function to map stage_id to stage name
function getStageName($stage_id) {
    $stage_names = [
        '1' => 'Pending Assessment',
        '2' => 'Under Assessment',
        '3' => 'Assessed',
        '4' => 'Queried',
        '5' => 'Query Letter Sent',
        '6' => 'Inspection Scheduling',
        '7' => 'Inspected',
        '8' => 'ILTC',
        '10' => 'Registered',
        '14' => 'Feedback Letter',
        '12' => 'Applied for Reinspection',
        '21' => 'Applied for renewal',
        '22' => 'Withdrawn',
        '25' => 'Approved',
        '30' => 'Expired',
        '35' => 'Closed',
        '36' => 'Backlog',
        '37' => 'Licensed with Commitment'
    ];
    return $stage_names[$stage_id] ?? 'Stage ' . $stage_id;
}

$stage_name = getStageName($application->application_current_stage);

// Build back URL, ensuring stage_id is included (defaults to 'all' if not set)
$back_url = 'fsmil_page.php?' . http_build_query(array_filter([
    'stage_id' => $stage_id, // Ensures stage_id is passed to maintain filter context
    'by' => $by,
    'page' => $page,
    'limit' => $limit
]));
?>

<head>
    <title>Inspection & Licensing Food - Application Details - <?php echo htmlspecialchars($application->applicant_name); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f5f7f9;
            color: #333;
            line-height: 1.5;
            padding: 0;
            font-size: 12px;
        }
        .header {
            position: sticky;
            top: 0;
            z-index: 30;
            backdrop-filter: blur(4px);
            background-color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid #e7eef6;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.04);
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
        }
        .branding {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background-color: #0f5e8a;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        .brand-text h1 {
            font-size: 12px;
            font-weight: 600;
            color: #1a202c;
        }
        .brand-text p {
            font-size: 10px;
            color: #6b7a86;
            margin-top: -2px;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .icon-button {
            padding: 6px;
            border-radius: 50%;
            background: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .icon-button:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .notification-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 6px;
            height: 6px;
            background-color: #e53e3e;
            border-radius: 50%;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 6px;
            background-color: white;
            border: 1px solid #e8f1f8;
            padding: 5px 10px;
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .user-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #f0f6fb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f5e8a;
            font-size: 12px;
        }
        .user-name {
            font-size: 11px;
            font-weight: 500;
            display: none;
        }
        @media (min-width: 640px) {
            .user-name {
                display: block;
            }
        }
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 15px;
        }
        .dashboard-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 15px;
        }
        .roadmap {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            height: fit-content;
            border: 1px solid #e7eef6;
        }
        .roadmap h3 {
            text-align: center;
            margin-bottom: 15px;
            color: #1a202c;
            padding-bottom: 8px;
            border-bottom: 1px solid #e7eef6;
            font-weight: 600;
            font-size: 14px;
        }
        .roadmap-list {
            list-style: none;
            padding: 0;
            margin-bottom: 15px;
        }
        .roadmap-list li {
            margin-bottom: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            color: #1a202c;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s ease;
            background-color: #f8fafc;
            border-left: 3px solid #0f5e8a;
        }
        .roadmap-list li i {
            margin-right: 6px;
            color: #0f5e8a;
            font-size: 12px;
        }
        .roadmap-list li.completed {
            background: #f0f9ff;
            color: #0f5e8a;
            border-left-color: #0f5e8a;
        }
        .roadmap-list li.active {
            background: #0f5e8a;
            color: white;
        }
        .roadmap-list li.pending {
            background: #f8fafc;
            color: #6b7a86;
        }
        .roadmap details {
            margin-bottom: 8px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e7eef6;
        }
        .roadmap details summary {
            padding: 10px 12px;
            border-radius: 6px;
            background: #f8fafc;
            cursor: pointer;
            font-weight: 600;
            color: #1a202c;
            font-size: 13px;
            transition: background 0.3s ease;
            list-style: none;
        }
        .roadmap details summary::-webkit-details-marker {
            display: none;
        }
        .roadmap details summary i {
            margin-right: 6px;
            color: #0f5e8a;
            font-size: 12px;
        }
        .roadmap details summary:hover {
            background: #e6f2fa;
            color: #0f5e8a;
        }
        .roadmap details[open] summary {
            background: #0f5e8a;
            color: white;
        }
        .roadmap details[open] summary i {
            color: white;
        }
        .roadmap details ul {
            list-style: none;
            padding: 8px 15px;
            background: white;
        }
        .roadmap details ul li {
            margin: 4px 0;
        }
        .roadmap details ul li a {
            color: #4a5568;
            text-decoration: none;
            display: block;
            padding: 6px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-size: 13px;
        }
        .roadmap details ul li a:hover {
            background: #e6f2fa;
            color: #0f5e8a;
        }
        .roadmap details ul li a i {
            margin-right: 6px;
            width: 14px;
            text-align: center;
            color: #0f5e8a;
            font-size: 12px;
        }
        .roadmap details details {
            margin: 6px 0;
            border: 1px solid #e7eef6;
            border-radius: 5px;
        }
        .roadmap details details summary {
            background-color: #f8fafc;
            font-size: 13px;
            padding: 8px 12px;
        }
        .roadmap details details ul li {
            padding-left: 15px;
        }
        .main-content {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7eef6;
        }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e7eef6;
        }
        .content-header h2 {
            color: #1a202c;
            font-weight: 600;
            font-size: 20px;
        }
        .details-card {
            padding: 15px;
            border: 1px solid #e7eef6;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            background: #fff;
        }
        .details-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 15px;
        }
        .details-card .row {
            margin-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 8px;
        }
        .details-card .row:last-child {
            border-bottom: none;
        }
        .details-card .label {
            font-weight: 600;
            color: #4a5568;
            font-size: 13px;
        }
        .details-card .value {
            color: #1a202c;
            font-size: 13px;
        }
        .back-button {
            margin-bottom: 15px;
        }
        .back-button a {
            font-size: 13px;
            color: #0f5e8a;
            text-decoration: none;
        }
        .back-button a:hover {
            text-decoration: underline;
        }
        .date-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e7eef6;
        }
        .date-section h4 {
            color: #0f5e8a;
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        /* Edit Form Styles */
        .edit-form {
            background: #f8f9fa;
            border: 1px solid #e3f2fd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .date-input-group {
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .date-input-group label {
            font-weight: 600;
            color: #495057;
            font-size: 13px;
            margin-bottom: 8px;
            display: block;
        }

        .date-input-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .date-input-group input:focus {
            border-color: #0f5e8a;
            outline: none;
            box-shadow: 0 0 0 3px rgba(15, 94, 138, 0.1);
        }

        .update-btn-container {
            margin-top: 20px;
            text-align: right;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }

        .btn-update {
            background: linear-gradient(135deg, #0f5e8a, #0d4d6f);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(15, 94, 138, 0.3);
        }

        .btn-update:hover {
            background: linear-gradient(135deg, #0d4d6f, #0b3d5a);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(15, 94, 138, 0.4);
        }

        .btn-edit {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
            margin-bottom: 15px;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
        }

        .btn-cancel {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .btn-cancel:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-1px);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            color: #0f5e8a;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .assessment-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .assessment-group h5 {
            color: #0f5e8a;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #0f5e8a;
        }

        .last-updated {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 12px 15px;
            margin-top: 20px;
        }

        .last-updated .label {
            font-weight: 700;
            color: #155724;
        }

        .last-updated .value {
            color: #155724;
            font-weight: 600;
        }

        .empty-date {
            color: #6c757d;
            font-style: italic;
        }

        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f1b0b7);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        @media (max-width: 900px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
            .roadmap {
                margin-bottom: 15px;
            }
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .update-btn-container {
                text-align: center;
            }
            .btn-cancel {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <div class="branding">
                <div class="logo">H</div>
                <div class="brand-text">
                    <h1>FSMIL Dashboard</h1>
                    <p>Applications Monitoring & Data Reporting</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="icon-button">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"></span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-name">Division Staff</div>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="dashboard-layout">
            <div class="roadmap">
                <!--h3>Application Roadmap</h3>
                <ul class="roadmap-list" style="list-style-type: none; padding-left: 0; margin: 0;">
                    <li class="completed"><i class="fas fa-check-circle"></i> 1. Received Application</li>
                    <li class="completed"><i class="fas fa-search"></i> 2. Screening</li>
                    <li class="completed"><i class="fas fa-tasks"></i> 3. 1st Assessment</li>
                    <li class="completed"><i class="fas fa-clipboard-list"></i> 4. 2nd Assessment</li>
                    <li class="completed"><i class="fas fa-users"></i> 5. Peer Review</li>
                    <li class="completed"><i class="fas fa-check-double"></i> 6. Approval</li>
                </ul-->

                <h3>Filters & Actions</h3>
                <details>
                    <summary><i class="fas fa-filter"></i> All Applications</summary>
                    <ul>
                        <li><a href="fric_page.php?stage_id=backlog"><i class="fas fa-history"></i> Backlog</a></li>
                        <li><a href="fric_page.php?stage_id=10"><i class="fas fa-box"></i> Registered Products</a></li>
                        <li><a href="fric_page.php?stage_id=14"><i class="fas fa-times-circle"></i> Rejected</a></li>
                        <li><a href="fric_page.php?stage_id=30"><i class="fas fa-calendar-times"></i> Expired</a></li>
                    </ul>
                </details>
                <details open>
                    <summary><i class="fas fa-sync-alt"></i> Under Process</summary>
                    <ul>
                        <li>
                            <details>
                                <summary><i class="fas fa-search"></i> Screening</summary>
                                <ul>
                                    <li><a href="fric_page.php?stage_id=1"><i class="fas fa-hourglass-start"></i> Pending Screening</a></li>
                                    <li><a href="fric_page.php?stage_id=2"><i class="fas fa-spinner"></i> Under Screening</a></li>
                                </ul>
                            </details>
                        </li>
                        <li>
                            <details>
                                <summary><i class="fas fa-tasks"></i> Assessment</summary>
                                <ul>
                                    <li><a href="fric_page.php?stage_id=7"><i class="fas fa-hourglass-half"></i> Pending Assessment</a></li>
                                    <li><a href="fric_page.php?stage_id=3"><i class="fas fa-clipboard-check"></i> Under 1st Assessment</a></li>
                                    <li><a href="fric_page.php?stage_id=35"><i class="fas fa-clipboard-list"></i> Pending 2nd Assessment</a></li>
                                    <li><a href="fric_page.php?stage_id=4"><i class="fas fa-tasks"></i> Under 2nd Assessment</a></li>
                                    <li><a href="fric_page.php?stage_id=36"><i class="fas fa-folder-plus"></i> Pending ADD. DATA 1st Assessment</a></li>
                                    <li><a href="fric_page.php?stage_id=21"><i class="fas fa-file-medical"></i> ADD. DATA, Under 1st Assessment</a></li>
                                    <li><a href="fric_page.php?stage_id=37"><i class="fas fa-folder-plus"></i> Pending ADD. DATA 2nd Assessment</a></li>
                                    <li><a href="fric_page.php?stage_id=22"><i class="fas fa-file-medical"></i> ADD. DATA, Under 2nd Assessment</a></li>
                                    <li><a href="fric_page.php?stage_id=38"><i class="fas fa-user-tie"></i> Manager (1st & 2nd Reports Review)</a></li>
                                </ul>
                            </details>
                        </li>
                        <li>
                            <details>
                                <summary><i class="fas fa-question-circle"></i> Queries</summary>
                                <ul>
                                    <li><a href="fric_page.php?stage_id=8"><i class="fas fa-envelope"></i> Query Letters to be Sent</a></li>
                                    <li><a href="fric_page.php?stage_id=25"><i class="fas fa-reply"></i> Awaiting Applicant's Feedback</a></li>
                                </ul>
                            </details>
                        </li>
                        <li>
                            <details>
                                <summary><i class="fas fa-users"></i> Peer Review</summary>
                                <ul>
                                    <li><a href="fric_page.php?stage_id=19"><i class="fas fa-industry"></i> Pending GMP</a></li>
                                    <li><a href="fric_page.php?stage_id=5"><i class="fas fa-user-check"></i> Pending Peer Review</a></li>
                                    <li><a href="fric_page.php?stage_id=6"><i class="fas fa-check-double"></i> Passed Peer Review</a></li>
                                </ul>
                            </details>
                        </li>
                    </ul>
                </details>
            </div>

            <div class="main-content">
                <div class="content-header">
                    <h2>Application Details</h2>
                </div>

                <!-- Back button preserves stage_id (defaults to 'all'), by, page, and limit -->
                <div class="back-button">
                    <a href="<?php echo htmlspecialchars($back_url); ?>"><i class="fas fa-arrow-left"></i> Back to Applications</a>
                </div>

                <div class="details-card">
                    <h3><?php echo htmlspecialchars($application->applicant_name); ?></h3>
                    <div class="row">
                        <div class="col-md-3 label">Application ID:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->application_id); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Reference No.:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->reference_no); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Applicant Name:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->applicant_name); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Premise Category:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->premise_category); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Product Category:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->product_category); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Product Type:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->product_type); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Applicant TIN no.:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->applicant_TIN_no ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Applicant Telephone:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->applicant_telephone ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Applicant Email:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->applicant_email ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Country:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->country ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Province:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->province ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">District:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->district ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Sector:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->sector ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Cell:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->cell ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Village:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->village ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Managing Director:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->managing_director ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Responsible Technician:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->responsible_technician ?? 'N/A'); ?></div>
                    </div>
                        <div class="row">
                        <div class="col-md-3 label">Responsible Technician Telephone:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->responsible_technician_telephone ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Application Date:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->date_submitted); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Current Stage:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($stage_name); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Process Timeline:</div>
                        <div class="col-md-9 value"><?php echo $days_processing_monitoring; ?></div>
                    </div>
                                        <!-- Screening and Assessment Dates Section -->
                    <div class="date-section">
                        <div class="section-header">
                            <h4 class="section-title">
                                <i class="fas fa-calendar-alt"></i> Assessment Dates
                            </h4>
                            <?php if ($user_access == 100): ?>
                                <button type="button" class="btn-edit" onclick="toggleEditMode()">
                                    <i class="fas fa-edit"></i> Edit Dates
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" id="datesForm">
                            <input type="hidden" name="update_dates" value="1">
                            
                            <!-- Static Display -->
                            <div id="staticDates">

                                
                                <!-- Assessment 1 -->
           <!-- Assessment 1 -->
<div class="assessment-group">
    <h5><i class="fas fa-tasks"></i> Assessment 1</h5>

    <!-- Assessment Date -->
    <div class="row">
        <div class="col-md-3 label">Assessment:</div>
        <div class="col-md-9 value">
            <?php echo formatDate($application->date_assessment1) ?: '<span class="empty-date">Not set</span>'; ?>
        </div>
    </div>

    <!-- Query Assessment Date -->
    <div class="row">
        <div class="col-md-3 label">Query Assessment:</div>
        <div class="col-md-9 value">
            <?php echo formatDate($application->date_query_assessment1) ?: '<span class="empty-date">Not set</span>'; ?>
        </div>
    </div>

    <!-- Query Response Date -->
    <div class="row">
        <div class="col-md-3 label">Query Response:</div>
        <div class="col-md-9 value">
            <?php echo formatDate($application->date_response1) ?: '<span class="empty-date">Not set</span>'; ?>
        </div>
    </div>
</div>
                                
                                <!-- Assessment 2 -->
                                <div class="assessment-group">
                                    <h5><i class="fas fa-clipboard-list"></i> Assessment 2</h5>
                                    <div class="row">
                                        <div class="col-md-3 label">First Assessment:</div>
                                        <div class="col-md-9 value"><?php echo formatDate($application->date_assessment2) ?: '<span class="empty-date">Not set</span>'; ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 label">Second Assessment:</div>
                                        <div class="col-md-9 value"><?php echo formatDate($application->date_second_assessment2) ?: '<span class="empty-date">Not set</span>'; ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 label">Query Assessment:</div>
                                        <div class="col-md-9 value"><?php echo formatDate($application->date_query_assessment2) ?: '<span class="empty-date">Not set</span>'; ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 label">Response:</div>
                                        <div class="col-md-9 value"><?php echo formatDate($application->date_response2) ?: '<span class="empty-date">Not set</span>'; ?></div>
                                    </div>
                                </div>
                                
                                <!-- Assessment 3 -->
                                <div class="assessment-group">
                                    <h5><i class="fas fa-file-medical"></i> Assessment 3</h5>
                                    <div class="row">
                                        <div class="col-md-3 label">First Assessment:</div>
                                        <div class="col-md-9 value"><?php echo formatDate($application->date_first_assessment3) ?: '<span class="empty-date">Not set</span>'; ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 label">Second Assessment:</div>
                                        <div class="col-md-9 value"><?php echo formatDate($application->date_second_assessment3) ?: '<span class="empty-date">Not set</span>'; ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Edit Form (only visible when editing) -->
                            <?php if ($user_access == 100): ?>
                            <div id="editDates" class="edit-form">
                                <div class="date-input-group">
                                    <label for="date_screening"><i class="fas fa-search"></i> Date Screening:</label>
                                    <input type="date" id="date_screening" name="date_screening" value="<?php echo formatDate($application->date_screening); ?>">
                                </div>
                                
                                <div class="assessment-group">
                                    <h5><i class="fas fa-tasks"></i> Assessment 1</h5>
                                    <div class="date-input-group">
                                        <label for="date_first_assessment1">Assessment:</label>
                                        <input type="date" id="date_assessment1" name="date_fassessment1" value="<?php echo formatDate($application->date_assessment1); ?>">
                                    </div>
                                    <div class="date-input-group">
                                        <label for="date_query_assessment1">Query Assessment:</label>
                                        <input type="date" id="date_query_assessment1" name="date_query_assessment1" value="<?php echo formatDate($application->date_query_assessment1); ?>">
                                    </div>
                                    <div class="date-input-group">
                                        <label for="date_response1">Response:</label>
                                        <input type="date" id="date_response1" name="date_response1" value="<?php echo formatDate($application->date_response1); ?>">
                                    </div>
                                </div>
                                
                                <div class="assessment-group">
                                    <h5><i class="fas fa-clipboard-list"></i> Assessment 2</h5>
                                    <div class="date-input-group">
                                        <label for="date_first_assessment2">First Assessment:</label>
                                        <input type="date" id="date_first_assessment2" name="date_first_assessment2" value="<?php echo formatDate($application->date_first_assessment2); ?>">
                                    </div>
                                    <div class="date-input-group">
                                        <label for="date_second_assessment2">Second Assessment:</label>
                                        <input type="date" id="date_second_assessment2" name="date_second_assessment2" value="<?php echo formatDate($application->date_second_assessment2); ?>">
                                    </div>
                                    <div class="date-input-group">
                                        <label for="date_query_assessment2">Query Assessment:</label>
                                        <input type="date" id="date_query_assessment2" name="date_query_assessment2" value="<?php echo formatDate($application->date_query_assessment2); ?>">
                                    </div>
                                    <div class="date-input-group">
                                        <label for="date_response2">Response:</label>
                                        <input type="date" id="date_response2" name="date_response2" value="<?php echo formatDate($application->date_response2); ?>">
                                    </div>
                                </div>
                                
                                <div class="assessment-group">
                                    <h5><i class="fas fa-file-medical"></i> Assessment 3</h5>
                                    <div class="date-input-group">
                                        <label for="date_first_assessment3">First Assessment:</label>
                                        <input type="date" id="date_first_assessment3" name="date_first_assessment3" value="<?php echo formatDate($application->date_first_assessment3); ?>">
                                    </div>
                                    <div class="date-input-group">
                                        <label for="date_second_assessment3">Second Assessment:</label>
                                        <input type="date" id="date_second_assessment3" name="date_second_assessment3" value="<?php echo formatDate($application->date_second_assessment3); ?>">
                                    </div>
                                </div>
                                
                                <div class="update-btn-container">
                                    <button type="button" class="btn-update" onclick="submitForm()">
                                        <i class="fas fa-save"></i> Update Dates
                                    </button>
                                    <button type="button" class="btn-cancel" onclick="toggleEditMode()">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Last Updated By - Show for ALL users -->
                            <div class="last-updated">
                                <div class="row">
                                    <div class="col-md-3 label"><i class="fas fa-user-edit"></i> Last Updated By:</div>
                                    <div class="col-md-9 value"><?php echo htmlspecialchars($application->updated_by_email ?? 'N/A'); ?></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let editMode = false;

        function toggleEditMode() {
            editMode = !editMode;
            const staticDates = document.getElementById('staticDates');
            const editDates = document.getElementById('editDates');
            const editBtn = document.querySelector('.btn-edit');
            
            if (editMode) {
                staticDates.style.display = 'none';
                editDates.style.display = 'block';
                editBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Editing';
                editBtn.style.background = 'linear-gradient(135deg, #6c757d, #5a6268)';
            } else {
                staticDates.style.display = 'block';
                editDates.style.display = 'none';
                editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit Dates';
                editBtn.style.background = 'linear-gradient(135deg, #28a745, #218838)';
            }
        }

        function submitForm() {
            if (confirm('Are you sure you want to update these dates? This action will be logged in the audit trail.')) {
                document.getElementById('datesForm').submit();
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('HMDR Details: Page loaded successfully');
            // Hide edit form by default
            const editDates = document.getElementById('editDates');
            if (editDates) {
                editDates.style.display = 'none';
            }
        });
    </script>
</body>