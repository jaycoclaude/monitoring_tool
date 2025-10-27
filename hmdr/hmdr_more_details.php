<?php
session_start();
require_once '../includes/config.php'; // PDO connection assumed

// Check login session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_access'])) {
    echo '<div class="alert alert-danger"><p>Not allowed to access. Please login. <a href="../index.php">Click here</a></p></div>';
    exit();
}

$datetoday = date("Y-m-d");
$user_id = $_SESSION['user_id'];
$app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;
$stage_id = $_GET['stage_id'] ?? 'all'; // Default to 'all' if stage_id is not provided
$by = $_GET['by'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

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
    error_log('Error in hmdr_more_details: ' . $e->getMessage());
    echo '<div class="alert alert-danger"><p>An error occurred. Please contact the administrator.</p></div>';
    exit();
}

// Fetch application details
try {
    $stmt = $pdo->prepare("SELECT * FROM tbl_hm_applications WHERE hm_application_id = :app_id LIMIT 1");
    $stmt->execute(['app_id' => $app_id]);
    $application = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$application) {
        echo '<div class="alert alert-danger"><p>Application not found.</p></div>';
        exit();
    }
} catch (Exception $e) {
    error_log('Error fetching application: ' . $e->getMessage());
    echo '<div class="alert alert-danger"><p>Error fetching application details. Please contact the administrator.</p></div>';
    exit();
}

// Fetch timeline information
$days_processing_monitoring = 'N/A';
try {
    $stmt = $pdo->prepare("SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway");
    $stmt->execute([
        ':status_id' => $application->application_current_stage,
        ':assessment_pathway' => $application->assessment_procedure
    ]);
    $timelines = $stmt->fetchAll(PDO::FETCH_OBJ);

    foreach ($timelines as $timeline) {
        $number_of_days = intval($timeline->number_of_days);
        $days_processing = (strtotime($datetoday) - strtotime($application->date_submitted)) / 86400;

        if ($days_processing > $number_of_days) {
            $days_processing_monitoring = "<strong><font color='blue'>($number_of_days)</font><br><font color='red'>" . number_format($days_processing - $number_of_days) . " days<br>Delay</font></strong>";
        } else {
            $days_processing_monitoring = "<strong><font color='blue'>" . number_format($number_of_days - $days_processing) . " days<br>On time</font></strong>";
        }
    }
} catch (Exception $e) {
    error_log('Error fetching timelines: ' . $e->getMessage());
    $days_processing_monitoring = "<strong><font color='red'>Error</font></strong>";
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

$stage_name = getStageName($application->application_current_stage);

// Build back URL, ensuring stage_id is included (defaults to 'all' if not set)
$back_url = 'hmdr_page.php?' . http_build_query(array_filter([
    'stage_id' => $stage_id, // Ensures stage_id is passed to maintain filter context
    'by' => $by,
    'page' => $page,
    'limit' => $limit
]));
?>

<head>
    <title>MA - Application Details - <?php echo htmlspecialchars($application->brand_name); ?></title>
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
        @media (max-width: 900px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
            .roadmap {
                margin-bottom: 15px;
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
                <h3>Application Roadmap</h3>
                <ul class="roadmap-list" style="list-style-type: none; padding-left: 0; margin: 0;">
                    <li class="completed"><i class="fas fa-check-circle"></i> 1. Received Application</li>
                    <li class="completed"><i class="fas fa-search"></i> 2. Screening</li>
                    <li class="completed"><i class="fas fa-tasks"></i> 3. 1st Assessment</li>
                    <li class="completed"><i class="fas fa-clipboard-list"></i> 4. 2nd Assessment</li>
                    <li class="completed"><i class="fas fa-users"></i> 5. Peer Review</li>
                    <li class="completed"><i class="fas fa-check-double"></i> 6. Approval</li>
                </ul>

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
                    <h2>Application Details</h2>
                </div>

                <!-- Back button preserves stage_id (defaults to 'all'), by, page, and limit -->
                <div class="back-button">
                    <a href="<?php echo htmlspecialchars($back_url); ?>"><i class="fas fa-arrow-left"></i> Back to Applications</a>
                </div>

                <div class="details-card">
                    <h3><?php echo htmlspecialchars($application->brand_name); ?></h3>
                    <div class="row">
                        <div class="col-md-3 label">Application ID:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->hm_application_id); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Reference No.:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->reference_no); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Brand Name:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->brand_name); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Generic Name:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->hm_generic_name); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Manufacturer:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->hm_manufacturer_name); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Assessment Pathway:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->assessment_procedure); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Application Process:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->application_process ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Tracking Number:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->tracking_no ?? 'N/A'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 label">Classification:</div>
                        <div class="col-md-9 value"><?php echo htmlspecialchars($application->classification ?? 'N/A'); ?></div>
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
                </div>
            </div>
        </div>
    </div>

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