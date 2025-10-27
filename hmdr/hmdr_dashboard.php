<?php
$datetoday = date("Y-m-d");
$monthtoday = date("m");
$yeartoday = date("Y");
$weektoday = date("W");

// Function to check if date is valid
function isValidDate($date)
{
    return !empty($date) && $date !== "0000-00-00";
}
// Function to calculate days between two dates
function getDaysBetween($start, $end)
{
    if (isValidDate($start) && isValidDate($end)) {
        $days = (strtotime($end) - strtotime($start)) / 86400;
        return $days > 0 ? $days : 0; // prevent negative days
    }
    return 0;
}

session_start();
require_once '../includes/config.php'; // PDO connection assumed to be set up here

// Check login session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_access'])) {
    header("Location: index.php");
    exit();
}

try {
    // Fetch user data from session
    $user_id = $_SESSION['user_id'];
    $user_access = $_SESSION['user_access'];

    // Validate user exists and is active
    $stmt = $pdo->prepare("SELECT user_id, user_access, user_status FROM tbl_hm_users WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$user || $user->user_status != 1) {
        session_destroy();
        header("Location: index.php");
        exit();
    }

    // Check user access level
    if ($user_access == 2) {
      header("Location: all_application.php");
        exit();
        // URLs for access level 2
?>
        
<?php
    } elseif ($user_access == 3) {
      header("Location: all_application.php");
        exit();
        // URL for access level 3
?>
       
<?php
    }
} catch (Exception $e) {
    error_log('Error in dashboard: ' . $e->getMessage());
    echo '<div style="color:red;">An error occurred. Please contact the administrator.</div>';
}

$count_expired_products = 0;
$count_about_to_expire_products = 0;
$count_active = 0;
$count_under_renewal = 0;
$count_expired_not_renewed = 0;
$count_withdraw = 0;
$count_grace_period = 0;
$count_expired_and_withdrawn = 0;
$count_renewal_in_progress = 0;
$department_email = 'imushimiyimana@rwandafda.gov.rw';

$start_date = strtotime($datetoday);

$count_grace_period = 0;
$percentage_not_assigned_delayed01 = 0;
$percentage_not_assigned_tobedelayed01 = 0;
$percentage_not_assigned_ontime01 = 0;

$stmt = $pdo->prepare("SELECT * FROM tbl_hm_register");
$stmt->execute();
$applications_req = $stmt->fetchAll(PDO::FETCH_OBJ);
foreach ($applications_req as $application_req) {
    $days_diff = 0;
    $end_date = '';
    $grace_period_days = 0;
    $hm_application_number = $application_req->hm_application_number;
    $hm_registration_number = $application_req->hm_registration_number;
    $hm_product_brand_name = $application_req->hm_product_brand_name;
    $hm_generic_name = $application_req->hm_generic_name;
    $hm_dosage_strength = $application_req->hm_dosage_strength;
    $hm_dosage_form = $application_req->hm_dosage_form;
    $hm_pack_size = $application_req->hm_pack_size;
    $hm_packaging_type = $application_req->hm_packaging_type;
    $hm_shelf_life = $application_req->hm_shelf_life;
    $hm_manufacturer_name = $application_req->hm_manufacturer_name;
    $hm_manufacturer_address = $application_req->hm_manufacturer_address;
    $hm_manufacturer_country = $application_req->hm_manufacturer_country;
    $hm_mah = $application_req->hm_mah;
    $hm_ltr = $application_req->hm_ltr;
    $hm_registration_date = $application_req->hm_registration_date;
    $hm_expiry_date = $application_req->hm_expiry_date;
    $hm_product_status = $application_req->hm_product_status;
    $current_status = $application_req->current_status;
    $hm_mah_email = $application_req->hm_mah_email;
    $hm_ltr_email = $application_req->hm_ltr_email;

    $end_date = strtotime($hm_expiry_date);
    $days_diff = ($end_date - $start_date) / 60 / 60 / 24;
    $grace_period_days = 0;

    if ($hm_product_status == 'Registered') {
        $count_active += 1;
        if ($current_status == 'Renewal in Progress') {
            $count_under_renewal = 0;
            $total_not_assigned_delayed01 = 0;
            $total_not_assigned_ontime1 = 0;
            $total_not_assigned_tobedelayed01 = 0;

            $count_under_renewal += 1;

            $stmtTimeline = $pdo->prepare("
                SELECT * 
                FROM tbl_timelines 
                WHERE status_id = :status_id 
                  AND assessment_pathway = :assessment_pathway
                LIMIT 20
            ");
            $stmtTimeline->execute([
                ':status_id' => $application_current_stage ?? 0,
                ':assessment_pathway' => $assessment_procedure ?? ''
            ]);

            $applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

            foreach ($applications_req_chart as $application_req_chart) {
                $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
                $half_days = round($number_of_days_chart / 2);

                $days_processing_chart = 0;
                if (!empty($date_submitted) && $date_submitted != '0000-00-00') {
                    $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 86400;
                }

                if ($days_processing_chart > $number_of_days_chart) {
                    $total_not_assigned_delayed01 += 1;
                } elseif ($days_processing_chart < $half_days) {
                    $total_not_assigned_ontime1 += 1;
                } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
                    $total_not_assigned_tobedelayed01 += 1;
                }
            }

            $total_not_assigned = $count_not_assigned;

            if ($total_not_assigned > 0) {
                $raw_percentages = [
                    'delayed' => ($total_not_assigned_delayed01 / $total_not_assigned) * 100,
                    'tobedelayed' => ($total_not_assigned_tobedelayed01 / $total_not_assigned) * 100,
                    'ontime' => ($total_not_assigned_ontime1 / $total_not_assigned) * 100,
                ];

                $floored = [];
                $remainders = [];
                $total_floor = 0;
                foreach ($raw_percentages as $key => $val) {
                    $floored[$key] = floor($val);
                    $remainders[$key] = $val - $floored[$key];
                    $total_floor += $floored[$key];
                }

                $difference = 100 - $total_floor;
                arsort($remainders);
                foreach ($remainders as $key => $rem) {
                    if ($difference <= 0) break;
                    $floored[$key]++;
                    $difference--;
                }

                $percentage_not_assigned_delayed01 = $floored['delayed'];
                $percentage_not_assigned_tobedelayed01 = $floored['tobedelayed'];
                $percentage_not_assigned_ontime01 = $floored['ontime'];
            } else {
                $percentage_not_assigned_delayed01 = 0;
                $percentage_not_assigned_tobedelayed01 = 0;
                $percentage_not_assigned_ontime01 = 0;
            }
        }
    }

    if (($hm_expiry_date < $datetoday) && ($hm_product_status == 'Registered')) {
        $count_expired_products += 1;
        $grace_period_days = (strtotime($datetoday) - strtotime($hm_expiry_date)) / 60 / 60 / 24;

        if (($grace_period_days <= 90) && ($current_status != 'Renewal in Progress')) {
            $count_grace_period += 1;
        }
    } elseif (($hm_expiry_date > $datetoday && $days_diff <= 90) && $current_status != 'Renewal in Progress') {
        $count_about_to_expire_products += 1;
    }

    if ($hm_product_status == 'Expired') {
        $count_expired_not_renewed += 1;
    }
    if ($hm_product_status == 'Withdrawn') {
        $count_withdraw += 1;
    }
    if ($hm_product_status == 'Expired' || $hm_product_status == 'Withdrawn') {
        $count_expired_and_withdrawn += 1;
    }
}

include 'application.php';
include 'variations.php';
include 'renewal.php';
?>
<head>
  <title>MA - Monitoring Tool</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link
    rel="stylesheet"
    href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"
  />
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <link
    href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
    rel="stylesheet"
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
  />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7f9;
        color: #333;
        line-height: 1.6;
        padding: 0;
    }

    .header {
        position: sticky;
        top: 0;
        z-index: 30;
        backdrop-filter: blur(4px);
        background-color: rgba(255, 255, 255, 0.8);
        border-bottom: 1px solid #e7eef6;
        box-shadow: 0 1px 10px rgba(0, 0, 0, 0.04);
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 24px;
    }

    .branding {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .logo {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background-color: #0f5e8a;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 16px;
    }

    .brand-text h1 {
        font-size: 14px;
        font-weight: 600;
        color: #1a202c;
    }

    .brand-text p {
        font-size: 11px;
        color: #6b7a86;
        margin-top: -2px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .icon-button {
        position: relative;
        padding: 8px;
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
        top: 8px;
        right: 8px;
        width: 8px;
        height: 8px;
        background-color: #e53e3e;
        border-radius: 50%;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 8px;
        background-color: white;
        border: 1px solid #e8f1f8;
        padding: 6px 12px;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .user-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: #f0f6fb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0f5e8a;
    }

    .user-name {
        font-size: 12px;
        font-weight: 500;
        display: none;
    }

    @media (min-width: 640px) {
        .user-name {
            display: block;
        }
    }

    .dashboard-container {
        max-width: 1800px;
        margin: 0 auto;
        padding: 20px;
    }

    .top-nav {
        display: flex;
        flex-direction: row;
        justify-content: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .top-nav a {
        background: white;
        padding: 12px 25px;
        margin: 5px;
        border-radius: 30px;
        text-decoration: none;
        color: #2c3e50;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }

    .top-nav a i {
        margin-right: 8px;
    }

    .top-nav a:hover,
    .top-nav a.active {
        background: #0f5e8a;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
    }

    .dashboard-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 20px;
    }

    .roadmap {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        height: fit-content;
        border: 1px solid #e7eef6;
    }

    .roadmap h3 {
        text-align: center;
        margin-bottom: 20px;
        color: #1a202c;
        padding-bottom: 10px;
        border-bottom: 2px solid #e7eef6;
        font-weight: 600;
        font-size: 16px;
    }

    .roadmap-list {
        list-style: none;
        padding: 0;
        margin-bottom: 20px;
    }

    .roadmap-list li {
        margin-bottom: 10px;
        padding: 10px 15px;
        border-radius: 8px;
        color: #1a202c;
        font-weight: 500;
        transition: all 0.3s ease;
        background-color: #f8fafc;
        border-left: 4px solid #0f5e8a;
    }

    .roadmap-list li i {
        margin-right: 8px;
        color: #0f5e8a;
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
        margin-bottom: 10px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e7eef6;
    }

    .roadmap details summary {
        padding: 12px 15px;
        border-radius: 8px;
        background: #f8fafc;
        cursor: pointer;
        font-weight: 600;
        color: #1a202c;
        transition: background 0.3s ease;
        list-style: none;
    }

    .roadmap details summary::-webkit-details-marker {
        display: none;
    }

    .roadmap details summary i {
        margin-right: 8px;
        color: #0f5e8a;
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
        padding: 10px 20px;
        background: white;
    }

    .roadmap details ul li {
        margin: 5px 0;
    }

    .roadmap details ul li a {
        color: #4a5568;
        text-decoration: none;
        display: block;
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .roadmap details ul li a:hover {
        background: #e6f2fa;
        color: #0f5e8a;
    }

    .roadmap details ul li a i {
        margin-right: 8px;
        width: 16px;
        text-align: center;
        color: #0f5e8a;
    }

    .roadmap details details {
        margin: 8px 0;
        border: 1px solid #e7eef6;
        border-radius: 6px;
    }

    .roadmap details details summary {
        background-color: #f8fafc;
        font-size: 14px;
        padding: 10px 14px;
    }

    .roadmap details details ul li {
        padding-left: 20px;
    }

    .main-content {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        border: 1px solid #e7eef6;
    }

    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e7eef6;
    }

    .content-header h2 {
        color: #1a202c;
        font-weight: 600;
        font-size: 24px;
    }

    .grid-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* VERTICAL GROUP STACK */
    #section1, #section2, #section3 {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .stat-card-wrapper {
        display: flex;
        align-items: center;
        padding: 15px;
        border-radius: 12px;
        transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
        border-left: 4px solid;
        position: relative;
        z-index: 1;
    }

    .stat-card-wrapper:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }

    .stat-card-wrapper:hover .stat-label {
        text-decoration: none;
    }

    .stat-card {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        text-decoration: none;
    }

    .stat-content {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .stat-card i {
        font-size: 2rem;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .stat-label {
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
    }

    .chart-container {
        width: 100px;
        height: 100px;
        flex-shrink: 0;
        padding-top: 30px;
    }

    .status-chip {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #2c3e50;
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* CARD COLORS */
    .card-primary { background-color: #e6f0ff; border-left-color: #003087; }
    .card-primary i, .card-primary .stat-value, .card-primary .stat-label { color: #003087; }
    .card-primary:hover { background-color: #f0f5ff; border-left-color: #0047b3; }
    .card-primary .status-chip { background: #003087; }

    .card-success { background-color: #e6ffe6; border-left-color: #1e8741; }
    .card-success i, .card-success .stat-value, .card-success .stat-label { color: #1e8741; }
    .card-success:hover { background-color: #f0fff0; border-left-color: #2ca352; }
    .card-success .status-chip { background: #1e8741; }

    .card-warning { background-color: #fff5e6; border-left-color: #cc6d00; }
    .card-warning i, .card-warning .stat-value, .card-warning .stat-label { color: #cc6d00; }
    .card-warning:hover { background-color: #fffaf0; border-left-color: #e67e00; }
    .card-warning .status-chip { background: #cc6d00; }

    .card-danger { background-color: #ffe6e6; border-left-color: #b3120b; }
    .card-danger i, .card-danger .stat-value, .card-danger .stat-label { color: #b3120b; }
    .card-danger:hover { background-color: #fff0f0; border-left-color: #cc1e14; }
    .card-danger .status-chip { background: #b3120b; }

    .card-info { background-color: #e6fffa; border-left-color: #008a7a; }
    .card-info i, .card-info .stat-value, .card-info .stat-label { color: #008a7a; }
    .card-info:hover { background-color: #f0fffd; border-left-color: #00a693; }
    .card-info .status-chip { background: #008a7a; }

    .card-secondary { background-color: #f0f0f0; border-left-color: #4a4a4a; }
    .card-secondary i, .card-secondary .stat-value, .card-secondary .stat-label { color: #4a4a4a; }
    .card-secondary:hover { background-color: #f8f8f8; border-left-color: #5e5e5e; }
    .card-secondary .status-chip { background: #4a4a4a; }

    /* FIXED: VERTICAL GROUP STACK WITH PROPER MARGINS */
    .stage-group {
        margin-bottom: 2rem;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        background: white;
    }

    .stage-group:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }

    .group-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1rem 1.5rem;
        border-bottom: 2px solid #dee2e6;
    }

    .group-header h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* FIXED: PROPER SPACING FOR MULTI-ROW GROUPS */
    .group-cards {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem; /* ADDED: Space between rows */
    }

    .group-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 0;
    }

    /* GROUP HEADER COLORS */
    .overview-group .group-header { border-color: #003087; }
    .screening-group .group-header { border-color: #ffc107; }
    .assessment-group .group-header { border-color: #28a745; }
    .queries-group .group-header { border-color: #6c757d; }
    .peer-review-group .group-header { border-color: #17a2b8; }
    .outcome-group .group-header { border-color: #007bff; }
    .renewal-group .group-header { border-color: #17a2b8; }
    .variation-group .group-header { border-color: #007bff; }

    @media (max-width: 900px) {
        .dashboard-layout {
            grid-template-columns: 1fr;
        }

        .roadmap {
            margin-bottom: 20px;
        }

        .top-nav {
            flex-direction: column;
            align-items: center;
        }

        .top-nav a {
            width: 100%;
            max-width: 300px;
            text-align: center;
        }

        .group-row {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .stat-card-wrapper {
            flex-direction: column;
            align-items: flex-start;
        }

        .stat-card {
            width: 100%;
        }

        .chart-container {
            margin-top: 10px;
            align-self: center;
        }

        .status-chip {
            top: 5px;
            right: 5px;
        }

        .group-cards {
            padding: 1rem;
            gap: 0.75rem; /* Mobile: Slightly less spacing */
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
          <p>Human Medcines Monitoring Tool</p>
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
    <div class="top-nav">
      <ul
        class="nav-pills"
        style="list-style-type: none; padding-left: 0; margin: 0"
      >
        <li>
          <a href="#" onclick="divVisibility('section1');" class="active">
            <i class="fas fa-file-alt"></i> Applications
          </a>
        </li>
        <li>
          <a href="#" onclick="divVisibility('section2');">
            <i class="fas fa-sync-alt"></i> Renewals
          </a>
        </li>
        <li>
          <a href="#" onclick="divVisibility('section3');">
            <i class="fas fa-random"></i> Variations
          </a>
        </li>
      </ul>
    </div>

    <div class="dashboard-layout">
      <div class="roadmap">
        <!-- <h3>Application Roadmap</h3>
                <ul class="roadmap-list" style="list-style-type: none; padding-left: 0; margin: 0;">
                    <li class="completed"><i class="fas fa-check-circle"></i> 1. Received Application</li>
                    <li class="completed"><i class="fas fa-search"></i> 2. Screening</li>
                    <li class="completed"><i class="fas fa-tasks"></i> 3. 1st Assessment</li>
                    <li class="completed"><i class="fas fa-clipboard-list"></i> 4. 2nd Assessment</li>
                    <li class="completed"><i class="fas fa-users"></i>5. Peer Review</li>
                    <li class="completed"><i class="fas fa-check-double"></i> 6. Approval</li>
                </ul> -->

        <h3>Filters & Actions</h3>
        <details>
          <summary><i class="fas fa-filter"></i> All Applications</summary>
          <ul>
            <li>
              <a href="hmdr_page.php?stage_id=backlog"
                ><i class="fas fa-history"></i> Backlog</a
              >
            </li>
            <li>
              <a href="hmdr_page.php?stage_id=10"
                ><i class="fas fa-box"></i> Registered Products</a
              >
            </li>
            <li>
              <a href="hmdr_page.php?stage_id=14"
                ><i class="fas fa-times-circle"></i> Rejected</a
              >
            </li>
            <li>
              <a href="hmdr_page.php?stage_id=30"
                ><i class="fas fa-calendar-times"></i> Expired</a
              >
            </li>
          </ul>
        </details>

        <details open>
          <summary><i class="fas fa-sync-alt"></i> Under Process</summary>
          <ul>
            <li>
              <details>
                <summary><i class="fas fa-search"></i> Screening</summary>
                <ul>
                  <li>
                    <a href="hmdr_page.php?stage_id=1"
                      ><i class="fas fa-hourglass-start"></i> Pending
                      Screening</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=2"
                      ><i class="fas fa-spinner"></i> Under Screening</a
                    >
                  </li>
                </ul>
              </details>
            </li>
            <li>
              <details>
                <summary><i class="fas fa-tasks"></i> Assessment</summary>
                <ul>
                  <li>
                    <a href="hmdr_page.php?stage_id=7"
                      ><i class="fas fa-hourglass-half"></i> Pending
                      Assessment</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=3"
                      ><i class="fas fa-clipboard-check"></i> Under 1st
                      Assessment</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=35"
                      ><i class="fas fa-clipboard-list"></i> Pending 2nd
                      Assessment</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=4"
                      ><i class="fas fa-tasks"></i> Under 2nd Assessment</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=36"
                      ><i class="fas fa-folder-plus"></i> Pending ADD. DATA 1st
                      Assessment</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=21"
                      ><i class="fas fa-file-medical"></i> ADD. DATA, Under 1st
                      Assessment</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=37"
                      ><i class="fas fa-folder-plus"></i> Pending ADD. DATA 2nd
                      Assessment</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=22"
                      ><i class="fas fa-file-medical"></i> ADD. DATA, Under 2nd
                      Assessment</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=38"
                      ><i class="fas fa-user-tie"></i> Manager (1st & 2nd
                      Reports Review)</a
                    >
                  </li>
                </ul>
              </details>
            </li>
            <li>
              <details>
                <summary>
                  <i class="fas fa-question-circle"></i> Queries
                </summary>
                <ul>
                  <li>
                    <a href="hmdr_page.php?stage_id=8"
                      ><i class="fas fa-envelope"></i> Query Letters to be
                      Sent</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=25"
                      ><i class="fas fa-reply"></i> Awaiting Applicant's
                      Feedback</a
                    >
                  </li>
                </ul>
              </details>
            </li>
            <li>
              <details>
                <summary><i class="fas fa-users"></i> Peer Review</summary>
                <ul>
                  <li>
                    <a href="hmdr_page.php?stage_id=19"
                      ><i class="fas fa-industry"></i> Pending GMP</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=5"
                      ><i class="fas fa-user-check"></i> Pending Peer Review</a
                    >
                  </li>
                  <li>
                    <a href="hmdr_page.php?stage_id=6"
                      ><i class="fas fa-check-double"></i> Passed Peer Review</a
                    >
                  </li>
                </ul>
              </details>
            </li>
          </ul>
        </details>
      </div>

<div class="main-content">
    <div class="content-header">
        <h2>Applications Dashboard</h2>
    </div>

    <div class="grid-container">
        <div id="section1">
            
            <!-- OVERVIEW GROUP (Row 1) - ALL PRIMARY -->
            <div class="stage-group overview-group">
                <div class="group-header">
                    <h4><i class="fas fa-chart-line"></i> Overview</h4>
                </div>
                <div class="group-cards">
                    <div class="group-row">
                        <div class="stat-card-wrapper card-primary">
                            <a href="hmdr_page.php?stage_id=all" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-list-check"></i>
                                    <div class="stat-value"><?php echo number_format($count_all_applications); ?></div>
                                    <div class="stat-label">All Applications</div>
                                </div>
                            </a>
                            <div class="chart-container">
                               
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-primary">
                            <a href="hmdr_page.php?stage_id=under_process" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-cogs"></i>
                                    <div class="stat-value"><?php echo number_format($count_all_applications_under_process); ?></div>
                                    <div class="stat-label">Applications Under Process</div>
                                </div>
                            </a>
                            <div class="chart-container">
                                
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-primary">
                            <a href="hmdr_page.php?stage_id=backlog" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div class="stat-value"><?php echo number_format($count_backlog); ?></div>
                                    <div class="stat-label">Backlogs</div>
                                </div>
                            </a>
                            <div class="chart-container">
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SCREENING GROUP (Row 2) - ALL WARNING -->
            <div class="stage-group screening-group">
                <div class="group-header">
                    <h4><i class="fas fa-search"></i> Screening</h4>
                </div>
                <div class="group-cards">
                    <div class="group-row">
                        <div class="stat-card-wrapper card-warning">
                            <a href="hmdr_page.php?stage_id=1" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-hourglass-start"></i>
                                    <div class="stat-value"><?php echo number_format($count_not_assigned); ?></div>
                                    <div class="stat-label">Pending Screening</div>
                                </div>
                                <div class="status-chip">Step 1</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-pending-screening"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-warning">
                            <a href="hmdr_page.php?stage_id=2" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-spinner"></i>
                                    <div class="stat-value"><?php echo number_format($count_screening); ?></div>
                                    <div class="stat-label">Under Screening</div>
                                </div>
                                <div class="status-chip">Step 2</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-under-screening"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ASSESSMENT GROUP (Row 3) - ALL SUCCESS -->
            <div class="stage-group assessment-group">
                <div class="group-header">
                    <h4><i class="fas fa-tasks"></i> Assessment</h4>
                </div>
                <div class="group-cards">
                    <div class="group-row">
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=7" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-hourglass-half"></i>
                                    <div class="stat-value"><?php echo number_format($count_not_assessed); ?></div>
                                    <div class="stat-label">Pending Assessment</div>
                                </div>
                                <div class="status-chip">Step 3</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-pending-assessment"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=3" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-clipboard-check"></i>
                                    <div class="stat-value"><?php echo number_format($count_pending_first_assessment); ?></div>
                                    <div class="stat-label">Under 1st Assessment</div>
                                </div>
                                <div class="status-chip">Step 4</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-under-1st-assessment"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=35" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-clipboard-list"></i>
                                    <div class="stat-value"><?php echo number_format($count_pending_second_assessment_pending); ?></div>
                                    <div class="stat-label">Pending 2nd Assessment</div>
                                </div>
                                <div class="status-chip">Step 5</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-pending-2nd-assessment"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="group-row">
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=4" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-tasks"></i>
                                    <div class="stat-value"><?php echo number_format($count_assessment); ?></div>
                                    <div class="stat-label">Under 2nd Assessment</div>
                                </div>
                                <div class="status-chip">Step 6</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-under-2nd-assessment"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=36" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-folder-plus"></i>
                                    <div class="stat-value"><?php echo number_format($count_pending_first_assessment_pending_add_data); ?></div>
                                    <div class="stat-label">Pending ADD. DATA 1st Assessment</div>
                                </div>
                                <div class="status-chip">Step 9</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-pending-add-data-1st"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=21" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-file-medical"></i>
                                    <div class="stat-value"><?php echo number_format($count_pending_first_assessment_add_data); ?></div>
                                    <div class="stat-label">ADD. DATA, Under 1st Assessment</div>
                                </div>
                                <div class="status-chip">Step 11</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-add-data-under-1st"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="group-row">
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=37" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-folder-plus"></i>
                                    <div class="stat-value"><?php echo number_format($count_pending_second_assessment_pending_add_data); ?></div>
                                    <div class="stat-label">Pending ADD. DATA 2nd Assessment</div>
                                </div>
                                <div class="status-chip">Step 12</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-pending-add-data-2nd"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=22" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-file-medical"></i>
                                    <div class="stat-value"><?php echo number_format($count_pending_second_assessment_add_data); ?></div>
                                    <div class="stat-label">ADD. DATA, Under 2nd Assessment</div>
                                </div>
                                <div class="status-chip">Step 13</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-add-data-under-2nd"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=38" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-user-tie"></i>
                                    <div class="stat-value"><?php echo number_format($count_manager_report_review); ?></div>
                                    <div class="stat-label">Manager (1st & 2nd Reports Review)</div>
                                </div>
                                <div class="status-chip">Step 10</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-manager-review"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUERIES GROUP (Row 4) - ALL SECONDARY -->
            <div class="stage-group queries-group">
                <div class="group-header">
                    <h4><i class="fas fa-question-circle"></i> Queries</h4>
                </div>
                <div class="group-cards">
                    <div class="group-row">
                        <div class="stat-card-wrapper card-secondary">
                            <a href="hmdr_page.php?stage_id=8" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-envelope"></i>
                                    <div class="stat-value"><?php echo number_format($count_second_assessment_completed_letter_not_sent); ?></div>
                                    <div class="stat-label">Query Letters to be Sent</div>
                                </div>
                                <div class="status-chip">Step 7</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-query-letters"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-secondary">
                            <a href="hmdr_page.php?stage_id=25" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-reply"></i>
                                    <div class="stat-value"><?php echo number_format($count_awaiting_applicant_feedback); ?></div>
                                    <div class="stat-label">Awaiting Applicant's Feedback</div>
                                </div>
                                <div class="status-chip">Step 8</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-awaiting-feedback"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PEER REVIEW GROUP (Row 5) - ALL INFO -->
            <div class="stage-group peer-review-group">
                <div class="group-header">
                    <h4><i class="fas fa-users"></i> Peer Review</h4>
                </div>
                <div class="group-cards">
                    <div class="group-row">
                        <div class="stat-card-wrapper card-warning">
                            <a href="hmdr_page.php?stage_id=19" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-industry"></i>
                                    <div class="stat-value"><?php echo number_format($count_pending_gmp); ?></div>
                                    <div class="stat-label">Pending GMP</div>
                                </div>
                                <div class="status-chip">Step 14</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-pending-gmp"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-warning">
                            <a href="hmdr_page.php?stage_id=5" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-user-check"></i>
                                    <div class="stat-value"><?php echo number_format($count_peer_review); ?></div>
                                    <div class="stat-label">Pending Peer Review</div>
                                </div>
                                <div class="status-chip">Step 15</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-pending-peer-review"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-warning">
                            <a href="hmdr_page.php?stage_id=6" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-check-double"></i>
                                    <div class="stat-value"><?php echo number_format($count_under_approval); ?></div>
                                    <div class="stat-label">Passed Peer Review</div>
                                </div>
                                <div class="status-chip">Step 16</div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-passed-peer-review"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FINAL OUTCOMES GROUP (Row 6) - ALL SUCCESS -->
            <div class="stage-group outcome-group">
                <div class="group-header">
                    <h4><i class="fas fa-flag-checkered"></i> Final Outcomes</h4>
                </div>
                <div class="group-cards">
                    <div class="group-row">
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=10" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-check-circle"></i>
                                    <div class="stat-value"><?php echo number_format($count_registered); ?></div>
                                    <div class="stat-label">Registered</div>
                                </div>
                            </a>
                            <div class="chart-container">
                                
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=14" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-ban"></i>
                                    <div class="stat-value"><?php echo number_format($count_rejected); ?></div>
                                    <div class="stat-label">Rejected</div>
                                </div>
                            </a>
                            <div class="chart-container">
                               
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-success">
                            <a href="hmdr_page.php?stage_id=30" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-calendar-times"></i>
                                    <div class="stat-value"><?php echo number_format($count_expired_applications); ?></div>
                                    <div class="stat-label">Expired</div>
                                </div>
                            </a>
                            <div class="chart-container">
                               
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- RENEWAL SECTION -->
    <div class="grid-container">
        <div id="section2" style="display: none;">
            <div class="stage-group renewal-group">
                <div class="group-header">
                    <h4><i class="fas fa-sync-alt"></i> Renewal Applications</h4>
                </div>
                <div class="group-cards">
                    <div class="group-row">
                        <div class="stat-card-wrapper card-info">
                            <a href="hmdr_page.php?stage_id=10" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-check-circle"></i>
                                    <div class="stat-value"><?php echo number_format($count_active); ?></div>
                                    <div class="stat-label">Registered</div>
                                </div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-renewals-registered"></canvas>
                            </div>
                        </div>
                        <div class="stat-card-wrapper card-info">
                            <a href="hmdr_page.php?stage_id=renewal" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-sync-alt"></i>
                                    <div class="stat-value"><?php echo number_format($count_all_applications_renewal); ?></div>
                                    <div class="stat-label">Renewal Applications</div>
                                </div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-renewal-applications"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VARIATION SECTION -->
    <div class="grid-container">
        <div id="section3" style="display: none;">
            <div class="stage-group variation-group">
                <div class="group-header">
                    <h4><i class="fas fa-edit"></i> Variation Applications</h4>
                </div>
                <div class="group-cards">
                    <div class="group-row">
                        <div class="stat-card-wrapper card-primary">
                            <a href="hmdr_page.php?stage_id=variation" class="stat-card">
                                <div class="stat-content">
                                    <i class="fas fa-list-check"></i>
                                    <div class="stat-value"><?php echo number_format($count_all_applications_variation); ?></div>
                                    <div class="stat-label">All Applications</div>
                                </div>
                            </a>
                            <div class="chart-container">
                                <canvas id="chart-variations-all"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const sections = ["section1", "section2", "section3"];

      function divVisibility(sectionId) {
        sections.forEach(function (id) {
          const section = document.getElementById(id);
          if (section) {
            section.style.display = id === sectionId ? "grid" : "none";
          }
        });

        document.querySelectorAll(".nav-pills a").forEach(function (link) {
          link.classList.remove("active");
          if (link.getAttribute("onclick").includes(sectionId)) {
            link.classList.add("active");
          }
        });

        const titleMap = {
          section1: "Applications Dashboard",
          section2: "Renewals Dashboard",
          section3: "Variations Dashboard",
        };
        const contentHeader = document.querySelector(".content-header h2");
        if (contentHeader) {
          contentHeader.textContent =
            titleMap[sectionId] || "Applications Dashboard";
        }
      }

      window.divVisibility = divVisibility;
      divVisibility("section1");
    });
  </script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const topLevelDetails = document.querySelectorAll(".roadmap > details");
      topLevelDetails.forEach((detail) => {
        detail.addEventListener("toggle", () => {
          if (detail.open) {
            topLevelDetails.forEach((otherDetail) => {
              if (otherDetail !== detail) {
                otherDetail.open = false;
              }
            });
          }
        });
      });

      const nestedDetailsGroups = document.querySelectorAll(
        ".roadmap details > ul > li > details"
      );
      nestedDetailsGroups.forEach((detail) => {
        detail.addEventListener("toggle", () => {
          if (detail.open) {
            const siblingDetails =
              detail.parentElement.parentElement.querySelectorAll("details");
            siblingDetails.forEach((otherDetail) => {
              if (otherDetail !== detail) {
                otherDetail.open = false;
              }
            });
          }
        });
      });
    });
  </script>
  <script>
    const centerTextPlugin = {
      id: "centerText",
      beforeDraw(chart) {
        const {
          ctx,
          chartArea: { width, height },
        } = chart;
        ctx.save();
        ctx.font = "bold 18px Nunito";
        ctx.fillStyle = "#111827";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";

        const data = chart.data.datasets[0].data;
        const total = data.reduce((sum, val) => sum + val, 0);

        ctx.restore();
      },
    };

    Chart.register(centerTextPlugin);

    function createGradient(ctx, colorStart, colorEnd) {
      const gradient = ctx.createLinearGradient(0, 0, 0, 120);
      gradient.addColorStop(0, colorStart);
      gradient.addColorStop(1, colorEnd);
      return gradient;
    }

    const chartConfig = (data, labels, colors, canvasId) => {
      const ctx = document.getElementById(canvasId).getContext("2d");
      return {
        type: "doughnut",
        data: {
          labels: labels,
          datasets: [
            {
              data: data,
              backgroundColor: colors.map((color) =>
                createGradient(ctx, color[0], color[1])
              ),
              borderWidth: 0,
            },
          ],
        },
        options: {
          cutout: "50%",
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              enabled: false,
              external: function (context) {
                let tooltipEl = document.getElementById("chartjs-tooltip");
                if (!tooltipEl) {
                  tooltipEl = document.createElement("div");
                  tooltipEl.id = "chartjs-tooltip";
                  tooltipEl.style.zIndex = "10000";
                  tooltipEl.style.backgroundColor = "rgba(0, 0, 0, 0.8)";
                  tooltipEl.style.color = "#fff";
                  tooltipEl.style.padding = "8px";
                  tooltipEl.style.borderRadius = "6px";
                  tooltipEl.style.fontFamily = "Nunito";
                  tooltipEl.style.fontSize = "12px";
                  tooltipEl.style.pointerEvents = "none";
                  tooltipEl.style.position = "absolute";
                  tooltipEl.style.transition = "all 0.2s ease";
                  document.body.appendChild(tooltipEl);
                }

                const tooltipModel = context.tooltip;

                if (tooltipModel.opacity === 0) {
                  tooltipEl.style.opacity = "0";
                  return;
                }

                tooltipEl.style.opacity = "1";

                if (tooltipModel.body) {
                  const bodyLines = tooltipModel.body.map((b) => b.lines);
                  tooltipEl.innerHTML = `<div style="padding: 6px">${bodyLines[0]}</div>`;
                }

                const position = context.chart.canvas.getBoundingClientRect();
                tooltipEl.style.left =
                  position.left +
                  window.pageXOffset +
                  tooltipModel.caretX +
                  "px";
                tooltipEl.style.top =
                  position.top +
                  window.pageYOffset +
                  tooltipModel.caretY -
                  tooltipEl.offsetHeight -
                  10 +
                  "px";
              },
              callbacks: {
                label: function (context) {
                  return `${context.label}: ${context.raw}%`;
                },
              },
            },
          },
          animation: {
            animateScale: true,
            animateRotate: true,
          },
          maintainAspectRatio: false,
        },
      };
    };

    const dimColors = [
      ["#73B194", "#91CFB2"],
      ["#D59281", "#E9B09F"],
      ["#ece2c2", "#f5edd9"],
    ];

    const doughnutCharts = [
      {
        id: "chart-applications-all",
        data: [20, 30, 50],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-applications-under-process",
        data: [50, 30, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-pending-screening",
        data: [60, 25, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-under-screening",
        data: [45, 35, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-pending-assessment",
        data: [55, 30, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-under-1st-assessment",
        data: [65, 20, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-pending-2nd-assessment",
        data: [50, 35, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-under-2nd-assessment",
        data: [70, 20, 10],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-query-letters",
        data: [40, 40, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-awaiting-feedback",
        data: [35, 45, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-pending-add-data-1st",
        data: [30, 50, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-add-data-under-1st",
        data: [60, 25, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-pending-add-data-2nd",
        data: [40, 40, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-add-data-under-2nd",
        data: [65, 20, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-manager-review",
        data: [75, 15, 10],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-pending-gmp",
        data: [50, 30, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-pending-peer-review",
        data: [60, 25, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-passed-peer-review",
        data: [80, 15, 5],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-registered",
        data: [85, 10, 5],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-rejected",
        data: [20, 60, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-expired",
        data: [15, 70, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-backlogs",
        data: [25, 55, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-registered",
        data: [80, 15, 5],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewal-applications",
        data: [65, 25, 10],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-pending-assessment",
        data: [55, 30, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-under-1st",
        data: [60, 25, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-pending-2nd",
        data: [50, 35, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-under-2nd",
        data: [70, 20, 10],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-query-letters",
        data: [40, 40, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-awaiting-feedback",
        data: [35, 45, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-pending-add-data-1st",
        data: [30, 50, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-add-data-under-1st",
        data: [60, 25, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-pending-add-data-2nd",
        data: [40, 40, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-add-data-under-2nd",
        data: [65, 20, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-manager-review-renewal",
        data: [75, 15, 10],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-pending-peer-review",
        data: [60, 25, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-passed-peer-review",
        data: [80, 15, 5],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-renewals-approved",
        data: [85, 10, 5],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-3months-before-expiry",
        data: [70, 20, 10],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-3months-grace-period",
        data: [50, 35, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-expired-withdrawn",
        data: [30, 50, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-all",
        data: [65, 25, 10],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-pending-assessment",
        data: [55, 30, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-under-1st",
        data: [60, 25, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-pending-2nd",
        data: [50, 35, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-under-2nd",
        data: [70, 20, 10],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-query-letters",
        data: [40, 40, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-awaiting-feedback",
        data: [35, 45, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-manager-review-variation",
        data: [75, 15, 10],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-pending-add-data-1st",
        data: [30, 50, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-add-data-under-1st",
        data: [60, 25, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-pending-add-data-2nd",
        data: [40, 40, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-add-data-under-2nd",
        data: [65, 20, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-pending-peer-review",
        data: [60, 25, 15],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-passed-peer-review",
        data: [80, 15, 5],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-cancelled",
        data: [20, 60, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-rejected",
        data: [25, 55, 20],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
      {
        id: "chart-variations-approved",
        data: [85, 10, 5],
        labels: ["On Time", "Delayed", "ToBeDelayed"],
        colors: dimColors,
      },
    ];

    window.onload = function () {
      doughnutCharts.forEach((chart) => {
        const canvas = document.getElementById(chart.id);
        if (canvas) {
          new Chart(
            canvas,
            chartConfig(chart.data, chart.labels, chart.colors, chart.id)
          );
        }
      });
    };
  </script>
</body>
