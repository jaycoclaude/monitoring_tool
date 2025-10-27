<?php
session_start();
require_once '../includes/config.php'; // PDO connection assumed

// Check login session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_access'])) {
    echo '<div class="alert alert-danger"><p>Not allowed to access. Please login.</p></div>';
    exit();
}

$datetoday = date("Y-m-d");
$stage_id = $_GET['stage_id'] ?? '';
$user_id = $_SESSION['user_id'];
$access = $_SESSION['user_access'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(10, min(100, (int)$_GET['limit'])) : 10; // Rows per page: 10-100
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : ''; // Get search term
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // Get filter (all, ontime, tobedelayed, delayed)

// Validate user
try {
    $stmt = $pdo->prepare("SELECT user_id, user_status FROM tbl_hm_users WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$user || $user->user_status != 1) {
        session_destroy();
        echo '<div class="alert alert-danger"><p>Not allowed to access. Please login.</p></div>';
        exit();
    }
} catch (Exception $e) {
    error_log('Error in user validation: ' . $e->getMessage());
    echo '<div class="alert alert-danger"><p>An error occurred during user validation. Please contact the administrator.</p></div>';
    exit();
}

// Initialize counters
$total_not_assigned_ontime = 0;
$total_not_assigned_delayed = 0;
$total_not_assigned_tobedelayed = 0;
$count_applications = 0;

// Build base query for total count and data fetch (includes search for table)
$base_query = "FROM tbl_hm_applications a LEFT JOIN tbl_timelines t ON a.application_current_stage = t.status_id AND a.assessment_procedure = t.assessment_pathway";
$params = [];
$stage_name = '';

if ($stage_id === 'all') {
    $stage_name = 'All Applications';
} elseif ($stage_id === 'under_process') {
    $base_query .= " WHERE a.application_current_stage IN (1,2,3,4,5,6,7,8,19,21,22,25,35,36,37,38)";
    $stage_name = 'Applications Under Process';
} elseif ($stage_id === 'backlog') {
    $base_query .= " WHERE a.application_current_stage NOT IN (10, 14, 16, 23, 28, 30) 
                    AND a.date_submitted IS NOT NULL 
                    AND a.date_submitted != '0000-00-00' 
                    AND STR_TO_DATE(a.date_submitted, '%Y-%m-%d') IS NOT NULL 
                    AND (
        (a.assessment_procedure = 'FULL ASSESSMENT' AND (
            CASE 
                WHEN a.date_query_assessment1 IS NOT NULL 
                     AND a.date_query_assessment1 != '0000-00-00'
                     AND STR_TO_DATE(a.date_query_assessment1, '%Y-%m-%d') IS NOT NULL
                THEN DATEDIFF(a.date_query_assessment1, a.date_submitted) + 
                     IFNULL(DATEDIFF(
                         NULLIF(a.date_query_assessment2, '0000-00-00'), 
                         NULLIF(a.date_response1, '0000-00-00')
                     ), 0) + 
                     IFNULL(DATEDIFF(
                         NULLIF(a.date_query_assessment3, '0000-00-00'), 
                         NULLIF(a.date_response2, '0000-00-00')
                     ), 0) + 
                     IFNULL(DATEDIFF(
                         CURDATE(), 
                         NULLIF(a.date_response3, '0000-00-00')
                     ), 0)
                ELSE DATEDIFF(CURDATE(), a.date_submitted)
            END > 365
        )) OR 
        (a.assessment_procedure IN ('ABRIDGED', 'RECOGNITION') AND (
            CASE 
                WHEN a.date_query_assessment1 IS NOT NULL 
                     AND a.date_query_assessment1 != '0000-00-00'
                     AND STR_TO_DATE(a.date_query_assessment1, '%Y-%m-%d') IS NOT NULL
                THEN DATEDIFF(a.date_query_assessment1, a.date_submitted) + 
                     IFNULL(DATEDIFF(
                         NULLIF(a.date_query_assessment2, '0000-00-00'), 
                         NULLIF(a.date_response1, '0000-00-00')
                     ), 0) + 
                     IFNULL(DATEDIFF(
                         NULLIF(a.date_query_assessment3, '0000-00-00'), 
                         NULLIF(a.date_response2, '0000-00-00')
                     ), 0) + 
                     IFNULL(DATEDIFF(
                         CURDATE(), 
                         NULLIF(a.date_response3, '0000-00-00')
                     ), 0)
                ELSE DATEDIFF(CURDATE(), a.date_submitted)
            END > 90
        ))
    )";
    $stage_name = 'Backlog Applications';
} elseif ($stage_id === 'renewal') {
    $base_query .= " WHERE a.application_process = 'Renewal'";
    $stage_name = 'Renewal Applications';
} elseif ($stage_id === 'variation') {
    $base_query .= " WHERE a.application_process = 'Variation'";
    $stage_name = 'Variation Applications';
} else {
    $base_query .= " WHERE a.application_current_stage = :stage_id";
    $params['stage_id'] = $stage_id;
    $stage_name = getStageName($stage_id);
}

if (isset($_GET['by'])) {
    $letter = $_GET['by'];
    if ($letter !== 'All') {
        $base_query .= ($stage_id ? " AND" : " WHERE") . " a.brand_name LIKE :letter";
        $params['letter'] = $letter . '%';
    }
}

// Add search term to base query for table and count
if ($search) {
    $base_query .= ($stage_id || isset($_GET['by']) ? " AND" : " WHERE") . " (a.brand_name LIKE :search OR a.reference_no LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

// Add filter condition to base query
if ($filter !== 'all') {
    $base_query .= ($stage_id || isset($_GET['by']) || $search ? " AND" : " WHERE");
    if ($filter === 'ontime') {
        $base_query .= " (t.number_of_days IS NULL OR DATEDIFF(CURDATE(), a.date_submitted) < ROUND(t.number_of_days / 2))";
    } elseif ($filter === 'tobedelayed') {
        $base_query .= " (t.number_of_days IS NOT NULL AND DATEDIFF(CURDATE(), a.date_submitted) >= ROUND(t.number_of_days / 2) AND DATEDIFF(CURDATE(), a.date_submitted) <= t.number_of_days)";
    } elseif ($filter === 'delayed') {
        $base_query .= " (t.number_of_days IS NOT NULL AND DATEDIFF(CURDATE(), a.date_submitted) > t.number_of_days)";
    }
}

// Fetch total rows for pagination (includes search and filter)
$count_query = "SELECT COUNT(*) AS total " . $base_query;
try {
    $stmt_count = $pdo->prepare($count_query);
    if (isset($params['stage_id'])) $stmt_count->bindParam(':stage_id', $params['stage_id']);
    if (isset($params['letter'])) $stmt_count->bindParam(':letter', $params['letter']);
    if (isset($params['search'])) $stmt_count->bindParam(':search', $params['search']);
    $stmt_count->execute();
    $total_rows = $stmt_count->fetch(PDO::FETCH_OBJ)->total;
    $total_pages = max(1, ceil($total_rows / $limit));
} catch (Exception $e) {
    error_log('Error counting applications: ' . $e->getMessage() . " | Query: $count_query");
    $total_rows = 0;
    $total_pages = 1;
}

// Fetch all applications for timeline calculations (no pagination, no search, no filter for counts)
$timeline_base_query = "FROM tbl_hm_applications";
$timeline_params = [];
if ($stage_id === 'all') {
    // No additional WHERE clause
} elseif ($stage_id === 'under_process') {
    $timeline_base_query .= " WHERE application_current_stage IN (1,2,3,4,5,6,7,8,19,21,22,25,35,36,37,38)";
} elseif ($stage_id === 'backlog') {
    $timeline_base_query .= " WHERE application_current_stage NOT IN (10, 14, 16, 23, 28, 30) 
                            AND date_submitted IS NOT NULL 
                            AND date_submitted != '0000-00-00' 
                            AND STR_TO_DATE(date_submitted, '%Y-%m-%d') IS NOT NULL 
                            AND (
        (assessment_procedure = 'FULL ASSESSMENT' AND (
            CASE 
                WHEN date_query_assessment1 IS NOT NULL 
                     AND date_query_assessment1 != '0000-00-00'
                     AND STR_TO_DATE(date_query_assessment1, '%Y-%m-%d') IS NOT NULL
                THEN DATEDIFF(date_query_assessment1, date_submitted) + 
                     IFNULL(DATEDIFF(
                         NULLIF(date_query_assessment2, '0000-00-00'), 
                         NULLIF(date_response1, '0000-00-00')
                     ), 0) + 
                     IFNULL(DATEDIFF(
                         NULLIF(date_query_assessment3, '0000-00-00'), 
                         NULLIF(date_response2, '0000-00-00')
                     ), 0) + 
                     IFNULL(DATEDIFF(
                         CURDATE(), 
                         NULLIF(date_response3, '0000-00-00')
                     ), 0)
                ELSE DATEDIFF(CURDATE(), date_submitted)
            END > 365
        )) OR 
        (assessment_procedure IN ('ABRIDGED', 'RECOGNITION') AND (
            CASE 
                WHEN date_query_assessment1 IS NOT NULL 
                     AND date_query_assessment1 != '0000-00-00'
                     AND STR_TO_DATE(date_query_assessment1, '%Y-%m-%d') IS NOT NULL
                THEN DATEDIFF(date_query_assessment1, date_submitted) + 
                     IFNULL(DATEDIFF(
                         NULLIF(date_query_assessment2, '0000-00-00'), 
                         NULLIF(date_response1, '0000-00-00')
                     ), 0) + 
                     IFNULL(DATEDIFF(
                         NULLIF(date_query_assessment3, '0000-00-00'), 
                         NULLIF(date_response2, '0000-00-00')
                     ), 0) + 
                     IFNULL(DATEDIFF(
                         CURDATE(), 
                         NULLIF(date_response3, '0000-00-00')
                     ), 0)
                ELSE DATEDIFF(CURDATE(), date_submitted)
            END > 90
        ))
    )";
} elseif ($stage_id === 'renewal') {
    $timeline_base_query .= " WHERE application_process = 'Renewal'";
} elseif ($stage_id === 'variation') {
    $timeline_base_query .= " WHERE application_process = 'Variation'";
} else {
    $timeline_base_query .= " WHERE application_current_stage = :stage_id";
    $timeline_params['stage_id'] = $stage_id;
}

if (isset($_GET['by'])) {
    $letter = $_GET['by'];
    if ($letter !== 'All') {
        $timeline_base_query .= ($stage_id ? " AND" : " WHERE") . " brand_name LIKE :letter";
        $timeline_params['letter'] = $letter . '%';
    }
}

$timeline_query = "SELECT * " . $timeline_base_query;
try {
    $stmt_timeline = $pdo->prepare($timeline_query);
    if (isset($timeline_params['stage_id'])) $stmt_timeline->bindParam(':stage_id', $timeline_params['stage_id']);
    if (isset($timeline_params['letter'])) $stmt_timeline->bindParam(':letter', $timeline_params['letter']);
    $stmt_timeline->execute();
    $all_applications = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

    // Calculate timeline counts and store status for each application
    $application_statuses = [];
    foreach ($all_applications as $application) {
        $hm_application_id = $application->hm_application_id;
        $date_submitted = $application->date_submitted;
        $assessment_procedure = $application->assessment_procedure;
        $application_current_stage = $application->application_current_stage;

        try {
            $stmt = $pdo->prepare("SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway");
            $stmt->execute([
                ':status_id' => $application_current_stage,
                ':assessment_pathway' => $assessment_procedure
            ]);
            $timelines = $stmt->fetchAll(PDO::FETCH_OBJ);

            if ($timelines) {
                foreach ($timelines as $timeline) {
                    $number_of_days = intval($timeline->number_of_days);
                    $half_days = round($number_of_days / 2);
                    $days_processing = (strtotime($datetoday) - strtotime($date_submitted)) / 86400;

                    if ($days_processing > $number_of_days) {
                        $total_not_assigned_delayed += 1;
                        $application_statuses[$hm_application_id] = 'delayed';
                    } elseif ($days_processing < $half_days) {
                        $total_not_assigned_ontime += 1;
                        $application_statuses[$hm_application_id] = 'ontime';
                    } elseif ($days_processing >= $half_days && $days_processing <= $number_of_days) {
                        $total_not_assigned_tobedelayed += 1;
                        $application_statuses[$hm_application_id] = 'tobedelayed';
                    }
                }
            } else {
                // If no timeline data, treat as 'delayed'
                $total_not_assigned_delayed += 1;
                $application_statuses[$hm_application_id] = 'delayed';
            }
            $count_applications++;
        } catch (Exception $e) {
            error_log('Error fetching timelines for application ' . $hm_application_id . ': ' . $e->getMessage());
        }
    }
} catch (Exception $e) {
    error_log('Error fetching all applications for timeline: ' . $e->getMessage() . " | Query: $timeline_query");
    echo '<div class="alert alert-danger"><p>Error fetching applications for timeline calculation. Please contact the administrator.</p></div>';
    exit();
}

// Calculate percentages
$total_not_assigned = $count_applications;
$percentage_not_assigned_ontime = $total_not_assigned > 0 ? round(($total_not_assigned_ontime / $total_not_assigned) * 100) : 0;
$percentage_not_assigned_tobedelayed = $total_not_assigned > 0 ? round(($total_not_assigned_tobedelayed / $total_not_assigned) * 100) : 0;
$percentage_not_assigned_delayed = $total_not_assigned > 0 ? round(($total_not_assigned_delayed / $total_not_assigned) * 100) : 0;

// Fetch paginated applications (includes search and filter)
$data_query = "SELECT a.* " . $base_query . " ORDER BY a.date_submitted LIMIT :limit OFFSET :offset";
try {
    $stmt = $pdo->prepare($data_query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    if (isset($params['stage_id'])) $stmt->bindParam(':stage_id', $params['stage_id']);
    if (isset($params['letter'])) $stmt->bindParam(':letter', $params['letter']);
    if (isset($params['search'])) $stmt->bindParam(':search', $params['search']);
    $stmt->execute();
    $applications_req = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (Exception $e) {
    error_log('Error fetching applications: ' . $e->getMessage() . " | Query: $data_query | Parameters: " . json_encode($params));
    echo '<div class="alert alert-danger"><p>Error fetching applications. Please contact the administrator.</p></div>';
    exit();
}

// Function to map stage_id to stage name
function getStageName($stage_id) {
    $stage_names = [
        '1' => 'Pending Screening',
        '2' => 'Under Screening',
        '3' => 'Under 1st Assessment',
        '4' => 'Under 2nd Assessment',
        '5' => 'Pending Peer Review',
        '6' => 'Passed Peer Review',
        '7' => 'Pending Assessment',
        '8' => 'Query Letters to be Sent',
        '10' => 'Registered',
        '14' => 'Rejected',
        '19' => 'Pending GMP',
        '21' => 'ADD. DATA, Under 1st Assessment',
        '22' => 'ADD. DATA, Under 2nd Assessment',
        '25' => 'Awaiting Applicant\'s Feedback',
        '30' => 'Expired',
        '35' => 'Pending 2nd Assessment',
        '36' => 'Pending ADD. DATA 1st Assessment',
        '37' => 'Pending ADD. DATA 2nd Assessment',
        '38' => 'Manager (1st & 2nd Reports Review)'
    ];
    return $stage_names[$stage_id] ?? 'Stage ' . $stage_id;
}

// Build URL for pagination links
$base_url = 'hmdr_page.php?' . http_build_query(array_filter([
    'stage_id' => $stage_id,
    'by' => $_GET['by'] ?? null,
    'limit' => $limit,
    'search' => $search,
    'filter' => $filter
]));
?>

<head>
    <title>MA - Monitoring - <?php echo htmlspecialchars($stage_name); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
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
        .status-card {
            margin-bottom: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        .status-card .panel-heading {
            background-color: #337ab7;
            color: white;
            padding: 8px 12px;
        }
        .status-card .panel-heading h3 {
            font-size: 14px;
            margin: 0;
        }
        .status-card .panel-body {
            padding: 12px;
        }
        .well-ontime, .well-tobedelayed, .well-delayed, .well-all {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .well-ontime h4, .well-tobedelayed h4, .well-delayed h4, .well-all h4 {
            font-size: 12px;
            margin: 0;
        }
        .well-ontime {
            background-color: #73B194;
            color: white;
        }
        .well-tobedelayed {
            background-color: #ece2c2;
            color: #333;
        }
        .well-delayed {
            background-color: #D59281;
            color: white;
        }
        .well-all {
            background-color: #4a90e2;
            color: white;
        }
        .well-ontime:hover, .well-tobedelayed:hover, .well-delayed:hover, .well-all:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .well-selected {
            border: 2px solid #0f5e8a;
            transform: scale(1.05);
        }
        .search-container {
            position: relative;
            max-width: 500px;
            margin-bottom: 15px;
        }
        .search-container input {
            width: 100%;
            padding: 8px 12px 8px 34px;
            border: 1px solid #e7eef6;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-size: 13px;
            font-family: 'Nunito', sans-serif;
            transition: all 0.3s ease;
        }
        .search-container input::placeholder {
            color: #a0aec0;
            font-style: italic;
        }
        .search-container input:focus {
            outline: none;
            border-color: #0f5e8a;
            box-shadow: 0 0 8px rgba(15, 94, 138, 0.2);
            transform: scale(1.02);
        }
        .search-container i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #0f5e8a;
            font-size: 14px;
        }
        .table-responsive {
            font-size: 11px;
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #f8fafc;
        }
        .table > thead > tr > th,
        .table > tbody > tr > td {
            padding: 6px;
            vertical-align: middle;
            font-size: 15px;
        }
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pagination-container .pagination {
            margin: 0;
        }
        .pagination-container .form-inline {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pagination-container .form-inline label {
            margin: 0;
            font-size: 12px;
        }
        .pagination-container .form-control {
            width: 80px;
            font-size: 12px;
            padding: 4px;
        }
        @media (max-width: 900px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
            .roadmap {
                margin-bottom: 15px;
            }
            .search-container {
                max-width: 100%;
            }
            .pagination-container {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        .clickable-row {
            cursor: pointer;
        }
        .clickable-row:hover {
            background-color: #f2f2f2;
        }
        .filter-container {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <div class="branding">
                <div class="logo">H</div>
                <div class="brand-text">
                    <h1>HMDR Dashboard</h1>
                    <p>Hazard Monitoring & Data Reporting</p>
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
                    <div class="user-name">Safety Officer</div>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="dashboard-layout">
            <div class="roadmap">
                <h3>Filters & Actions</h3>
                <details>
                    <summary><i class="fas fa-filter"></i> All Applications</summary>
                    <ul>
                        <li><a href="hmdr_page.php?stage_id=backlog"><i class="fas fa-history"></i> Backlog</a></li>
                        <li><a href="hmdr_page.php?stage_id=10"><i class="fas fa-box"></i> Registered Products</a></li>
                        <li><a href="hmdr_page.php?stage_id=14"><i class="fas fa-times-circle"></i> Rejected</a></li>
                        <li><a href="hmdr_page.php?stage_id=30"><i class="fas fa-calendar-times"></i> Expired</a></li>
                    </ul>
                </details>
                <details open>
                    <summary><i class="fas fa-sync-alt"></i> Under Process</summary>
                    <ul>
                        <li>
                            <details>
                                <summary><i class="fas fa-search"></i> Screening</summary>
                                <ul>
                                    <li><a href="hmdr_page.php?stage_id=1"><i class="fas fa-hourglass-start"></i> Pending Screening</a></li>
                                    <li><a href="hmdr_page.php?stage_id=2"><i class="fas fa-spinner"></i> Under Screening</a></li>
                                </ul>
                            </details>
                        </li>
                        <li>
                            <details>
                                <summary><i class="fas fa-tasks"></i> Assessment</summary>
                                <ul>
                                    <li><a href="hmdr_page.php?stage_id=7"><i class="fas fa-hourglass-half"></i> Pending Assessment</a></li>
                                    <li><a href="hmdr_page.php?stage_id=3"><i class="fas fa-clipboard-check"></i> Under 1st Assessment</a></li>
                                    <li><a href="hmdr_page.php?stage_id=35"><i class="fas fa-clipboard-list"></i> Pending 2nd Assessment</a></li>
                                    <li><a href="hmdr_page.php?stage_id=4"><i class="fas fa-tasks"></i> Under 2nd Assessment</a></li>
                                    <li><a href="hmdr_page.php?stage_id=36"><i class="fas fa-folder-plus"></i> Pending ADD. DATA 1st Assessment</a></li>
                                    <li><a href="hmdr_page.php?stage_id=21"><i class="fas fa-file-medical"></i> ADD. DATA, Under 1st Assessment</a></li>
                                    <li><a href="hmdr_page.php?stage_id=37"><i class="fas fa-folder-plus"></i> Pending ADD. DATA 2nd Assessment</a></li>
                                    <li><a href="hmdr_page.php?stage_id=22"><i class="fas fa-file-medical"></i> ADD. DATA, Under 2nd Assessment</a></li>
                                    <li><a href="hmdr_page.php?stage_id=38"><i class="fas fa-user-tie"></i> Manager (1st & 2nd Reports Review)</a></li>
                                </ul>
                            </details>
                        </li>
                        <li>
                            <details>
                                <summary><i class="fas fa-question-circle"></i> Queries</summary>
                                <ul>
                                    <li><a href="hmdr_page.php?stage_id=8"><i class="fas fa-envelope"></i> Query Letters to be Sent</a></li>
                                    <li><a href="hmdr_page.php?stage_id=25"><i class="fas fa-reply"></i> Awaiting Applicant's Feedback</a></li>
                                </ul>
                            </details>
                        </li>
                        <li>
                            <details>
                                <summary><i class="fas fa-users"></i> Peer Review</summary>
                                <ul>
                                    <li><a href="hmdr_page.php?stage_id=19"><i class="fas fa-industry"></i> Pending GMP</a></li>
                                    <li><a href="hmdr_page.php?stage_id=5"><i class="fas fa-user-check"></i> Pending Peer Review</a></li>
                                    <li><a href="hmdr_page.php?stage_id=6"><i class="fas fa-check-double"></i> Passed Peer Review</a></li>
                                </ul>
                            </details>
                        </li>
                    </ul>
                </details>
            </div>

            <div class="main-content">
                <div class="content-header">
                    <h2><?php echo htmlspecialchars($stage_name); ?></h2>
                </div>

                <!-- Status Summary Card with Bar Chart -->
                <div class="panel panel-default status-card">
                    <div class="panel-heading">
                        <h3 class="panel-title">Application Status Summary (<?php echo htmlspecialchars($stage_name); ?>)</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <!-- Chart Column -->
                            <div class="col-md-8">
                                <canvas id="statusBarChart" style="height: 200px;"></canvas>
                            </div>
                            <!-- Stats Column -->
                            <div class="col-md-4">
                                <div class="well well-all <?php echo $filter === 'all' ? 'well-selected' : ''; ?>" onclick="window.location.href='<?php echo $base_url . '&filter=all'; ?>'">
                                    <h4>All: <?php echo $total_not_assigned; ?> (100%)</h4>
                                </div>
                                <div class="well well-ontime <?php echo $filter === 'ontime' ? 'well-selected' : ''; ?>" onclick="window.location.href='<?php echo $base_url . '&filter=ontime'; ?>'">
                                    <h4>On Time: <?php echo $total_not_assigned_ontime; ?> (<?php echo $percentage_not_assigned_ontime; ?>%)</h4>
                                </div>
                                <div class="well well-tobedelayed <?php echo $filter === 'tobedelayed' ? 'well-selected' : ''; ?>" onclick="window.location.href='<?php echo $base_url . '&filter=tobedelayed'; ?>'">
                                    <h4>To Be Delayed: <?php echo $total_not_assigned_tobedelayed; ?> (<?php echo $percentage_not_assigned_tobedelayed; ?>%)</h4>
                                </div>
                                <div class="well well-delayed <?php echo $filter === 'delayed' ? 'well-selected' : ''; ?>" onclick="window.location.href='<?php echo $base_url . '&filter=delayed'; ?>'">
                                    <h4>Delayed: <?php echo $total_not_assigned_delayed; ?> (<?php echo $percentage_not_assigned_delayed; ?>%)</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="search-container">
                    <form id="searchForm" action="hmdr_page.php" method="GET">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" id="searchInput" placeholder="Search by brand or reference..." value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="stage_id" value="<?php echo htmlspecialchars($stage_id); ?>">
                        <input type="hidden" name="by" value="<?php echo htmlspecialchars($_GET['by'] ?? ''); ?>">
                        <input type="hidden" name="limit" value="<?php echo htmlspecialchars($limit); ?>">
                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <button type="button" onclick="document.getElementById('searchInput').value='';document.getElementById('searchForm').submit();" style="margin-left: 500px; padding: 6px 12px; border-radius: 20px; border: 1px solid #e7eef6; background: #f8fafc; cursor: pointer;">Clear</button>
                    </form>
                </div>

                <div id="myDiv" style="width:100%; overflow: auto;">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tr>
                                <td colspan="11"><p><a href="hmdr_dashboard.php">Back to Dashboard</a></p></td>
                            </tr>
                            <tr>
                                <td><b>No.</b></td>
                                <td><b>Reference No.</b></td>
                                <td><b>Brand Name</b></td>
                                <td><b>Generic Name</b></td>
                                <td><b>Manufacturer</b></td>
                                <td><b>Assessment Pathway</b></td>
                                <td>
                                <?php 
                                if ($stage_id === 'under_process') {
                                    echo '<b>Current Stage</b>';
                                }   
                                else{
                                    echo '<b>Application Date</b>';
                                }
                                ?>
                                </td>
                                <td><b>Process Timeline</b></td>
                                
                            </tr>
                            <tbody id="myTable">
                                <?php
                                $i = ($page - 1) * $limit; // Adjust row numbering for pagination
                                foreach ($applications_req as $application_req) {
                                    $hm_application_id = $application_req->hm_application_id;
                                    $reference_no = $application_req->reference_no;
                                    $brand_name = $application_req->brand_name;
                                    $hm_generic_name = $application_req->hm_generic_name;
                                    $hm_manufacturer_name = $application_req->hm_manufacturer_name;
                                    $assessment_procedure = $application_req->assessment_procedure;
                                    $date_submitted = $application_req->date_submitted;
                                    $application_current_stage = $application_req->application_current_stage;

                                    // Fetch processing timelines
                                    try {
                                        $stmt = $pdo->prepare("SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway");
                                        $stmt->execute([
                                            ':status_id' => $application_current_stage,
                                            ':assessment_pathway' => $assessment_procedure
                                        ]);
                                        $timelines = $stmt->fetchAll(PDO::FETCH_OBJ);

                                        $days_processing_monitoring = '';
                                        if ($timelines) {
                                            foreach ($timelines as $timeline) {
                                                $number_of_days = intval($timeline->number_of_days);
                                                $half_days = round($number_of_days / 2);
                                                $days_processing = (strtotime($datetoday) - strtotime($date_submitted)) / 86400;

                                                if ($days_processing > $number_of_days) {
                                                    $days_processing_monitoring = "<strong><font color='blue'>($number_of_days)</font><br><font color='red'>" . number_format($days_processing - $number_of_days) . " days over<br>Delayed</font></strong>";
                                                } elseif ($days_processing < $half_days) {
                                                    $days_processing_monitoring = "<strong><font color='blue'>" . number_format($number_of_days - $days_processing) . " days left<br>On time</font></strong>";
                                                } elseif ($days_processing >= $half_days && $days_processing <= $number_of_days) {
                                                    $days_processing_monitoring = "<strong><font color='orange'>" . number_format($number_of_days - $days_processing) . " days left<br>To be delayed</font></strong>";
                                                }
                                            }
                                        } else {
                                            $days_processing_monitoring = "<strong><font color='red'>No timeline data<br>Delayed</font></strong>";
                                        }
                                    } catch (Exception $e) {
                                        error_log('Error fetching timelines for application ' . $hm_application_id . ': ' . $e->getMessage());
                                        $days_processing_monitoring = "<strong><font color='red'>Error</font></strong>";
                                    }

                                    // Output table row with title attribute for hover tooltip
                                    echo "<tr class='clickable-row' data-href='hmdr_more_details.php?app_id=$hm_application_id' title='Double click to open'>
        <td>" . ++$i . "</td>
        <td>$reference_no</td>
        <td>$brand_name</td>
        <td>$hm_generic_name</td>
        <td>$hm_manufacturer_name</td>
        <td>$assessment_procedure</td>
        " . 
        ($stage_id === 'under_process' 
            ? "<td>" . htmlspecialchars(getStageName($application_current_stage)) . "</td>" 
            : "<td>" . $date_submitted . "</td>") . 
        "
        <td>$days_processing_monitoring</td>
        <td>";

        // ✅ Show "Update Info" button only if access = 100
        if ($access == 100) {
    echo "<a href='update_info.php?app_id=$hm_application_id&stage_id=$stage_id' class='btn btn-primary btn-sm'>Update Info</a>";
        }

echo "</td></tr>";

                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="pagination-container">
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $page > 1 ? $base_url . '&page=' . ($page - 1) : '#'; ?>">Previous</a>
                            </li>
                            <?php
                            $max_pages_to_show = 5;
                            $start_page = max(1, $page - floor($max_pages_to_show / 2));
                            $end_page = min($total_pages, $start_page + $max_pages_to_show - 1);

                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=1">1</a></li>';
                                if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }

                            for ($p = $start_page; $p <= $end_page; $p++) {
                                echo '<li class="page-item ' . ($p == $page ? 'active' : '') . '">';
                                echo '<a class="page-link" href="' . $base_url . '&page=' . $p . '">' . $p . '</a>';
                                echo '</li>';
                            }

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $page < $total_pages ? $base_url . '&page=' . ($page + 1) : '#'; ?>">Next</a>
                            </li>
                        </ul>
                        <div class="form-inline">
                            <label>Rows per page:</label>
                            <select class="form-control" onchange="window.location.href='<?php echo "hmdr_page.php?" . http_build_query(array_filter(['stage_id' => $stage_id, 'by' => $_GET['by'] ?? null, 'page' => 1, 'limit' => '', 'search' => $search, 'filter' => $filter], 'strlen')); ?>&limit=' + this.value">
                                <?php
                                $options = [10, 25, 50, 100];
                                foreach ($options as $opt) {
                                    echo '<option value="' . $opt . '" ' . ($limit == $opt ? 'selected' : '') . '>' . $opt . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const rows = document.querySelectorAll(".clickable-row");
            rows.forEach(row => {
                row.addEventListener("dblclick", () => {
                    window.location.href = row.dataset.href;
                });
            });

            // Submit search form on keyup with debounce
            let searchTimeout;
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('searchForm').submit();
                }, 500); // 500ms debounce
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('statusBarChart').getContext('2d');
            const barColors = [
                '#73B194', // On Time (green)
                '#ece2c2', // To Be Delayed (yellow)
                '#D59281'  // Delayed (red)
            ];
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['On Time', 'To Be Delayed', 'Delayed'],
                    datasets: [{
                        label: 'Number of Applications',
                        data: [
                            <?php echo $total_not_assigned_ontime; ?>,
                            <?php echo $total_not_assigned_tobedelayed; ?>,
                            <?php echo $total_not_assigned_delayed; ?>
                        ],
                        backgroundColor: barColors,
                        borderColor: barColors.map(color => color.replace('0.7', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                callback: function(value) {
                                    if (value % 1 === 0) {
                                        return value;
                                    }
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.raw;
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const topLevelDetails = document.querySelectorAll('.roadmap > details');
            topLevelDetails.forEach(detail => {
                detail.addEventListener('toggle', () => {
                    if (detail.open) {
                        topLevelDetails.forEach(otherDetail => {
                            if (otherDetail !== detail) {
                                otherDetail.open = false;
                            }
                        });
                    }
                });
            });

            const nestedDetailsGroups = document.querySelectorAll('.roadmap details > ul > li > details');
            nestedDetailsGroups.forEach(detail => {
                detail.addEventListener('toggle', () => {
                    if (detail.open) {
                        const siblingDetails = detail.parentElement.parentElement.querySelectorAll('details');
                        siblingDetails.forEach(otherDetail => {
                            if (otherDetail !== detail) {
                                otherDetail.open = false;
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>