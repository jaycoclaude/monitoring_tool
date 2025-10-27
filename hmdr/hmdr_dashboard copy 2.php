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
        // URLs for access level 2 (no user_id or passcode in URL)
?>
        <a href="all_applications.php">All Applications</a><br>
        <a href="my_applications.php">My Applications</a>
<?php
    } elseif ($user_access == 3) {
        // URL for access level 3 (no user_id or passcode in URL)
?>
        <a href="my_applications.php">My Applications</a>
<?php
    } else {
}
} catch (Exception $e) {
    // Handle exception (log, display error, etc.)
    error_log('Error in dashboard: ' . $e->getMessage());
    echo '<div style="color:red;">An error occurred. Please contact the administrator.</div>';
}
    ///////////////End Check user assess///////////////////
    //echo 'OK';
    $count_expired_products = 0;
    $count_about_to_expire_products = 0;
    $count_active = 0;
    $count_under_renewal = 0;
    $count_expired_not_renewed = 0;
    $count_withdraw = 0;
    $count_grace_period = 0;
    $count_expired_and_withdrawn = 0;
    $count_renewal_in_progress = 0;
    //$department_email='hmdar@rwandafda.gov.rw';
    $department_email = 'imushimiyimana@rwandafda.gov.rw';
    //$customer_email='irenemuto@gmail.com';
    ////////////Calculate the difference between two dates////////////////
    // Declare and define two dates
    $start_date = strtotime($datetoday);
    //echo $start_date.'<br>';
    // Formulate the Difference between two dates
    //$diff = abs($date2 - $date1);

    //////////////////////////
    ///////////////Sample notifications////////////////
    /*$to = "dgasana@rwandafda.gov.rw";
	$subject = "Rwanda FDA notification - your account";
	$message = "Please use the following credentials to login: email: dgasana@rwandafda.gov.rw, passcode: 9807 (https://rwandafda.gov.rw/monitoring-tool-login)";
	$headers = "From: notifications@rwandafda.gov.rw";
	*/
    //$headers = "From: notifications@rwandafda.gov.rw". "\r\n" ."CC: imushimiyimana@rwandafda.gov.rw";
    //$headers = "From: notifications@rwandafda.gov.rw" . "\r\n" ."CC: imushimiyimana@rwandafda.gov.rw,rmuganga@rwandafda.gov.rw,vhabyalimana@rwandafda.gov.rw";
    //mail($to,$subject,$message,$headers);
    ////////////////////////////////

    //////////////////////
    /*
	$notification_type='MA-Monitoring tool notification';
	$notification_to_category='Staff';
	if(!($wpdb->get_results("SELECT * from tbl_hm_notifications where notification_to='$department_email' and notification_month='$monthtoday' and notification_year='$yeartoday' and notification_type='$notification_type' and notification_to_category='$notification_to_category'")))
{
	$department_email='hmdar@rwandafda.gov.rw';
	$to = $department_email;
	$subject = "Rwanda FDA notification - ".$notification_type;
$message = "MA applications insights:  Total: 3,250 applications, 2040 registered and 428 classified as backlogs in different approval levels. Compliance with the timelines: On 120 applications not assessed, 12 are complying; On 168 applications under 1st assessment 0 are complying; On 163 applications under 1st assessment 0 are complying; On 64 applications under query letters not sent 51 are complying; On 482 applications under query letters sent 403 are complying; On 25 applications under 1st assessment ADD.DATA, 0 are complying;  On 21 applications under 2nd assessment ADD.DATA, 0 are complying; On 36 applications under pending peer review, 0 are complying. (https://rwandafda.gov.rw/monitoring-tool-login)";
$headers = "From: notifications@rwandafda.gov.rw" . "\r\n" ."CC: imushimiyimana@rwandafda.gov.rw,rmuganga@rwandafda.gov.rw,vhabyalimana@rwandafda.gov.rw";
	mail($to,$subject,$message,$headers);
	$wpdb->insert( 
	'tbl_hm_notifications', 
		array( 
		'notification_to' => $department_email,
		'notification_subject' => $subject,
		'notification_message'=> $message,	
		'notification_headers' => $headers,
		'notification_date' => $datetoday,
		'notification_month' => $monthtoday,
		'notification_year' => $yeartoday,
		'notification_type'=> $notification_type,	
		'notification_to_category' => 'Staff'
		
		
	), 
	array( 
		'%s', 
		'%s',
		'%s', 
		'%s',
		'%s', 
		'%s', 
		'%s',
		'%s', 
		'%s'
	) 
);

	
}
*/

    /////////////////////////
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
          // ✅ Initialize counters before use
$count_under_renewal = 0;
$total_not_assigned_delayed01 = 0;
$total_not_assigned_ontime1 = 0;
$total_not_assigned_tobedelayed01 = 0;

// ✅ Increment the counter
$count_under_renewal += 1;

// ✅ Prepare PDO query
$stmtTimeline = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway
      LIMIT 20
");
$stmtTimeline->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
echo "Application stage " . $application_current_stage;
echo "Assessment pathway " . $assessment_procedure;

$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Loop through timeline results
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // ✅ Compute days since submission safely
    $days_processing_chart = 0;
    if (!empty($date_submitted) && $date_submitted != '0000-00-00') {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 86400;
    }

    // ✅ Categorize timeline
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed01 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime1 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed01 += 1;
    }
}

          /////////////////End Select processing timelines//////////////////////
          ////////////////////////
          ////////////////Values for the graphs///////////////
          //$total_not_assigned=$count_not_assigned;
          $total_not_assigned = $count_not_assigned;
          //echo $total_not_assigned;
          //$total_not_assigned_delayed=total_not_assigned_delayed;

          //$total_not_assigned_ontime=0;
          /////////////////////////////////////////////////////////////////////////
          // Calculate percentages with sum == 100 logic
          $total_not_assigned = $count_not_assigned;

          if ($total_not_assigned > 0) {
            // Raw percentages (floats)
            $raw_percentages = [
              'delayed' => ($total_not_assigned_delayed01 / $total_not_assigned) * 100,
              'tobedelayed' => ($total_not_assigned_tobedelayed01 / $total_not_assigned) * 100,
              'ontime' => ($total_not_assigned_ontime01 / $total_not_assigned) * 100,
            ];

            // Floor values and store remainders
            $floored = [];
            $remainders = [];
            $total_floor = 0;
            foreach ($raw_percentages as $key => $val) {
              $floored[$key] = floor($val);
              $remainders[$key] = $val - $floored[$key];
              $total_floor += $floored[$key];
            }

            // Distribute remaining points until sum = 100
            $difference = 100 - $total_floor;
            arsort($remainders);
            foreach ($remainders as $key => $rem) {
              if ($difference <= 0) break;
              $floored[$key]++;
              $difference--;
            }

            // Assign final rounded percentages
            $percentage_not_assigned_delayed01 = $floored['delayed'];
            $percentage_not_assigned_tobedelayed01 = $floored['tobedelayed'];
            $percentage_not_assigned_ontime01 = $floored['ontime'];
          } else {
            $percentage_not_assigned_delayed01 = 0;
            $percentage_not_assigned_tobedelayed01 = 0;
            $percentage_not_assigned_ontime01 = 0;
          }
          ////////////////////////////////////////////////////////////////////
        }
      }
      if (($hm_expiry_date < $datetoday) && ($hm_product_status == 'Registered')) {
        $count_expired_products += 1;
        //$grace_period_days=($start_date - $end_date)/60/60/24;
        $grace_period_days = (strtotime($datetoday) - strtotime($hm_expiry_date)) / 60 / 60 / 24;
        //echo $hm_registration_number." ".$grace_period_days."<br>";
        //$grace_period_days=($start_date - $end_date)/60/60/24;

        if (($grace_period_days <= 90) && ($current_status <> 'Renewal in Progress')) {
          $count_grace_period += 1;
        }
      }
      //else if($days_diff<=90)
      else if (($hm_expiry_date > $datetoday && $days_diff <= 90) && $current_status <> 'Renewal in Progress') {
        $count_about_to_expire_products += 1;
        /*if($current_status=='Renewal in Progress')
	{
		$count_renewal_in_progress+=1;
	}
	*/

        //////////////////////Notification to client--MA-About to Expire///////////////////////////////
        //if($hm_mah_email<>'' || $hm_ltr_email<>'')
        //{

        /* Start Notifications
	$notification_type='MA-About to Expire';
	$notification_to_category='Client';
	if(!($wpdb->get_results("SELECT * from tbl_hm_notifications where (notification_to='$hm_mah_email' or notification_to='$hm_ltr_email') and (notification_month='$monthtoday' and notification_year='$yeartoday' and notification_type='$notification_type' and notification_to_category='$notification_to_category' and hm_registration_number='$hm_registration_number')")))
	{
	//$to2 = $hm_mah_email;
	$to2 = 'imushimiyimana@yahoo.fr,irenemuto@gmail.com';
	//$to3 = $hm_ltr_email;
	$subject2 = "Rwanda FDA notification - MA-About to Expire";
	$message2 = "Dear Valued Customer," . "\r\n" ." This serves to remind you that your Market Authorization/Registration Certificate with Registration No.".$hm_registration_number." ". "for the product:".$hm_product_brand_name."/".$hm_generic_name."/".$hm_dosage_strength."/".$hm_dosage_form." is about to expire. Please check and apply for renewal before expiry.";
	$headers2 = "From: notification@rwandafda.gov.rw" . "\r\n" ."CC: imushimiyimana@rwandafda.gov.rw,rjanvier1998@gmail.com";
	//$headers = "From: notifications@rwandafda.gov.rw" . "\r\n" ."CC: imushimiyimana@rwandafda.gov.rw,rmuganga@rwandafda.gov.rw,vhabyalimana@rwandafda.gov.rw";

	mail($to2,$subject2,$message2,$headers2);
	$wpdb->insert( 
	'tbl_hm_notifications', 
		array( 
		'notification_to' => $to2,
		'notification_subject' => $subject2,
		'notification_message'=> $message2,	
		'notification_headers' => $headers2,
		'notification_date' => $datetoday,
		'notification_month' => $monthtoday,
		'notification_year' => $yeartoday,
		'notification_type'=> $notification_type,	
		'notification_to_category' => $notification_to_category,
		'hm_registration_number' => $hm_registration_number
		
		
	), 
	array( 
		'%s', 
		'%s',
		'%s', 
		'%s',
		'%s', 
		'%s', 
		'%s',
		'%s', 
		'%s', 
		'%s'
	) 
);
///////////////////////End Notification to client--MA-Expired//////////////////////////////	
}*/ ////////////End notifications
        //}

      }
      //echo $hm_application_number;
      /*else
{
$count_active+=1;	
}
*/
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











    ////////////////////////////////// start Applications control/////////////////////////////////////////



include 'application.php';





    ////////////////////////////////////End Applications control/////////////////////////////////////////





  


    ////////////////////Variations control////////////////////////

include 'variations.php';

/////////////////// end of variation control//////////////////////





    //  -------------------------------------------------------------------------------------------------------------------
      ////////////////Renewal Applications//////////////////////

include 'renewal.php';
  
    ///////////////////////End Select Renewal///////////////////////



  ?>

    <head>
      <title>MA - Monitoring Tool</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
      <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    padding: 20px;
  }

  .dashboard-container {
    max-width: 1800px;
    margin: 0 auto;
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
    background: #0052cc;
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
  }

  .roadmap h3 {
    text-align: center;
    margin-bottom: 20px;
    color: #2c3e50;
    padding-bottom: 10px;
    border-bottom: 2px solid #f1f1f1;
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
    color: #2c3e50;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .roadmap-list li i {
    margin-right: 8px;
  }

  .roadmap-list li.completed {
    background: #34c759;
    color: white;
  }

  .roadmap-list li.active {
    background: #0052cc;
    color: white;
  }

  .roadmap-list li.pending {
    background: #f1f1f1;
  }

  .roadmap details {
    margin-bottom: 10px;
  }

  .roadmap details summary {
    padding: 10px 15px;
    border-radius: 8px;
    background: #f8f9fa;
    cursor: pointer;
    font-weight: 600;
    color: #2c3e50;
    transition: background 0.3s ease;
  }

  .roadmap details summary i {
    margin-right: 8px;
  }

  .roadmap details summary:hover {
    background: #0052cc;
    color: white;
  }

  .roadmap details[open] summary {
    background: #0052cc;
    color: white;
  }

  .roadmap details ul {
    list-style: none;
    padding: 10px 20px;
  }

  .roadmap details ul li {
    margin: 5px 0;
  }

  .roadmap details ul li a {
    color: #2c3e50;
    text-decoration: none;
    display: block;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.3s ease;
  }

  .roadmap details ul li a:hover {
    background: #0052cc;
    color: white;
  }

  .main-content {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
  }

  .content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f1f1f1;
  }

  .grid-container {
    max-width: 1200px;
    margin: 0 auto;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 30px;
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

  .card-primary {
    background-color: #e6f0ff;
    border-left-color: #003087;
  }

  .card-primary i,
  .card-primary .stat-value,
  .card-primary .stat-label {
    color: #003087;
  }

  .card-primary:hover {
    background-color: #f0f5ff;
    border-left-color: #0047b3;
  }

  .card-primary .status-chip {
    background: #003087;
  }

  .card-success {
    background-color: #e6ffe6;
    border-left-color: #1e8741;
  }

  .card-success i,
  .card-success .stat-value,
  .card-success .stat-label {
    color: #1e8741;
  }

  .card-success:hover {
    background-color: #f0fff0;
    border-left-color: #2ca352;
  }

  .card-success .status-chip {
    background: #1e8741;
  }

  .card-warning {
    background-color: #fff5e6;
    border-left-color: #cc6d00;
  }

  .card-warning i,
  .card-warning .stat-value,
  .card-warning .stat-label {
    color: #cc6d00;
  }

  .card-warning:hover {
    background-color: #fffaf0;
    border-left-color: #e67e00;
  }

  .card-warning .status-chip {
    background: #cc6d00;
  }

  .card-danger {
    background-color: #ffe6e6;
    border-left-color: #b3120b;
  }

  .card-danger i,
  .card-danger .stat-value,
  .card-danger .stat-label {
    color: #b3120b;
  }

  .card-danger:hover {
    background-color: #fff0f0;
    border-left-color: #cc1e14;
  }

  .card-danger .status-chip {
    background: #b3120b;
  }

  .card-info {
    background-color: #e6fffa;
    border-left-color: #008a7a;
  }

  .card-info i,
  .card-info .stat-value,
  .card-info .stat-label {
    color: #008a7a;
  }

  .card-info:hover {
    background-color: #f0fffd;
    border-left-color: #00a693;
  }

  .card-info .status-chip {
    background: #008a7a;
  }

  .card-secondary {
    background-color: #f0f0f0;
    border-left-color: #4a4a4a;
  }

  .card-secondary i,
  .card-secondary .stat-value,
  .card-secondary .stat-label {
    color: #4a4a4a;
  }

  .card-secondary:hover {
    background-color: #f8f8f8;
    border-left-color: #5e5e5e;
  }

  .card-secondary .status-chip {
    background: #4a4a4a;
  }

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

    .stats-grid {
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
  }
</style>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          // Define sections
          const sections = ['section1', 'section2', 'section3'];

          // Function to toggle section visibility
          function divVisibility(sectionId) {
            // Hide all sections
            sections.forEach(function(id) {
              const section = document.getElementById(id);
              if (section) {
                section.style.display = id === sectionId ? 'grid' : 'none';
              }
            });

            // Update active class on navigation links
            document.querySelectorAll('.nav-pills a').forEach(function(link) {
              link.classList.remove('active');
              if (link.getAttribute('onclick').includes(sectionId)) {
                link.classList.add('active');
              }
            });

            // Update dashboard title based on section
            const titleMap = {
              'section1': 'Applications Dashboard',
              'section2': 'Renewals Dashboard',
              'section3': 'Variations Dashboard'
            };
            const contentHeader = document.querySelector('.content-header h2');
            if (contentHeader) {
              contentHeader.textContent = titleMap[sectionId] || 'Applications Dashboard';
            }
          }

          // Expose function to global scope for onclick handlers
          window.divVisibility = divVisibility;

          // Initialize by showing the first section (Applications)
          divVisibility('section1');
        });
      </script>
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          // Top-level accordion: only one details open at a time in .roadmap
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

          // Nested accordion: only one nested details open at a time within each .roadmap details
          const nestedDetailsGroups = document.querySelectorAll('.roadmap details > ul > li > details');
          nestedDetailsGroups.forEach(detail => {
            detail.addEventListener('toggle', () => {
              if (detail.open) {
                // Find sibling details within the same parent <ul>
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
      <script>
        const centerTextPlugin = {
          id: 'centerText',
          beforeDraw(chart) {
            const {
              ctx,
              chartArea: {
                width,
                height
              }
            } = chart;
            ctx.save();
            ctx.font = 'bold 18px Nunito';
            ctx.fillStyle = '#111827';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            const data = chart.data.datasets[0].data;
            const total = data.reduce((sum, val) => sum + val, 0);

            // ctx.fillText(total.toLocaleString(), width / 2, height / 2);
            ctx.restore();
          }
        };

        Chart.register(centerTextPlugin);

        function createGradient(ctx, colorStart, colorEnd) {
          const gradient = ctx.createLinearGradient(0, 0, 0, 120);
          gradient.addColorStop(0, colorStart);
          gradient.addColorStop(1, colorEnd);
          return gradient;
        }

        const chartConfig = (data, labels, colors, canvasId) => {
          const ctx = document.getElementById(canvasId).getContext('2d');
          return {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [{
                data: data,
                backgroundColor: colors.map(color => createGradient(ctx, color[0], color[1])),
                borderWidth: 0
              }]
            },
            options: {
              cutout: '50%',
              plugins: {
                legend: {
                  display: false
                },
                tooltip: {
                  enabled: false, // Disable default tooltip
                  external: function(context) {
                    let tooltipEl = document.getElementById('chartjs-tooltip');
                    if (!tooltipEl) {
                      tooltipEl = document.createElement('div');
                      tooltipEl.id = 'chartjs-tooltip';
                      tooltipEl.style.zIndex = '10000';
                      tooltipEl.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                      tooltipEl.style.color = '#fff';
                      tooltipEl.style.padding = '8px';
                      tooltipEl.style.borderRadius = '6px';
                      tooltipEl.style.fontFamily = 'Nunito';
                      tooltipEl.style.fontSize = '12px';
                      tooltipEl.style.pointerEvents = 'none';
                      tooltipEl.style.position = 'absolute';
                      tooltipEl.style.transition = 'all 0.2s ease'; // ✨ Smooth motion added
                      document.body.appendChild(tooltipEl);
                    }

                    const tooltipModel = context.tooltip;

                    // ✨ Fix: check for opacity === 0 to hide
                    if (tooltipModel.opacity === 0) {
                      tooltipEl.style.opacity = '0';
                      return;
                    }

                    // Show and update tooltip
                    tooltipEl.style.opacity = '1';

                    if (tooltipModel.body) {
                      const bodyLines = tooltipModel.body.map(b => b.lines);
                      tooltipEl.innerHTML = `<div style="padding: 6px">${bodyLines[0]}</div>`;
                    }

                    const position = context.chart.canvas.getBoundingClientRect();
                    tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
                    tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY - tooltipEl.offsetHeight - 10 + 'px';
                  },
                  callbacks: {
                    label: function(context) {
                      return `${context.label}: ${context.raw}%`;
                    }
                  }
                }
                // centerText: {
                //  text: centerText 
                // }
              },
              animation: {
                animateScale: true,
                animateRotate: true
              },
              maintainAspectRatio: false
            }
          };
        };

        const dimColors = [
          ['#73B194', '#91CFB2'], // On Time
          ['#D59281', '#E9B09F'], // Delayed
          ['#ece2c2', '#f5edd9'] // To Be Delayed
        ];

        const doughnutCharts = [

          /////////////////////////////////////// Applications //////////////////////////////////////////

          // { id: 'chart-applications-all', data: [20, 30, 50], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-applications-under-process', data: [50, 30, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          {
            id: 'chart-pending-screening',
            data: [<?php echo $percentage_not_assigned_ontime1; ?>, <?php echo $percentage_not_assigned_delayed1; ?>, <?php echo $percentage_not_assigned_tobedelayed1; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-under-screening',
            data: [<?php echo $percentage_not_assigned_ontime2; ?>, <?php echo $percentage_not_assigned_delayed2; ?>, <?php echo $percentage_not_assigned_tobedelayed2; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-pending-assessment',
            data: [<?php echo $percentage_not_assigned_ontime7; ?>, <?php echo $percentage_not_assigned_delayed7; ?>, <?php echo $percentage_not_assigned_tobedelayed7; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-under-1st-assessment',
            data: [<?php echo $percentage_not_assigned_ontime3; ?>, <?php echo $percentage_not_assigned_delayed3; ?>, <?php echo $percentage_not_assigned_tobedelayed3; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-pending-2nd-assessment',
            data: [<?php echo $percentage_not_assigned_ontime35; ?>, <?php echo $percentage_not_assigned_delayed35; ?>, <?php echo $percentage_not_assigned_tobedelayed35; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-under-2nd-assessment',
            data: [<?php echo $percentage_not_assigned_ontime4; ?>, <?php echo $percentage_not_assigned_delayed4; ?>, <?php echo $percentage_not_assigned_tobedelayed4; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          //   { id: 'chart-query-letters', data: [20, 50, 30], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          {
            id: 'chart-awaiting-feedback',
            data: [<?php echo $percentage_not_assigned_ontime25; ?>, <?php echo $percentage_not_assigned_delayed25; ?>, <?php echo $percentage_not_assigned_tobedelayed25; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-pending-add-data-1st',
            data: [<?php echo $percentage_not_assigned_ontime36; ?>, <?php echo $percentage_not_assigned_delayed36; ?>, <?php echo $percentage_not_assigned_tobedelayed36; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-add-data-under-1st',
            data: [<?php echo $percentage_not_assigned_ontime21; ?>, <?php echo $percentage_not_assigned_delayed21; ?>, <?php echo $percentage_not_assigned_tobedelayed21; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-pending-add-data-2nd',
            data: [<?php echo $percentage_not_assigned_ontime21; ?>, <?php echo $percentage_not_assigned_delayed21; ?>, <?php echo $percentage_not_assigned_tobedelayed21; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-add-data-under-2nd',
            data: [<?php echo $percentage_not_assigned_ontime37; ?>, <?php echo $percentage_not_assigned_delayed37; ?>, <?php echo $percentage_not_assigned_tobedelayed37; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-query-letters',
            data: [<?php echo $percentage_not_assigned_ontime8; ?>, <?php echo $percentage_not_assigned_delayed8; ?>, <?php echo $percentage_not_assigned_tobedelayed8; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          // { id: 'chart-pending-gmp', data: [30, 50, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-pending-peer-review', data: [40, 40, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-passed-peer-review', data: [50, 30, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-registered', data: [70, 20, 10], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-rejected', data: [20, 50, 30], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-expired', data: [30, 40, 30], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },

          /////////////////////////////////////Renewals/////////////////////////////////////


          {
            id: 'chart-renewals-pending-assessment',
            data: [<?php echo $percentage_not_assigned_ontime07; ?>, <?php echo $percentage_not_assigned_delayed07; ?>, <?php echo $percentage_not_assigned_tobedelayed07; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-renewals-under-1st',
            data: [<?php echo $percentage_not_assigned_ontime03; ?>, <?php echo $percentage_not_assigned_delayed03; ?>, <?php echo $percentage_not_assigned_tobedelayed03; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-renewals-under-2nd',
            data: [<?php echo $percentage_not_assigned_ontime04; ?>, <?php echo $percentage_not_assigned_delayed04; ?>, <?php echo $percentage_not_assigned_tobedelayed04; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-renewals-pending-2nd',
            data: [<?php echo $percentage_not_assigned_ontime035; ?>, <?php echo $percentage_not_assigned_delayed035; ?>, <?php echo $percentage_not_assigned_tobedelayed035; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },

          {
            id: 'chart-renewals-query-letters',
            data: [<?php echo $percentage_not_assigned_ontime08; ?>, <?php echo $percentage_not_assigned_delayed08; ?>, <?php echo $percentage_not_assigned_tobedelayed08; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },

          {
            id: 'chart-renewals-awaiting-feedback',
            data: [<?php echo $percentage_not_assigned_ontime025; ?>, <?php echo $percentage_not_assigned_delayed025; ?>, <?php echo $percentage_not_assigned_tobedelayed025; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-renewals-add-data-under-1st',
            data: [<?php echo $percentage_not_assigned_ontime08; ?>, <?php echo $percentage_not_assigned_delayed08; ?>, <?php echo $percentage_not_assigned_tobedelayed08; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },

          /////////////these still has Dumy data because they are not declared and initialised in the code above/////////////////

          // {
          //   id: 'chart-renewals-pending-add-data-2nd',
          //   data: [30, 40, 30],
          //   labels: ['On Time', 'Delayed', 'ToBeDelayed'],
          //   colors: dimColors
          // },
          // {
          //   id: 'chart-renewals-add-data-under-2nd',
          //   data: [60, 20, 20],
          //   labels: ['On Time', 'Delayed', 'ToBeDelayed'],
          //   colors: dimColors
          // },
          // {
          //   id: 'chart-renewals-pending-add-data-1st',
          //   data: [20, 50, 30],
          //   labels: ['On Time', 'Delayed', 'ToBeDelayed'],
          //   colors: dimColors
          // },

          ////////////////Kept for future use/////////////////////

          // { id: 'chart-renewals-pending-peer-review', data: [40, 40, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-renewals-passed-peer-review', data: [50, 30, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-renewals-approved', data: [70, 20, 10], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-3months-before-expiry', data: [30, 50, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-3months-grace-period', data: [20, 30, 50], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-expired-withdrawn', data: [40, 40, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },


          ///////////////////////////////////// Variations ///////////////////////////////////////

          //   { id: 'chart-variations-all', data: [50, 30, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors }, // this is on all applications

          //   can't find this one either
          {
            id: 'chart-variations-pending-assessment',
            data: [<?php echo $percentage_not_assigned_ontime007; ?>, <?php echo $percentage_not_assigned_delayed007; ?>, <?php echo $percentage_not_assigned_tobedelayed007; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },


          {
            id: 'chart-variations-under-1st',
            data: [<?php echo $percentage_not_assigned_ontime003; ?>, <?php echo $percentage_not_assigned_delayed003; ?>, <?php echo $percentage_not_assigned_tobedelayed003; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-variations-pending-2nd',
            data: [<?php echo $percentage_not_assigned_ontime0035; ?>, <?php echo $percentage_not_assigned_delayed0035; ?>, <?php echo $percentage_not_assigned_tobedelayed0035; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },


          {
            id: 'chart-variations-under-2nd',
            data: [<?php echo $percentage_not_assigned_ontime002; ?>, <?php echo $percentage_not_assigned_delayed002; ?>, <?php echo $percentage_not_assigned_tobedelayed002; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-variations-query-letters',
            data: [<?php echo $percentage_not_assigned_ontime004; ?>, <?php echo $percentage_not_assigned_delayed004; ?>, <?php echo $percentage_not_assigned_tobedelayed004; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-variations-awaiting-feedback',
            data: [<?php echo $percentage_not_assigned_ontime0025; ?>, <?php echo $percentage_not_assigned_delayed0025; ?>, <?php echo $percentage_not_assigned_tobedelayed0025; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },

          //can't find this one either
          {
            id: 'chart-variations-add-data-under-1st',
            data: [<?php echo $percentage_not_assigned_ontime0021; ?>, <?php echo $percentage_not_assigned_delayed0021; ?>, <?php echo $percentage_not_assigned_tobedelayed0021; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          {
            id: 'chart-variations-pending-add-data-1st',
            data: [<?php echo $percentage_not_assigned_ontime0036; ?>, <?php echo $percentage_not_assigned_delayed0036; ?>, <?php echo $percentage_not_assigned_tobedelayed0036; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },

          //can't find this one either
          //   {
          //     id: 'chart-variations-pending-add-data-2nd',
          //     data: [30, 40, 30],
          //     labels: ['On Time', 'Delayed', 'ToBeDelayed'],
          //     colors: dimColors
          //   },
          {
            id: 'chart-variations-add-data-under-1st',
            data: [<?php echo $percentage_not_assigned_ontime0037; ?>, <?php echo $percentage_not_assigned_delayed0037; ?>, <?php echo $percentage_not_assigned_tobedelayed0037; ?>],
            labels: ['On Time', 'Delayed', 'ToBeDelayed'],
            colors: dimColors
          },
          //   {
          //     id: 'chart-variations-add-data-under-2nd',
          //     data: [30, 40, 30],
          //     labels: ['On Time', 'Delayed', 'ToBeDelayed'],
          //     colors: dimColors
          //   },

          ////////////////Kept for future use/////////////////////

          // { id: 'chart-variations-pending-peer-review', data: [40, 40, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-variations-passed-peer-review', data: [50, 30, 20], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-variations-cancelled', data: [20, 50, 30], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-variations-rejected', data: [30, 40, 30], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors },
          // { id: 'chart-variations-approved', data: [70, 20, 10], labels: ['On Time', 'Delayed', 'ToBeDelayed'], colors: dimColors }
        ];

        window.onload = function() {
          doughnutCharts.forEach(chart => {
            const canvas = document.getElementById(chart.id);
            if (canvas) {
              new Chart(canvas, chartConfig(chart.data, chart.labels, chart.colors, chart.id));
              // new Chart(canvas, chartConfig(chart.data, chart.labels, chart.colors, chart.id,chart.centerText));
            }
          });
        };
      </script>
    </head>

    <body>
      <?php include 'header.php';?>
      <div class="dashboard-container">
        <div class="top-nav">
          <ul class="nav-pills" style="list-style-type: none; padding-left: 0; margin: 0;">
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
            <h3>Application Roadmap</h3>
            <ul class="roadmap-list" style="list-style-type: none; padding-left: 0; margin: 0;">
              <li class="completed"><i class="fas fa-check-circle"></i> 1. Received Application</li>
              <li class="completed"><i class="fas fa-search"></i> 2. Screening</li>
              <li class="completed"><i class="fas fa-tasks"></i> 3. 1st Assessment</li>
              <li class="completed"><i class="fas fa-clipboard-list"></i> 4. 2nd Assessment</li>
              <li class="completed"><i class="fas fa-users"></i>5. Peer Review</li>
              <li class="completed"><i class="fas fa-check-double"></i> 6. Approval</li>
            </ul>

            <h3>Filters & Actions</h3>
            <details>
              <summary><i class="fas fa-filter"></i> All Applications</summary>
              <ul>
                <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_backlog, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4758))))); ?>"><i class="fas fa-history"></i> Backlog</a></li>
                <li><a href="<?php echo esc_url(add_query_arg('exp_num', $count_under_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4667))))); ?>"><i class="fas fa-box"></i> Registered Products</a></li>
                <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_rejected, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4742))))); ?>"><i class="fas fa-times-circle"></i> Rejected</a></li>
                <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_expired_applications, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4671))))); ?>"><i class="fas fa-calendar-times"></i> Expired</a></li>
              </ul>
            </details>

            <details open>
              <summary><i class="fas fa-sync-alt"></i> Under Process</summary>
              <ul>
                <li>
                  <details>
                    <summary><i class="fas fa-search"></i> Screening</summary>
                    <ul>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_not_assigned, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4714))))); ?>"><i class="fas fa-hourglass-start"></i> Pending Screening</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_screening, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4698))))); ?>"><i class="fas fa-spinner"></i> Under Screening</a></li>
                    </ul>
                  </details>
                </li>
                <li>
                  <details>
                    <summary><i class="fas fa-tasks"></i> Assessment</summary>
                    <ul>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_not_assessed, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4719))))); ?>"><i class="fas fa-hourglass-half"></i> Pending Assessment</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4841))))); ?>"><i class="fas fa-clipboard-check"></i> Under 1st Assessment</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_pending, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5178))))); ?>"><i class="fas fa-clipboard-list"></i> Pending 2nd Assessment</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_assessment, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4755))))); ?>"><i class="fas fa-tasks"></i> Under 2nd Assessment</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_pending_add_data, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5228))))); ?>"><i class="fas fa-folder-plus"></i> Pending ADD. DATA 1st Assessment</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_add_data, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4710))))); ?>"><i class="fas fa-file-medical"></i> ADD. DATA, Under 1st Assessment</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_pending_add_data, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5230))))); ?>"><i class="fas fa-folder-plus"></i> Pending ADD. DATA 2nd Assessment</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_add_data, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4778))))); ?>"><i class="fas fa-file-medical"></i> ADD. DATA, Under 2nd Assessment</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_manager_report_review, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5552))))); ?>"><i class="fas fa-user-tie"></i> Manager (1st & 2nd Reports Review)</a></li>
                    </ul>
                  </details>
                </li>
                <li>
                  <details>
                    <summary><i class="fas fa-question-circle"></i> Queries</summary>
                    <ul>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_second_assessment_completed_letter_not_sent, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4772))))); ?>"><i class="fas fa-envelope"></i> Query Letters to be Sent</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_awaiting_applicant_feedback, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4770))))); ?>"><i class="fas fa-reply"></i> Awaiting Applicant's Feedback</a></li>
                    </ul>
                  </details>
                </li>
                <li>
                  <details>
                    <summary><i class="fas fa-users"></i> Peer Review</summary>
                    <ul>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_gmp, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4740))))); ?>"><i class="fas fa-industry"></i> Pending GMP</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_peer_review, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4717))))); ?>"><i class="fas fa-user-check"></i> Pending Peer Review</a></li>
                      <li><a href="<?php echo esc_url(add_query_arg('ab_num', $count_under_approval, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4723))))); ?>"><i class="fas fa-check-double"></i> Passed Peer Review</a></li>
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
    <div class="stats-grid" id="section1">
      <div class="stat-card-wrapper card-primary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_all_applications, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4780))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-list-check"></i>
            <div class="stat-value"><?php echo number_format($count_all_applications); ?></div>
            <div class="stat-label">All Applications</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-applications-all"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_all_applications_under_process, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5204))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-cogs"></i>
            <div class="stat-value"><?php echo number_format($count_all_applications_under_process); ?></div>
            <div class="stat-label">Applications Under Process</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-applications-under-process"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-danger">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_backlog, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4758))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="stat-value"><?php echo number_format($count_backlog); ?></div>
            <div class="stat-label">Backlogs</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-backlogs"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-warning">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_not_assigned, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4714))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-hourglass-half"></i>
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
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_screening, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4698))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-search"></i>
            <div class="stat-value"><?php echo number_format($count_screening); ?></div>
            <div class="stat-label">Under Screening</div>
          </div>
          <div class="status-chip">Step 2</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-under-screening"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-warning">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_not_assessed, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4719))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-clipboard-check"></i>
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
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4841))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-tasks"></i>
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
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_pending, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5178))))); ?>" class="stat-card">
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
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_assessment, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4755))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-alt"></i>
            <div class="stat-value"><?php echo number_format($count_assessment); ?></div>
            <div class="stat-label">Under 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 6</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-under-2nd-assessment"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_second_assessment_completed_letter_not_sent, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4772))))); ?>" class="stat-card">
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
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_awaiting_applicant_feedback, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4770))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-user-clock"></i>
            <div class="stat-value"><?php echo number_format($count_awaiting_applicant_feedback); ?></div>
            <div class="stat-label">Awaiting Applicant's Feedback</div>
          </div>
          <div class="status-chip">Step 8</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-awaiting-feedback"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-warning">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_pending_add_data, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5228))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-exclamation-circle"></i>
            <div class="stat-value"><?php echo number_format($count_pending_first_assessment_pending_add_data); ?></div>
            <div class="stat-label">Pending ADD. DATA 1st Assessment</div>
          </div>
          <div class="status-chip">Step 9</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-pending-add-data-1st"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-primary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_manager_report_review, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5552))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-signature"></i>
            <div class="stat-value"><?php echo number_format($count_manager_report_review); ?></div>
            <div class="stat-label">Manager (1st and 2nd Assessment Reports Review)</div>
          </div>
          <div class="status-chip">Step 10</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-manager-review"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_add_data, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4710))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-alt"></i>
            <div class="stat-value"><?php echo number_format($count_pending_first_assessment_add_data); ?></div>
            <div class="stat-label">ADD. DATA, Under 1st Assessment</div>
          </div>
          <div class="status-chip">Step 11</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-add-data-under-1st"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_pending_add_data, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5230))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-import"></i>
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
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_add_data, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4778))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-export"></i>
            <div class="stat-value"><?php echo number_format($count_pending_second_assessment_add_data); ?></div>
            <div class="stat-label">ADD. DATA, Under 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 13</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-add-data-under-2nd"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-info">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_gmp, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4740))))); ?>" class="stat-card">
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
      <div class="stat-card-wrapper card-info">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_peer_review, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4717))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-users"></i>
            <div class="stat-value"><?php echo number_format($count_peer_review); ?></div>
            <div class="stat-label">Pending Peer Review</div>
          </div>
          <div class="status-chip">Step 15</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-pending-peer-review"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-info">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_under_approval, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4723))))); ?>" class="stat-card">
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
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('exp_num', $count_under_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4667))))); ?>" target="_blank" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-check-circle"></i>
            <div class="stat-value"><?php echo number_format($count_registered); ?></div>
            <div class="stat-label">Registered</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-registered"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_rejected, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4742))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-ban"></i>
            <div class="stat-value"><?php echo number_format($count_rejected); ?></div>
            <div class="stat-label">Rejected</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-rejected"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_expired_applications, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4671))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-calendar-times"></i>
            <div class="stat-value"><?php echo number_format($count_expired_applications); ?></div>
            <div class="stat-label">Expired</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-expired"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="grid-container">
    <div class="stats-grid" id="section2" style="display: none;">
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('exp_num', $count_under_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4667))))); ?>" target="_blank" class="stat-card">
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
        <a href="<?php echo esc_url(add_query_arg('exp_num', $count_under_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4979))))); ?>" class="stat-card">
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
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('exp_num', $count_under_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4829))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-hourglass-half"></i>
            <div class="stat-value"><?php echo number_format($count_not_assessed_renewal); ?></div>
            <div class="stat-label">Pending Assessment</div>
          </div>
          <div class="status-chip">Step 1</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-pending-assessment"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5084))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-cogs"></i>
            <div class="stat-value"><?php echo number_format($count_pending_first_assessment_renewal); ?></div>
            <div class="stat-label">Under 1st Assessment</div>
          </div>
          <div class="status-chip">Step 2</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-under-1st"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5618))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-clipboard-list"></i>
            <div class="stat-value"><?php echo number_format($count_pending_second_assessment_pending_renewal); ?></div>
            <div class="stat-label">Pending 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 3</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-pending-2nd"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_assessment_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5082))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-alt"></i>
            <div class="stat-value"><?php echo number_format($count_assessment_renewal); ?></div>
            <div class="stat-label">Under 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 4</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-under-2nd"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_second_assessment_completed_letter_not_sent_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5022))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-envelope"></i>
            <div class="stat-value"><?php echo number_format($count_queried_renewal); ?></div>
            <div class="stat-label">Query Letters to be Sent</div>
          </div>
          <div class="status-chip">Step 5</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-query-letters"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_awaiting_applicant_feedback_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4805))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-user-clock"></i>
            <div class="stat-value"><?php echo number_format($count_awaiting_applicant_feedback_renewal); ?></div>
            <div class="stat-label">Awaiting Applicant's Feedback</div>
          </div>
          <div class="status-chip">Step 6</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-awaiting-feedback"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_pending_add_data_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5024))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-exclamation-circle"></i>
            <div class="stat-value"><?php echo number_format($count_pending_first_assessment_pending_add_data_renewal); ?></div>
            <div class="stat-label">Pending ADD. DATA 1st Assessment</div>
          </div>
          <div class="status-chip">Step 7</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-pending-add-data-1st"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-primary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_manager_report_review_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5552))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-signature"></i>
            <div class="stat-value"><?php echo number_format($count_manager_report_review_renewal); ?></div>
            <div class="stat-label">Manager (1st and 2nd Assessment Reports Review)</div>
          </div>
          <div class="status-chip">Step 8</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-manager-review-renewal"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_add_data_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5024))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-alt"></i>
            <div class="stat-value"><?php echo number_format($count_pending_first_assessment_add_data_renewal); ?></div>
            <div class="stat-label">ADD. DATA, Under 1st Assessment</div>
          </div>
          <div class="status-chip">Step 9</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-add-data-under-1st"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_pending_add_data_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5026))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-import"></i>
            <div class="stat-value"><?php echo number_format($count_pending_second_assessment_pending_add_data_renewal); ?></div>
            <div class="stat-label">Pending ADD. DATA 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 10</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-pending-add-data-2nd"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_add_data_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5028))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-export"></i>
            <div class="stat-value"><?php echo number_format($count_pending_second_assessment_add_data_renewal); ?></div>
            <div class="stat-label">ADD. DATA, Under 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 11</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-add-data-under-2nd"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-info">
        <a href="<?php echo esc_url(add_query_arg('exp_num', $count_under_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5020))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-users"></i>
            <div class="stat-value"><?php echo number_format($count_pending_peer_review_renewal); ?></div>
            <div class="stat-label">Pending Peer Review</div>
          </div>
          <div class="status-chip">Step 12</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-pending-peer-review"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-info">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_awaiting_applicant_feedback_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4827))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-check-double"></i>
            <div class="stat-value"><?php echo number_format($count_under_approval_renewal); ?></div>
            <div class="stat-label">Passed Peer Review</div>
          </div>
          <div class="status-chip">Step 13</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-passed-peer-review"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-info">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_approved_renewal, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5033))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-check-circle"></i>
            <div class="stat-value"><?php echo number_format($count_approved_renewal); ?></div>
            <div class="stat-label">Approved</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-renewals-approved"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_about_to_expire_products, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4673))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-clock"></i>
            <div class="stat-value"><?php echo number_format($count_about_to_expire_products); ?></div>
            <div class="stat-label">3 Months Before Expiry</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-3months-before-expiry"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_expired_and_withdrawn, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4784))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-hourglass-end"></i>
            <div class="stat-value"><?php echo number_format($count_grace_period); ?></div>
            <div class="stat-label">3 Months Grace Period</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-3months-grace-period"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_expired_and_withdrawn, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4766))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-calendar-times"></i>
            <div class="stat-value"><?php echo number_format($count_expired_and_withdrawn); ?></div>
            <div class="stat-label">Expired/Withdrawn</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-expired-withdrawn"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="grid-container">
    <div class="stats-grid" id="section3" style="display: none;">
      <div class="stat-card-wrapper card-primary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_all_applications_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4798))))); ?>" class="stat-card">
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
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_not_assessed_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5086))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-hourglass-half"></i>
            <div class="stat-value"><?php echo number_format($count_not_assessed_variation); ?></div>
            <div class="stat-label">Pending Assessment</div>
          </div>
          <div class="status-chip">Step 1</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-pending-assessment"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5090))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-cogs"></i>
            <div class="stat-value"><?php echo number_format($count_pending_first_assessment_variation); ?></div>
            <div class="stat-label">Under 1st Assessment</div>
          </div>
          <div class="status-chip">Step 2</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-under-1st"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5088))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-clipboard-list"></i>
            <div class="stat-value"><?php echo number_format($count_pending_second_assessment_pending_variation); ?></div>
            <div class="stat-label">Pending 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 3</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-pending-2nd"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5092))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-alt"></i>
            <div class="stat-value"><?php echo number_format($count_pending_second_assessment_variation); ?></div>
            <div class="stat-label">Under 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 4</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-under-2nd"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_second_assessment_completed_letter_not_sent_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4793))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-envelope"></i>
            <div class="stat-value"><?php echo number_format($count_second_assessment_completed_letter_not_sent_variation); ?></div>
            <div class="stat-label">Query Letters to be Sent</div>
          </div>
          <div class="status-chip">Step 5</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-query-letters"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_awaiting_applicant_feedback_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4791))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-user-clock"></i>
            <div class="stat-value"><?php echo number_format($count_awaiting_applicant_feedback_variation); ?></div>
            <div class="stat-label">Awaiting Applicant's Feedback</div>
          </div>
          <div class="status-chip">Step 6</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-awaiting-feedback"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-primary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_manager_report_review_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5552))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-signature"></i>
            <div class="stat-value"><?php echo number_format($count_manager_report_review_variation); ?></div>
            <div class="stat-label">Manager (1st and 2nd Assessment Reports Review)</div>
          </div>
          <div class="status-chip">Step 7</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-manager-review-variation"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_pending_add_data_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5094))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-exclamation-circle"></i>
            <div class="stat-value"><?php echo number_format($count_pending_first_assessment_pending_add_data_variation); ?></div>
            <div class="stat-label">Pending ADD. DATA 1st Assessment</div>
          </div>
          <div class="status-chip">Step 8</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-pending-add-data-1st"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_first_assessment_add_data_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4796))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-alt"></i>
            <div class="stat-value"><?php echo number_format($count_pending_first_assessment_add_data_variation); ?></div>
            <div class="stat-label">ADD. DATA, Under 1st Assessment</div>
          </div>
          <div class="status-chip">Step 9</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-add-data-under-1st"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_pending_add_data_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5096))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-import"></i>
            <div class="stat-value"><?php echo number_format($count_pending_second_assessment_pending_add_data_variation); ?></div>
            <div class="stat-label">Pending ADD. DATA 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 10</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-pending-add-data-2nd"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_pending_second_assessment_add_data_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5095))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-file-export"></i>
            <div class="stat-value"><?php echo number_format($count_pending_second_assessment_add_data_variation); ?></div>
            <div class="stat-label">ADD. DATA, Under 2nd Assessment</div>
          </div>
          <div class="status-chip">Step 11</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-add-data-under-2nd"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-info">
        <a href="<?php echo esc_url(add_query_arg('exp_num', $count_pending_peer_review_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5097))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-users"></i>
            <div class="stat-value"><?php echo number_format($count_pending_peer_review_variation); ?></div>
            <div class="stat-label">Pending Peer Review</div>
          </div>
          <div class="status-chip">Step 12</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-pending-peer-review"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-info">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_under_approval_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(5099))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-check-double"></i>
            <div class="stat-value"><?php echo number_format($count_under_approval_variation); ?></div>
            <div class="stat-label">Passed Peer Review</div>
          </div>
          <div class="status-chip">Step 13</div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-passed-peer-review"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_withdrawn_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4787))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-ban"></i>
            <div class="stat-value"><?php echo number_format($count_withdrawn_variation); ?></div>
            <div class="stat-label">Cancelled</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-cancelled"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-secondary">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_rejected_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4789))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-times-circle"></i>
            <div class="stat-value"><?php echo number_format($count_rejected_variation); ?></div>
            <div class="stat-label">Rejected</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-rejected"></canvas>
        </div>
      </div>
      <div class="stat-card-wrapper card-success">
        <a href="<?php echo esc_url(add_query_arg('ab_num', $count_approved_variation, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4800))))); ?>" class="stat-card">
          <div class="stat-content">
            <i class="fas fa-check-circle"></i>
            <div class="stat-value"><?php echo number_format($count_approved_variation); ?></div>
            <div class="stat-label">Approved</div>
          </div>
        </a>
        <div class="chart-container">
          <canvas id="chart-variations-approved"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>
  </div>
</div>
        </div>
      </div>
      <script src="script.js"></script>
    </body>


<?php
// echo  '<div class="alert alert-danger"><p>' . __('Not allowed to access. Please login') . '</p></div>';
// wp_login_form( array( 'redirect' => get_permalink() ) );