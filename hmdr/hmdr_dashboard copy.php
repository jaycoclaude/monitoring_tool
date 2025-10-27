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
          $count_under_renewal += 1;
          /////////////////Select processing timelines//////////////////////
          $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage' and assessment_pathway='$assessment_procedure'");
          foreach ($applications_req_chart as $application_req_chart) {
            $number_of_days_chart = intval($application_req_chart->number_of_days);
            // echo $number_of_days_chart;
            $half_days = round($number_of_days_chart / 2);
            $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 60 / 60 / 24;
            //echo $days_processing.'<br>'.$number_of_days;
            //echo $days_processing;
            if ($days_processing_chart > $number_of_days_chart) {
              $total_not_assigned_delayed01 += 1;
              //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
              //echo $total_not_assigned_delayed;
              //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
            } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
              // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
              $total_not_assigned_ontime1 += 1;
              // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
              $total_not_assigned_tobedelayed01 += 1;
              //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
              // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
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

    //////////////////////////////////Applications control/////////////////////////////////////////////
    $count_under_approval = 0;
    $count_not_assigned = 0;
    $count_peer_review = 0;
    $count_queried = 0;
    $count_second_assessment = 0;
    $count_first_assessment = 0;
    $count_assessment = 0;
    $count_screening = 0;
    $count_all_applications = 0;
    $count_all_applications_under_process = 0;
    $count_not_assessed = 0;
    $count_registered = 0;
    $count_second_assessment_completed = 0;
    $count_onhold = 0;
    $count_pending_gmp = 0;
    $count_rejected = 0;
    $count_passed_peer_review_pending_gmp = 0;
    $count_pending_first_assessment_add_data = 0;
    $count_pending_second_assessment_add_data = 0;
    $count_backlog = 0;
    $count_awaiting_applicant_feedback = 0;
    $count_second_assessment_completed_letter_not_sent = 0;
    $count_pending_first_assessment = 0;
    $count_expired_applications = 0;
    $count_pending_first_assessment_pending = 0;
    $count_pending_second_assessment_pending = 0;
    $count_pending_first_assessment_pending_add_data = 0;
    $count_pending_second_assessment_pending_add_data = 0;
    $total_not_assigned_delayed = 0;
    $total_not_assigned_tobedelayed = 0;
    $total_not_assigned_ontime = 0;
    $total_not_assigned = 0;
    $number_of_days_chart = 0;
    $days_processing_chart = 0;
    $half_days = 0;
    $percentage_not_assigned_delayed = 0;
    $percentage_not_assigned_ontime = 0;
    $percentage_not_assigned_tobedelayed = 0;
    //////////////////

    //////////////////
   $stmt2 = $pdo->prepare("SELECT * FROM tbl_hm_applications");
$stmt2->execute();
$applications_req2 = $stmt2->fetchAll(PDO::FETCH_OBJ);

foreach ($applications_req2 as $application_req2) {

    // use null coalescing operator to prevent undefined property errors
    $hm_application_id       = $application_req2->hm_application_id ?? '';
    $reference_no            = $application_req2->reference_no ?? '';
    $tracking_no             = $application_req2->tracking_no ?? '';
    $date_submitted3         = $application_req2->date_submitted ?? '';
    $brand_name              = $application_req2->brand_name ?? '';
    $hm_generic_name         = $application_req2->hm_generic_name ?? '';
    $classification          = $application_req2->classification ?? '';
    $category                = $application_req2->category ?? '';
    $dosage_form             = $application_req2->dosage_form ?? '';
    $hm_applicant_email      = $application_req2->hm_applicant_email ?? '';
    $hm_mah_country          = $application_req2->hm_mah_country ?? '';
    $hm_ltr                  = $application_req2->hm_ltr ?? '';
    $hm_ltr_email            = $application_req2->hm_ltr_email ?? '';
    $hm_manufacturer_country = $application_req2->hm_manufacturer_country ?? '';
    $hm_manufacturer_email   = $application_req2->hm_manufacturer_email ?? '';
    $assessment_procedure    = $application_req2->assessment_procedure ?? '';
    $hm_registration_number  = $application_req2->hm_registration_number ?? '';
    $application_current_stage = $application_req2->application_current_stage ?? '';
    $application_process     = $application_req2->application_process ?? '';
    $gmp_status              = $application_req2->gmp_status ?? '';
    $date_screening          = $application_req2->date_screening ?? '';
    $date_first_assessment1  = $application_req2->date_first_assessment1 ?? '';
    $date_second_assessment1 = $application_req2->date_second_assessment1 ?? '';
    $date_query_assessment1  = $application_req2->date_query_assessment1 ?? '';
    $date_first_assessment2  = $application_req2->date_first_assessment2 ?? '';
    $date_second_assessment2 = $application_req2->date_second_assessment2 ?? '';
    $date_query_assessment2  = $application_req2->date_query_assessment2 ?? '';
    $date_first_assessment3  = $application_req2->date_first_assessment3 ?? '';
    $date_second_assessment3 = $application_req2->date_second_assessment3 ?? '';
    $date_query_assessment3  = $application_req2->date_query_assessment3 ?? '';
    $date_first_assessment4  = $application_req2->date_first_assessment4 ?? '';
    $date_second_assessment4 = $application_req2->date_second_assessment4 ?? '';
    $date_response1          = $application_req2->date_response1 ?? '';
    $date_response2          = $application_req2->date_response2 ?? '';
    $date_response3          = $application_req2->date_response3 ?? '';
      if ($application_current_stage == 6) {
        $count_under_approval += 1;
      } else if ($application_current_stage == 1) {



       // --- Initialize counters safely ---
if (!isset($count_not_assigned)) {
    $count_not_assigned = 0;
}
if (!isset($total_not_assigned_delayed1)) {
    $total_not_assigned_delayed1 = 0;
}
if (!isset($total_not_assigned_tobedelayed1)) {
    $total_not_assigned_tobedelayed1 = 0;
}
if (!isset($total_not_assigned_ontime1)) {
    $total_not_assigned_ontime1 = 0;
}
if (!isset($number_of_days)) {
    $number_of_days = 0;
}

// --- Increment counter for dashboard ---
$count_not_assigned += 1;

// --- Fetch timeline data (stmt21) ---
$sql21 = "SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway";
$stmt21 = $pdo->prepare($sql21);
$stmt21->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req_chart = $stmt21->fetchAll(PDO::FETCH_OBJ);

// --- Process timeline records ---
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days);
    $half_days = round($number_of_days_chart / 2);
    $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 86400;

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed1 += 1;
    } elseif ($days_processing_chart < ($number_of_days_chart / 2)) {
        $total_not_assigned_ontime1 += 1;
    } elseif ($days_processing_chart >= ($number_of_days_chart / 2) && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed1 += 1;
    }
}

// --- Calculate total ---
$total_not_assigned = $count_not_assigned;

// --- Compute timeline percentages ---
if ($total_not_assigned > 0) {
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed1 / $total_not_assigned) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed1 / $total_not_assigned) * 100,
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

    $percentage_not_assigned_delayed1 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed1 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime1 = $floored['ontime'];
} else {
    $percentage_not_assigned_delayed1 = 0;
    $percentage_not_assigned_tobedelayed1 = 0;
    $percentage_not_assigned_ontime1 = 0;
}

// --- Reminder timeline logic (without emails or inserts) ---
$sql22 = "SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway";
$stmt22 = $pdo->prepare($sql22);
$stmt22->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req3 = $stmt22->fetchAll(PDO::FETCH_OBJ);

// Get number of days for reminders
foreach ($applications_req3 as $application_req3) {
    $number_of_days = intval($application_req3->number_of_days);
}

$days_processing = (strtotime($datetoday) - strtotime($date_submitted)) / 86400;

// Instead of sending email or inserting notifications, 
// just log or flag for reminder internally
$reminder_needed = false;

if ($days_processing > $number_of_days) {
    // previously inserted reminders/notifications, now skipped
    $reminder_needed = true;
}

// Optionally store or display the reminder flag
// e.g., echo "Reminder needed: " . ($reminder_needed ? 'Yes' : 'No');

        //////////////End Reminder Alert Action////////////////////////////////////
      } else if ($application_current_stage == 5) {
        $count_peer_review += 1;
      } else if ($application_current_stage == 30) {
        $count_expired_applications += 1;
      }
      /*
else if($application_current_stage==9 || $application_current_stage==11 || $application_current_stage==1 || $application_current_stage==13)
{
	$count_queried+=1;
}
*/
      //else if($application_current_stage==8 || $application_current_stage==9 || $application_current_stage==11 || $application_current_stage==12 || $application_current_stage==13)
      else if ($application_current_stage == 8 || $application_current_stage == 11 || $application_current_stage == 12 || $application_current_stage == 13 || $application_current_stage == 9 || $application_current_stage == 18) {
 // --- Initialize counters safely ---
if (!isset($count_queried)) {
    $count_queried = 0;
}
if (!isset($total_not_assigned_delayed8)) {
    $total_not_assigned_delayed8 = 0;
}
if (!isset($total_not_assigned_tobedelayed8)) {
    $total_not_assigned_tobedelayed8 = 0;
}
if (!isset($total_not_assigned_ontime8)) {
    $total_not_assigned_ontime8 = 0;
}
if (!isset($send_notification_query)) {
    $send_notification_query = false;
}
if (!isset($start_date)) {
    $start_date = strtotime($datetoday);
}

// --- Increment queried counter ---
$count_queried += 1;

// --- Fetch timelines (stmt20) ---
$sql20 = "SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway";
$stmt20 = $pdo->prepare($sql20);
$stmt20->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req_chart = $stmt20->fetchAll(PDO::FETCH_OBJ);

// --- Process each timeline ---
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days);
    $half_days = round($number_of_days_chart / 2);

    // Determine date queried
    if (empty($date_second_assessment1) || $date_second_assessment1 === '0000-00-00') {
        $date_queried = $date_screening;
    } else {
        $date_queried = $date_second_assessment1;
    }

    // Calculate days processing
    $days_processing_chart = (strtotime($datetoday) - strtotime($date_queried)) / 86400;

    // Determine processing status
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed8 += 1;
    } elseif ($days_processing_chart < ($number_of_days_chart / 2)) {
        $total_not_assigned_ontime8 += 1;
    } elseif ($days_processing_chart >= ($number_of_days_chart / 2) && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed8 += 1;
    }
}

// --- Calculate percentages ---
$total_queried = $count_queried;

if ($total_queried > 0) {
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed8 / $total_queried) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed8 / $total_queried) * 100,
        'ontime' => ($total_not_assigned_ontime8 / $total_queried) * 100,
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

    $percentage_not_assigned_delayed8 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed8 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime8 = $floored['ontime'];
} else {
    $percentage_not_assigned_delayed8 = 0;
    $percentage_not_assigned_tobedelayed8 = 0;
    $percentage_not_assigned_ontime8 = 0;
}

// --- Staff check logic ---
$days_diff = '';
if ($application_current_stage == 18) {
    if (empty($date_query_assessment1)) {
        $end_date = strtotime($date_second_assessment1);
        $days_diff = ($start_date - $end_date) / 86400;
        $send_notification_query = ($days_diff > 0);
    }
}

      } else if ($application_current_stage == 4) {
        $count_assessment += 1;

        /////////////////Select processing timelines//////////////////////
// --- Select timeline info ---
// --- Initialize all counters to avoid undefined variable warnings ---
$total_not_assigned_delayed4 = 0;
$total_not_assigned_tobedelayed4 = 0;
$total_not_assigned_ontime4 = 0;
$total_assessment = 0;
$total_queried = isset($total_queried) ? $total_queried : 0;

// --- Fetch timeline records ---
$sql = "SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req_chart = $stmt->fetchAll(PDO::FETCH_OBJ);

foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days);
    $half_days = round($number_of_days_chart / 2);

    // --- Fetch assignment info ---
    $sql_ass = "SELECT * FROM tbl_application_assignment WHERE application_id = :app_id AND stage_id = :stage_id";
    $stmt_ass = $pdo->prepare($sql_ass);
    $stmt_ass->execute([
        ':app_id' => $hm_application_id,
        ':stage_id' => $application_current_stage
    ]);
    $applications_ass = $stmt_ass->fetchAll(PDO::FETCH_OBJ);

    if ($applications_ass) {
        foreach ($applications_ass as $application_ass) {
            $assignment_date = $application_ass->assignment_date;
        }
    } else {
        // Default to date_first_assessment1 if no assignment exists
        $assignment_date = $date_first_assessment1;
    }

    // --- Calculate days of processing ---
    $days_processing_chart = (strtotime($datetoday) - strtotime($assignment_date)) / 86400;

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed4++;
    } elseif ($days_processing_chart < ($number_of_days_chart / 2)) {
        $total_not_assigned_ontime4++;
    } elseif ($days_processing_chart >= ($number_of_days_chart / 2) && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed4++;
    }
}

// --- Use existing count_assessment if defined, else default to 0 ---
$total_assessment = isset($count_assessment) ? $count_assessment : 0;

// --- Compute safe percentages ---
if ($total_queried > 0 && $total_assessment > 0) {
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed4 / $total_assessment) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed4 / $total_assessment) * 100,
        'ontime' => ($total_not_assigned_ontime4 / $total_assessment) * 100,
    ];

    $floored = [];
    $remainders = [];
    $total_floor = 0;

    foreach ($raw_percentages as $key => $val) {
        $floored[$key] = floor($val);
        $remainders[$key] = $val - $floored[$key];
        $total_floor += $floored[$key];
    }

    // --- Normalize total to 100 ---
    $difference = 100 - $total_floor;
    arsort($remainders);
    foreach ($remainders as $key => $rem) {
        if ($difference <= 0) break;
        $floored[$key]++;
        $difference--;
    }

    $percentage_not_assigned_delayed4 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed4 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime4 = $floored['ontime'];
} else {
    // Default to 0% if no data available
    $percentage_not_assigned_delayed4 = 0;
    $percentage_not_assigned_tobedelayed4 = 0;
    $percentage_not_assigned_ontime4 = 0;
}


// --- End Select processing timelines ---

$total_assessment = $count_assessment;

// --- Compute percentages ---
if ($total_queried > 0) {
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed4 / $total_assessment) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed4 / $total_assessment) * 100,
        'ontime' => ($total_not_assigned_ontime4 / $total_assessment) * 100,
    ];

    $floored = [];
    $remainders = [];
    $total_floor = 0;

    foreach ($raw_percentages as $key => $val) {
        $floored[$key] = floor($val);
        $remainders[$key] = $val - $floored[$key];
        $total_floor += $floored[$key];
    }

    // --- Normalize total to 100 ---
    $difference = 100 - $total_floor;
    arsort($remainders);
    foreach ($remainders as $key => $rem) {
        if ($difference <= 0) break;
        $floored[$key]++;
        $difference--;
    }

    $percentage_not_assigned_delayed4 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed4 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime4 = $floored['ontime'];
} else {
    $percentage_not_assigned_delayed4 = 0;
    $percentage_not_assigned_tobedelayed4 = 0;
    $percentage_not_assigned_ontime4 = 0;
}

// --- Fetch timeline and assignment info for pending 2nd Assessment ---

$sql_req3 = "SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway";
$stmt_req3 = $pdo->prepare($sql_req3);
$stmt_req3->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req3 = $stmt_req3->fetchAll(PDO::FETCH_OBJ);

$number_of_days = 0;
foreach ($applications_req3 as $application_req3) {
    $number_of_days = intval($application_req3->number_of_days);
}

// --- Assignment info for stage 6 ---
$sql_ass2 = "SELECT * FROM tbl_application_assignment WHERE application_id = :app_id AND stage_id = 6";
$stmt_ass2 = $pdo->prepare($sql_ass2);
$stmt_ass2->execute([':app_id' => $hm_application_id]);
$applications_ass2 = $stmt_ass2->fetchAll(PDO::FETCH_OBJ);

if ($applications_ass2) {
    foreach ($applications_ass2 as $application_ass2) {
        $assignment_date = $application_ass2->assignment_date;
        $assigned_staff = $application_ass2->staff_id;
    }
} else {
    $assigned_staff = '';
    $assignment_date = $date_submitted;
}

// --- Optional follow-up logic can go here ---
// You can continue with business rules, logging, etc.
// (All email/notification handling has been fully removed)

        //////////////////////////
      } else if ($application_current_stage == 2 || $application_current_stage == 15) {
// --- Initialize counters safely ---
if (!isset($count_screening)) {
    $count_screening = 0;
}
if (!isset($total_not_assigned_delayed2)) {
    $total_not_assigned_delayed2 = 0;
}
if (!isset($total_not_assigned_tobedelayed2)) {
    $total_not_assigned_tobedelayed2 = 0;
}
if (!isset($total_not_assigned_ontime2)) {
    $total_not_assigned_ontime2 = 0;
}

// --- Increment screening counter ---
$count_screening += 1;

// --- Fetch timelines (stmt19) ---
$sql19 = "SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway";
$stmt19 = $pdo->prepare($sql19);
$stmt19->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req_chart = $stmt19->fetchAll(PDO::FETCH_OBJ);

// --- Process each timeline ---
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days);
    $half_days = round($number_of_days_chart / 2);
    $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 86400; // seconds to days

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed2 += 1;
    } elseif ($days_processing_chart < round($number_of_days_chart / 2)) {
        $total_not_assigned_ontime2 += 1;
    } elseif ($days_processing_chart >= ($number_of_days_chart / 2) && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed2 += 1;
    }
}

// --- Calculate percentages for graphs ---
$total_not_assigned = $count_screening;

if ($total_not_assigned > 0) {
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed2 / $total_not_assigned) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed2 / $total_not_assigned) * 100,
        'ontime' => ($total_not_assigned_ontime2 / $total_not_assigned) * 100,
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

    $percentage_not_assigned_delayed2 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed2 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime2 = $floored['ontime'];
} else {
    $percentage_not_assigned_delayed2 = 0;
    $percentage_not_assigned_tobedelayed2 = 0;
    $percentage_not_assigned_ontime2 = 0;
}

        /////////////////////////////////////////////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////
      } else if ($application_current_stage == 7) {
        $count_not_assessed += 1;
        /////////////////Select processing timelines//////////////////////
// --- Initialize counters to avoid undefined variable warnings ---
$total_not_assigned_delayed7 = 0;
$total_not_assigned_tobedelayed7 = 0;
$total_not_assigned_ontime7 = 0;
$total_not_assessed = isset($count_not_assessed) ? $count_not_assessed : 0;

// --- Fetch timelines using $stmt18 ---
$sql18 = "SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway";
$stmt18 = $pdo->prepare($sql18);
$stmt18->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req_chart = $stmt18->fetchAll(PDO::FETCH_OBJ);

// --- Loop through each timeline and calculate delays ---
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days);
    $half_days = round($number_of_days_chart / 2);

    // Compute days of processing safely
    $days_processing_chart = 0;
    if (!empty($datetoday) && !empty($date_screening)) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_screening)) / 86400;
    }

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed7++;
    } elseif ($days_processing_chart < ($number_of_days_chart / 2)) {
        $total_not_assigned_ontime7++;
    } elseif ($days_processing_chart >= ($number_of_days_chart / 2) && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed7++;
    }
}

// --- Compute percentages safely ---
if ($total_not_assessed > 0) {
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed7 / $total_not_assessed) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed7 / $total_not_assessed) * 100,
        'ontime' => ($total_not_assigned_ontime7 / $total_not_assessed) * 100,
    ];

    // Floor values and distribute remainders to total 100%
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

    // Final rounded percentages
    $percentage_not_assigned_delayed7 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed7 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime7 = $floored['ontime'];
} else {
    // Default if no data
    $percentage_not_assigned_delayed7 = 0;
    $percentage_not_assigned_tobedelayed7 = 0;
    $percentage_not_assigned_ontime7 = 0;
}


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

        $percentage_not_assigned_delayed7 = $floored['delayed'];
        $percentage_not_assigned_tobedelayed7 = $floored['tobedelayed'];
        $percentage_not_assigned_ontime7 = $floored['ontime'];
        ///////////////////////////////////////////////////////
        // echo $percentage_not_assigned_delayed7.'-'.$percentage_not_assigned_tobedelayed7.'-'.$percentage_not_assigned_ontime7.'<br>';
        /////////////////////////
        //////////////End Values for the graphs///////////////

      } else if ($application_current_stage == 38) {

        if (!isset($count_manager_report_review)) {
    $count_manager_report_review = 0;
}


$count_manager_report_review += 1;

// --- Select processing timelines ---
$sql23 = "SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway";
$stmt23 = $pdo->prepare($sql23);
$stmt23->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req_chart = $stmt23->fetchAll(PDO::FETCH_OBJ);

// Initialize counters to avoid undefined variable warnings
$total_not_assigned_delayed38 = 0;
$total_not_assigned_tobedelayed38 = 0;
$total_not_assigned_ontime38 = 0;

foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days);
    $half_days = round($number_of_days_chart / 2);
    $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 86400; // seconds to days

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed38 += 1;
    } elseif ($days_processing_chart < ($number_of_days_chart / 2)) {
        $total_not_assigned_ontime38 += 1;
    } elseif ($days_processing_chart >= ($number_of_days_chart / 2) && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed38 += 1;
    }
}

// --- Values for the graphs ---
$total_manager_report_review = $count_manager_report_review;

// Prevent division by zero
if ($total_manager_report_review > 0) {
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed38 / $total_manager_report_review) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed38 / $total_manager_report_review) * 100,
        'ontime' => ($total_not_assigned_ontime38 / $total_manager_report_review) * 100,
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

    $percentage_not_assigned_delayed38 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed38 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime38 = $floored['ontime'];
} else {
    $percentage_not_assigned_delayed38 = 0;
    $percentage_not_assigned_tobedelayed38 = 0;
    $percentage_not_assigned_ontime38 = 0;
}

        ///////////////////////////////////////////////////////
        // echo $percentage_not_assigned_delayed7.'-'.$percentage_not_assigned_tobedelayed7.'-'.$percentage_not_assigned_ontime7.'<br>';
        /////////////////////////
        //////////////End Values for the graphs///////////////

      } else if ($application_current_stage == 10) {
        $count_registered += 1;
      } else if ($application_current_stage == 18) {
        $count_second_assessment_completed += 1;
      } else if ($application_current_stage == 19) {
        $count_pending_gmp += 1;
      } else if ($application_current_stage == 14) {
        $count_rejected += 1;
      } else if ($application_current_stage == 16) {
        $count_onhold += 1;
      } else if ($application_current_stage == 20) {
        $count_passed_peer_review_pending_gmp += 1;
      } else if ($application_current_stage == 21 || $application_current_stage == 31 || $application_current_stage == 33) {
        $count_pending_first_assessment_add_data += 1;
        /////////////////Select processing timelines//////////////////////
// ✅ Initialize counters to avoid undefined warnings
$total_not_assigned_delayed21 = 0;
$total_not_assigned_tobedelayed21 = 0;
$total_not_assigned_ontime21 = 0;

// ✅ Prepare and execute query safely
$stmt9 = $pdo->prepare("
    SELECT number_of_days
    FROM tbl_timelines
    WHERE status_id = :status_id
      AND assessment_pathway = :assessment_pathway
");
$stmt9->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);

// ✅ Fetch all matching timelines
$applications_req_chart = $stmt9->fetchAll(PDO::FETCH_OBJ);

// ✅ Loop through each record
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // Calculate number of days processing (based on date_submitted)
    $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / (60 * 60 * 24);

    // ✅ Categorize timeline status
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed21 += 1;
        // Example: echo "Delayed: $days_processing_chart / $number_of_days_chart<br>";
    } 
    elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime21 += 1;
        // Example: echo "On Time: $days_processing_chart / $number_of_days_chart<br>";
    } 
    elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed21 += 1;
        // Example: echo "To Be Delayed: $days_processing_chart / $number_of_days_chart<br>";
    }
}

// ✅ Optional: Display summary
# echo "Delayed: $total_not_assigned_delayed21 | To Be Delayed: $total_not_assigned_tobedelayed21 | On Time: $total_not_assigned_ontime21<br>";

        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        $total_pending_first_assessment_add_data = $count_pending_first_assessment_add_data;
        // $total_not_assigned=$count_not_assigned;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////
        $raw_percentages = [
          'delayed' => ($total_not_assigned_delayed21 / $total_pending_first_assessment_add_data) * 100,
          'tobedelayed' => ($total_not_assigned_tobedelayed21 / $total_pending_first_assessment_add_data) * 100,
          'ontime' => ($total_not_assigned_ontime21 / $total_pending_first_assessment_add_data) * 100,
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

        $percentage_not_assigned_delayed21 = $floored['delayed'];
        $percentage_not_assigned_tobedelayed21 = $floored['tobedelayed'];
        $percentage_not_assigned_ontime21 = $floored['ontime'];
        ///////////////////////////////////////////////////////
        // echo $percentage_not_assigned_delayed7.'-'.$percentage_not_assigned_tobedelayed7.'-'.$percentage_not_assigned_ontime7.'<br>';
        /////////////////////////
        //////////////End Values for the graphs///////////////
      } else if ($application_current_stage == 3) {
        /////////////////Select Assignment///////////////
        $assignment_date = '';
        $assigned_staff = '';
$stmt5 = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway
");

// Execute with bound parameters
$stmt5->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);

// Fetch all results as objects (like WordPress get_results)
$applications_req3 = $stmt5->fetchAll(PDO::FETCH_OBJ);

// Loop through results
foreach ($applications_req3 as $application_req3) {
          $number_of_days = $application_req3->number_of_days;
        }
$stmt6 = $pdo->prepare("
    SELECT * 
    FROM tbl_application_assignment 
    WHERE application_id = :application_id 
      AND stage_id = :stage_id
");

// Execute with parameters
$stmt6->execute([
    ':application_id' => $hm_application_id,
    ':stage_id' => $application_current_stage
]);

// Fetch all matching rows
$applications_ass = $stmt6->fetchAll(PDO::FETCH_OBJ);

// Check and loop through results
if ($applications_ass) {
    foreach ($applications_ass as $application_ass) {
            $assignment_date = $application_ass->assignment_date;
            $assigned_staff = $application_ass->staff_id;
            ///////Select staff/////////////
          $stmt7 = $pdo->prepare("
    SELECT * 
    FROM tbl_staff 
    WHERE staff_id = :staff_id
");

// Execute with bound parameter
$stmt7->execute([
    ':staff_id' => $assigned_staff
]);

// Fetch results as objects (like WordPress)
$applications_staff = $stmt7->fetchAll(PDO::FETCH_OBJ);

// Loop through staff results
foreach ($applications_staff as $application_staff) {
              $assigned_staff_email = $application_staff->staff_email;
              $assigned_staff_names = $application_staff->staff_names;
              //echo ' '.$staff_email.'--'.$staff_names;
            }
            ///////End Select staff////////
          }
        } else {
          $assigned_staff = '';
          $assignment_date = $date_submitted;
        }

        /////////////////End Select Assignment////////////////

        $count_pending_first_assessment += 1;
        /////////////////Select processing timelines//////////////////////
        // ✅ Make sure these counters are initialized before the loop
$total_not_assigned_delayed3 = 0;
$total_not_assigned_tobedelayed3 = 0;
$total_not_assigned_ontime3 = 0;

// Prepare SQL statement
$stmt8 = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway
");

// Execute with bound parameters
$stmt8->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);

// Fetch results as objects (like WordPress get_results)
$applications_req_chart = $stmt8->fetchAll(PDO::FETCH_OBJ);

// Loop through results
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // Calculate days difference
    $days_processing_chart = (strtotime($datetoday) - strtotime($assignment_date)) / (60 * 60 * 24);

    // Categorize the record based on processing time
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed3 += 1;
        // Example: echo "Status: Delayed ($days_processing_chart vs $number_of_days_chart)<br>";
    } 
    elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime3 += 1;
        // Example: echo "Status: On Time ($days_processing_chart / $number_of_days_chart)<br>";
    } 
    elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed3 += 1;
        // Example: echo "Status: To Be Delayed ($days_processing_chart / $number_of_days_chart)<br>";
    }
}
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        $total_pending_first_assessment = $count_pending_first_assessment;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        if ($total_pending_first_assessment > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed3 / $total_pending_first_assessment) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed3 / $total_pending_first_assessment) * 100,
            'ontime' => ($total_not_assigned_ontime3 / $total_pending_first_assessment) * 100,
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
          $percentage_not_assigned_delayed3 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed3 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime3 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed3 = 0;
          $percentage_not_assigned_tobedelayed3 = 0;
          $percentage_not_assigned_ontime3 = 0;
        }
        ////////////////////////////////////////////////////////////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////

        /////////////Notification MA - Pending 1st Assessment////////////////
        /////////////////Select Assignment///////////////
        //$assignment_date = '';
        $assigned_staff = '';
 // ✅ 1️⃣ Get number_of_days from tbl_timelines
$stmt6 = $pdo->prepare("
    SELECT number_of_days 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway
");
$stmt6->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req3 = $stmt6->fetchAll(PDO::FETCH_OBJ);

$number_of_days = 0;
foreach ($applications_req3 as $application_req3) {
    $number_of_days = intval($application_req3->number_of_days ?? 0);
}

// ✅ 2️⃣ Get application assignment (stage_id = 5)
$stmt7 = $pdo->prepare("
    SELECT * 
    FROM tbl_application_assignment 
    WHERE application_id = :application_id 
      AND stage_id = 5
");
$stmt7->execute([':application_id' => $hm_application_id]);
$applications_ass = $stmt7->fetchAll(PDO::FETCH_OBJ);

if ($applications_ass && count($applications_ass) > 0) {
    foreach ($applications_ass as $application_ass) {
        $assignment_date = $application_ass->assignment_date;
        $assigned_staff = $application_ass->staff_id;

        /////// ✅ 3️⃣ Select staff /////////////
        $stmt7a = $pdo->prepare("
            SELECT staff_email, staff_names 
            FROM tbl_staff 
            WHERE staff_id = :staff_id
        ");
        $stmt7a->execute([':staff_id' => $assigned_staff]);
        $applications_staff = $stmt7a->fetchAll(PDO::FETCH_OBJ);

        foreach ($applications_staff as $application_staff) {
            $assigned_staff_email = $application_staff->staff_email;
            $assigned_staff_names = $application_staff->staff_names;
            // Example: echo "Assigned to: $assigned_staff_names ($assigned_staff_email)<br>";
        }
        /////// ✅ End Select staff /////////
    }
} else {
    // ✅ Default values when no assignment exists
    $assigned_staff = '';
    $assignment_date = $date_submitted;
}


        /////////////////End Select Assignment////////////////
        if ($assigned_staff <> '') {
          $days_processing = (strtotime($datetoday) - strtotime($assignment_date)) / 60 / 60 / 24;
          //$send_to='imushimiyimana@rwandafda.gov.rw';
          $send_to = $assigned_staff_email;
          $notification_to_category = 'Staff';
          if (($assessment_procedure == 'FULL ASSESSMENT') && (($days_processing - $number_of_days) == 5)) {

            //if(!($wpdb->get_results("SELECT * from tbl_hm_notifications where (notification_to='$dm_email') and (notification_month='$monthtoday' and notification_year='$yeartoday' and notification_week='$weektoday' and notification_type='$notification_type' and notification_to_category='$notification_to_category')")))
            $notification_type = 'Application Pending 1st Assessment';
            $subject2 = "Rwanda FDA notification - MA-Application Pending 1st Assessment" . " " . $reference_no;
            $message2 = "The application with Reference No. " . $reference_no . " is pending in your account for 1st assessment. Please login to the Monitoring tool for action.";
            //$headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" ."CC: irenemuto@gmail.com";
            $headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" . "CC: dgasana@rwandafda.gov.rw";

            if (!($wpdb->get_results("SELECT * from tbl_hm_notifications where notification_to='$send_to' and notification_date='$datetoday' and application_id='$hm_application_id' and notification_type='$notification_type' and notification_to_category='$notification_to_category'"))) {
              $sent = wp_mail($send_to, $subject2, strip_tags($message2), $headers2);
              //mail($to2,$subject2,$message2,$headers2);
              $wpdb->insert(
                'tbl_hm_notifications',
                array(
                  'notification_to' => $send_to,
                  'notification_subject' => $subject2,
                  'notification_message' => $message2,
                  'notification_headers' => $headers2,
                  'notification_date' => $datetoday,
                  'notification_week' => $weektoday,
                  'notification_month' => $monthtoday,
                  'notification_year' => $yeartoday,
                  'notification_type' => $notification_type,
                  'notification_to_category' => $notification_to_category,
                  'application_id' => $hm_application_id


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
            }
          }
          if (($assessment_procedure == 'ABRIDGED' || $assessment_procedure == 'RECOGNITION') && (($days_processing - $number_of_days) == 1)) {
            $notification_type = 'Application Pending 1st Assessment';
            $subject2 = "Rwanda FDA notification - MA-Application Pending 1st Assessment" . " " . $reference_no;
            $message2 = "The application with Reference No. " . $reference_no . " is pending in your account for 1st assessment. Please login to the Monitoring tool for action.";
            //$headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" ."CC: irenemuto@gmail.com";
            $headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" . "CC: ragiraneza@rwandafda.gov.rw,tuwambajineza@rwandafda.gov.rw";

            if (!($wpdb->get_results("SELECT * from tbl_hm_notifications where notification_to='$send_to' and notification_date='$datetoday' and application_id='$hm_application_id' and notification_type='$notification_type' and notification_to_category='$notification_to_category'"))) {
              $sent = wp_mail($send_to, $subject2, strip_tags($message2), $headers2);
              //mail($to2,$subject2,$message2,$headers2);
              $wpdb->insert(
                'tbl_hm_notifications',
                array(
                  'notification_to' => $send_to,
                  'notification_subject' => $subject2,
                  'notification_message' => $message2,
                  'notification_headers' => $headers2,
                  'notification_date' => $datetoday,
                  'notification_week' => $weektoday,
                  'notification_month' => $monthtoday,
                  'notification_year' => $yeartoday,
                  'notification_type' => $notification_type,
                  'notification_to_category' => $notification_to_category,
                  'application_id' => $hm_application_id


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
            }
          }
          ////////////////////////////
          if ($days_processing > $number_of_days) {

// --- Check if reminder already exists ---
$sql20 = "SELECT * FROM tbl_hm_notifications_reminder WHERE application_id = :application_id AND stage_id = 5";
$stmt20 = $pdo->prepare($sql20);
$stmt20->execute([':application_id' => $hm_application_id]);
$reminder_exists = $stmt20->fetchAll(PDO::FETCH_OBJ);

if (!$reminder_exists) {
    // Insert new reminder record
    $sqlInsertReminder = "
        INSERT INTO tbl_hm_notifications_reminder (
            application_id,
            staff_id,
            stage_id,
            reminder_date,
            reminder_status
        ) VALUES (
            :application_id,
            :staff_id,
            :stage_id,
            :reminder_date,
            :reminder_status
        )";
    $stmt21 = $pdo->prepare($sqlInsertReminder);
    $stmt21->execute([
        ':application_id' => $hm_application_id,
        ':staff_id' => $assigned_staff,
        ':stage_id' => 5,
        ':reminder_date' => $datetoday,
        ':reminder_status' => 'Reminder'
    ]);
}

// --- Fetch existing reminders for alerts/actions ---
$sqlFetchAlerts = "SELECT * FROM tbl_hm_notifications_reminder WHERE application_id = :application_id AND stage_id = 5";
$stmt22 = $pdo->prepare($sqlFetchAlerts);
$stmt22->execute([':application_id' => $hm_application_id]);
$applications_alert = $stmt22->fetchAll(PDO::FETCH_OBJ);

foreach ($applications_alert as $application_alert) {
    $reminder_date = $application_alert->reminder_date;
    $alert_date = $application_alert->alert_date;
    $reminder_status = $application_alert->reminder_status;

    if ($reminder_status === 'Reminder') {
        $days_in_reminder = (strtotime($datetoday) - strtotime($reminder_date)) / 86400;
        if ($days_in_reminder > 2) {
            // Update reminder -> Alert
            $sqlUpdateAlert = "
                UPDATE tbl_hm_notifications_reminder
                SET alert_date = :alert_date,
                    reminder_status = 'Alert'
                WHERE application_id = :application_id";
            $stmtAlert = $pdo->prepare($sqlUpdateAlert);
            $stmtAlert->execute([
                ':alert_date' => $datetoday,
                ':application_id' => $hm_application_id
            ]);
        }
    } elseif ($reminder_status === 'Alert') {
        $days_in_alert = (strtotime($datetoday) - strtotime($alert_date)) / 86400;
        if ($days_in_alert > 2) {
            // Update alert -> Action
            $sqlUpdateAction = "
                UPDATE tbl_hm_notifications_reminder
                SET action_date = :action_date,
                    reminder_status = 'Action'
                WHERE application_id = :application_id";
            $stmtAction = $pdo->prepare($sqlUpdateAction);
            $stmtAction->execute([
                ':action_date' => $datetoday,
                ':application_id' => $hm_application_id
            ]);
        }
    }
}

            //////////////////////////End Send Alert/////////////////////////////////
          }
          //$headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" ."CC: imushimiyimana@rwandafda.gov.rw,dgasana@rwandafda.gov.rw,ragiraneza@rwandafda.gov.rw,tuwambajineza@rwandafda.gov.rw,snkusi@rwandafda.gov.rw";

          ////////////End Notification MA - Pending 1st Assessment////////////////
        }
      } else if ($application_current_stage == 22  || $application_current_stage == 32 || $application_current_stage == 34) {
        $count_pending_second_assessment_add_data += 1;
        /////////////////Select processing timelines//////////////////////
      // ✅ Initialize counters to prevent undefined variable warnings
$total_not_assigned_delayed22 = 0;
$total_not_assigned_tobedelayed22 = 0;
$total_not_assigned_ontime22 = 0;

// ✅ Prepare & execute query safely
$stmt10 = $pdo->prepare("
    SELECT number_of_days 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway
");
$stmt10->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);

// ✅ Fetch results as objects (same as $wpdb->get_results)
$applications_req_chart = $stmt10->fetchAll(PDO::FETCH_OBJ);

// ✅ Loop through timelines to calculate status counts
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // Days since submission
    $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / (60 * 60 * 24);

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed22 += 1;
    } 
    elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime22 += 1;
    } 
    elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed22 += 1;
    }
}

// ✅ Compute graph values
$total_pending_second_assessment_add_data = $count_pending_second_assessment_add_data;

// Avoid division by zero
if (!empty($total_pending_second_assessment_pending) && $total_pending_second_assessment_pending > 0) {
    // Calculate raw (float) percentages
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed22 / $total_pending_second_assessment_add_data) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed22 / $total_pending_second_assessment_add_data) * 100,
        'ontime' => ($total_not_assigned_ontime22 / $total_pending_second_assessment_add_data) * 100,
    ];

    // Floor values and record remainders
    $floored = [];
    $remainders = [];
    $total_floor = 0;

    foreach ($raw_percentages as $key => $val) {
        $floored[$key] = floor($val);
        $remainders[$key] = $val - $floored[$key];
        $total_floor += $floored[$key];
    }

    // Adjust values so total = 100%
    $difference = 100 - $total_floor;
    arsort($remainders);

    foreach ($remainders as $key => $rem) {
        if ($difference <= 0) break;
        $floored[$key]++;
        $difference--;
    }

    // ✅ Final rounded percentage outputs
    $percentage_not_assigned_delayed22 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed22 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime22 = $floored['ontime'];

} else {
    // ✅ Default to zero when no data
    $percentage_not_assigned_delayed22 = 0;
    $percentage_not_assigned_tobedelayed22 = 0;
    $percentage_not_assigned_ontime22 = 0;
}

        ////////////////////////////////////////////////////////////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////
      } else if ($application_current_stage == 35) {
        $count_pending_second_assessment_pending += 1;
        /////////////////Select processing timelines//////////////////////
// ✅ Initialize counters to avoid undefined variable warnings
$total_not_assigned_delayed35 = 0;
$total_not_assigned_tobedelayed35 = 0;
$total_not_assigned_ontime35 = 0;

// ✅ Prepare and execute the query safely
$stmt12 = $pdo->prepare("
    SELECT number_of_days 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway
");
$stmt12->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);

// ✅ Fetch results
$applications_req_chart = $stmt12->fetchAll(PDO::FETCH_OBJ);

// ✅ Loop through results to calculate processing statistics
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // Calculate days since first assessment
    $days_processing_chart = (strtotime($datetoday) - strtotime($date_first_assessment1)) / (60 * 60 * 24);

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed35 += 1;
    } 
    elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime35 += 1;
    } 
    elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed35 += 1;
    }
}

// ✅ Prepare totals for graphs
$total_pending_second_assessment_pending = $count_pending_second_assessment_pending;

// ✅ Prevent division by zero
if (!empty($total_pending_second_assessment_pending) && $total_pending_second_assessment_pending > 0) {

    // Raw float percentages
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed35 / $total_pending_second_assessment_pending) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed35 / $total_pending_second_assessment_pending) * 100,
        'ontime' => ($total_not_assigned_ontime35 / $total_pending_second_assessment_pending) * 100,
    ];

    // Floor values and collect remainders
    $floored = [];
    $remainders = [];
    $total_floor = 0;
    foreach ($raw_percentages as $key => $val) {
        $floored[$key] = floor($val);
        $remainders[$key] = $val - $floored[$key];
        $total_floor += $floored[$key];
    }

    // Adjust so total = 100
    $difference = 100 - $total_floor;
    arsort($remainders);
    foreach ($remainders as $key => $rem) {
        if ($difference <= 0) break;
        $floored[$key]++;
        $difference--;
    }

    // ✅ Assign final rounded percentages
    $percentage_not_assigned_delayed35 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed35 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime35 = $floored['ontime'];

} else {
    // ✅ Default to zero when no data
    $percentage_not_assigned_delayed35 = 0;
    $percentage_not_assigned_tobedelayed35 = 0;
    $percentage_not_assigned_ontime35 = 0;
}

        ////////////////////////////////////////////////////////////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////
      } else if ($application_current_stage == 36) {
        $count_pending_first_assessment_pending_add_data += 1;
        /////////////////Select processing timelines//////////////////////
// ✅ Initialize counters to prevent "undefined variable" notices
$total_not_assigned_delayed36 = 0;
$total_not_assigned_tobedelayed36 = 0;
$total_not_assigned_ontime36 = 0;

// ✅ Prepare and execute the query
$stmt11 = $pdo->prepare("
    SELECT number_of_days 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway
");
$stmt11->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);

// ✅ Fetch results
$applications_req_chart = $stmt11->fetchAll(PDO::FETCH_OBJ);

// ✅ Loop through records to compute status counts
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // Calculate days between submission and today
    $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / (60 * 60 * 24);

    // Categorize progress by status
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed36 += 1;
    } 
    elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime36 += 1;
    } 
    elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed36 += 1;
    }
}

// ✅ Prepare totals for graph
$total_pending_first_assessment_pending_add_data = $count_pending_first_assessment_pending_add_data;

// ✅ Prevent division by zero
if (!empty($total_pending_second_assessment_pending) && $total_pending_second_assessment_pending > 0) {

    // Calculate raw (float) percentages
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed36 / $total_pending_first_assessment_pending_add_data) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed36 / $total_pending_first_assessment_pending_add_data) * 100,
        'ontime' => ($total_not_assigned_ontime36 / $total_pending_first_assessment_pending_add_data) * 100,
    ];

    // Floor values and compute remainders
    $floored = [];
    $remainders = [];
    $total_floor = 0;
    foreach ($raw_percentages as $key => $val) {
        $floored[$key] = floor($val);
        $remainders[$key] = $val - $floored[$key];
        $total_floor += $floored[$key];
    }

    // Adjust so total = 100%
    $difference = 100 - $total_floor;
    arsort($remainders);
    foreach ($remainders as $key => $rem) {
        if ($difference <= 0) break;
        $floored[$key]++;
        $difference--;
    }

    // ✅ Assign final graph percentages
    $percentage_not_assigned_delayed36 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed36 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime36 = $floored['ontime'];

} else {
    // ✅ Default to zeros when no data
    $percentage_not_assigned_delayed36 = 0;
    $percentage_not_assigned_tobedelayed36 = 0;
    $percentage_not_assigned_ontime36 = 0;
}

        ////////////////////////////////////////////////////////////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////
      } else if ($application_current_stage == 37) {
        $count_pending_second_assessment_pending_add_data += 1;
        /////////////////Select processing timelines//////////////////////
        // --- Initialize counters to prevent undefined variable warnings ---
$total_not_assigned_delayed37 = 0;
$total_not_assigned_tobedelayed37 = 0;
$total_not_assigned_ontime37 = 0;
$total_pending_second_assessment_pending_add_data = isset($count_pending_second_assessment_pending_add_data) ? $count_pending_second_assessment_pending_add_data : 0;
$total_pending_second_assessment_pending = isset($total_pending_second_assessment_pending) ? $total_pending_second_assessment_pending : 0;

// --- Fetch timelines using PDO ($stmt19) ---
$sql19 = "SELECT * FROM tbl_timelines WHERE status_id = :status_id AND assessment_pathway = :assessment_pathway";
$stmt19 = $pdo->prepare($sql19);
$stmt19->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);
$applications_req_chart = $stmt19->fetchAll(PDO::FETCH_OBJ);

// --- Process each result ---
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days);
    $half_days = round($number_of_days_chart / 2);

    // Compute days processing
    $days_processing_chart = 0;
    if (!empty($datetoday) && !empty($date_submitted)) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 86400;
    }

    // Categorize based on days
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed37++;
    } elseif ($days_processing_chart < ($number_of_days_chart / 2)) {
        $total_not_assigned_ontime37++;
    } elseif ($days_processing_chart >= ($number_of_days_chart / 2) && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed37++;
    }
}

// --- Compute percentage breakdown for graphs ---
if ($total_pending_second_assessment_pending > 0 && $total_pending_second_assessment_pending_add_data > 0) {
    $raw_percentages = [
        'delayed' => ($total_not_assigned_delayed37 / $total_pending_second_assessment_pending_add_data) * 100,
        'tobedelayed' => ($total_not_assigned_tobedelayed37 / $total_pending_second_assessment_pending_add_data) * 100,
        'ontime' => ($total_not_assigned_ontime37 / $total_pending_second_assessment_pending_add_data) * 100,
    ];

    // Round values while keeping total = 100
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

    // Assign final rounded percentages
    $percentage_not_assigned_delayed37 = $floored['delayed'];
    $percentage_not_assigned_tobedelayed37 = $floored['tobedelayed'];
    $percentage_not_assigned_ontime37 = $floored['ontime'];
} else {
    $percentage_not_assigned_delayed37 = 0;
    $percentage_not_assigned_tobedelayed37 = 0;
    $percentage_not_assigned_ontime37 = 0;
}

        ////////////////////////////////////////////////////////////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////
      }
      //////////////Count backlogs//////////////////
      /*
if($application_current_stage<>10)
{
	$count_backlog+=1;
}
*/
      /////////////End Count backlogs//////////////////
      if ($application_current_stage <> 10 && $application_current_stage <> 14 && $application_current_stage <> 28 && $application_current_stage <> 30 && $application_current_stage <> 23 && $application_current_stage <> 16) {
        $count_all_applications_under_process += 1;
        //////////////////////Count backlogs//////////////////
        $assessment_procedure = $application_req2->assessment_procedure;
        $date_submitted = $application_req2->date_submitted;
        $date_query_assessment1 = $application_req2->date_second_assessment1;
        $date_response1 = $application_req2->date_response1;
        $date_query_assessment2 = $application_req2->date_query_assessment2;
        $date_response2 = $application_req2->date_response2;
        $date_query_assessment3 = $application_req2->date_query_assessment3;
        $date_response3 = $application_req2->date_response3;


        if (!isValidDate($date_submitted)) {
          continue; // skip apps with no submission date
        }

        $days_processing_backlog_round0 = getDaysBetween($date_submitted, $datetoday);
        $days_processing_backlog_round1 = getDaysBetween($date_submitted, $date_query_assessment1);
        $days_processing_backlog_round2 = getDaysBetween($date_response1, $date_query_assessment2);
        $days_processing_backlog_round3 = getDaysBetween($date_response2, $date_query_assessment3);
        $days_processing_backlog_round4 = getDaysBetween($date_response3, $datetoday);


        if ($days_processing_backlog_round1 > 0) {
          $days_processing_backlog = $days_processing_backlog_round1 + $days_processing_backlog_round2 + $days_processing_backlog_round3 + $days_processing_backlog_round4;
        } else {
          $days_processing_backlog = $days_processing_backlog_round0;
        }
        // if ($days_processing_backlog > 365 && $assessment_procedure == "FULL ASSESSMENT") {
        //     $count_backlog += 1;
        // } elseif ($days_processing_backlog > 90 && ($assessment_procedure == "ABRIDGED" || $assessment_procedure == "RECOGNITION")) {
        //     $count_backlog += 1;
        // }
        $is_backlog = false;
        if ($assessment_procedure == "FULL ASSESSMENT" && $days_processing_backlog > 365) {
          $is_backlog = true;
        } elseif (($assessment_procedure == "ABRIDGED" || $assessment_procedure == "RECOGNITION") && $days_processing_backlog > 90) {
          $is_backlog = true;
        }

        if ($is_backlog) {
          $count_backlog += 1;
        }
        ////////////////////// End Count backlogs ////////////////////
        //}
        //////////////////////End Count backlogs//////////////////
      }
      /////////////////////////////////
      /*if($application_current_stage==18 || $application_current_stage==11 ||  $application_current_stage==12 || $application_current_stage==13)
{

if(($date_first_assessment3<>'' && $date_first_assessment3<>'0000-00-00') && ($date_query_assessment3<>'' && $date_query_assessment3<>'0000-00-00') && ($date_response3=='' || $date_response3=='0000-00-00'))
{
	$count_awaiting_applicant_feedback+=1;
}
if(($date_first_assessment2<>'' && $date_first_assessment2<>'0000-00-00') && ($date_query_assessment2<>'' && $date_query_assessment2<>'0000-00-00') && ($date_response2=='' || $date_response2=='0000-00-00'))
{
	$count_awaiting_applicant_feedback+=1;
}
if(($date_first_assessment1<>'' && $date_first_assessment1<>'0000-00-00') && ($date_query_assessment1<>'' && $date_query_assessment1<>'0000-00-00') && ($date_response1=='' || $date_response1=='0000-00-00'))
{
	$count_awaiting_applicant_feedback+=1;
}
}*/
      if ($application_current_stage == 25 || $application_current_stage == 26 ||  $application_current_stage == 27  || $application_current_stage == 17 || $application_current_stage == 29 || $application_current_stage == 39) {
        $count_awaiting_applicant_feedback += 1;

        /////////////////Select processing timelines//////////////////////
$stmt3 = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway
");

// Bind parameters
$stmt3->execute([
    ':status_id' => $application_current_stage,
    ':assessment_pathway' => $assessment_procedure
]);

// Fetch results as objects
$applications_req_chart = $stmt3->fetchAll(PDO::FETCH_OBJ);

// ✅ Initialize all counters before loop
$total_not_assigned_delayed25 = 0;
$total_not_assigned_tobedelayed25 = 0;
$total_not_assigned_ontime25 = 0;
$total_awaiting_applicant_feedback = 0;

// Loop through results
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    if ($application_current_stage == 25 || $application_current_stage == 17 || $application_current_stage == 29) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_query_assessment1)) / 86400;
    } elseif ($application_current_stage == 26) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_query_assessment2)) / 86400;
    } elseif ($application_current_stage == 27) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_query_assessment3)) / 86400;
    } elseif ($application_current_stage == 39) {
        if (empty($date_query_assessment1) || $date_query_assessment1 == '0000-00-00') {
            $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 86400;
        } else {
            $days_processing_chart = (strtotime($datetoday) - strtotime($date_query_assessment1)) / 86400;
        }
    } else {
        $days_processing_chart = 0; // default fallback
    }

    // Classification logic
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed25++;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime25++;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed25++;
    }


// After the loop
$total_awaiting_applicant_feedback = $count_awaiting_applicant_feedback ?? 0;

// ✅ Safe division (avoid division by zero)
if ($total_awaiting_applicant_feedback > 0) {
    $raw_percentages = [
        'delayed'      => ($total_not_assigned_delayed25 / $total_awaiting_applicant_feedback) * 100,
        'tobedelayed'  => ($total_not_assigned_tobedelayed25 / $total_awaiting_applicant_feedback) * 100,
        'ontime'       => ($total_not_assigned_ontime25 / $total_awaiting_applicant_feedback) * 100,
    ];
} else {
    $raw_percentages = ['delayed' => 0, 'tobedelayed' => 0, 'ontime' => 0];
}


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
          $percentage_not_assigned_delayed25 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed25 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime25 = $floored['ontime'];
        }
        ////////////////////////////////////////////////////////////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////



        ///////////////////////////////////////////////
      }

      //////////////////////////////////
      /////////////////////////////////
      /*if((($date_first_assessment3<>'' && $date_first_assessment3<>'0000-00-00') && ($date_query_assessment3=='' || $date_query_assessment3=='0000-00-00')) || (($date_first_assessment2<>'' && $date_first_assessment2<>'0000-00-00') && ($date_query_assessment2=='' || $date_query_assessment2=='0000-00-00')) || (($date_first_assessment1<>'' && $date_first_assessment1<>'0000-00-00') && ($date_query_assessment1=='' || $date_query_assessment1=='0000-00-00')))
{
//$count_second_assessment_completed_letter_not_sent+=1;
$count_second_assessment_completed_letter_not_sent=145;

}
*/
      if ($application_current_stage == 8 || $application_current_stage == 18 || $application_current_stage == 11 || $application_current_stage == 12 || $application_current_stage == 13 || $application_current_stage == 9) {

        ///////////////////////////////////
        $count_second_assessment_completed_letter_not_sent += 1;
        ///////////////////////////////////
      }
      ////////////////////////////////

      $count_all_applications += 1;
    }

    //$count_all_applications=3250;
    /*
$count_pending_first_assessment_add_data=53;
$count_pending_second_assessment_add_data=46;
$count_registered=1925;
$count_peer_review=38;
$count_under_approval=76;
*/

    ////////////////////////////////////End Applications control/////////////////////////////////////////
    ////////////////////Variations control////////////////////////
    $count_all_applications_variation = 0;
    $count_approved_variation = 0;
    $count_under_assessment_variation = 0;
    $count_queried_variation = 0;
    $count_withdrawn_variation = 0;
    $count_rejected_variation = 0;
    $count_second_assessment_completed_letter_not_sent_variation = 0;
    $count_awaiting_applicant_feedback_variation = 0;
    $count_under_assessment_variation = 0;
    $count_pending_peer_review_variation = 0;
    $count_pending_first_assessment_add_data_variation = 0;
    $count_pending_second_assessment_add_data_variation = 0;
    $count_pending_second_assessment_variation = 0;
    $count_pending_first_assessment_variation = 0;
    $count_under_approval_variation = 0;
    $count_manager_report_review_variation = 0;
    // $percentage_not_assigned_delayed001 = 0;
    // $percentage_not_assigned_tobedelayed001 = 0;
    // $percentage_not_assigned_ontime001 = 0;
    // $percentage_not_assigned_delayed002 = 0;
    // $percentage_not_assigned_tobedelayed002 = 0;
    // $percentage_not_assigned_ontime002 = 0;
    // $percentage_not_assigned_delayed004 = 0;
    // $percentage_not_assigned_tobedelayed004 = 0;
    // $percentage_not_assigned_ontime004 = 0;
    // $percentage_not_assigned_delaye005 = 0;
    // $percentage_not_assigned_tobedelayed005 = 0;
    // $percentage_not_assigned_ontime005 = 0;
    // $percentage_not_assigned_delayed006 = 0;
    // $percentage_not_assigned_tobedelayed006 = 0;
    // $percentage_not_assigned_ontime006 = 0;
    // $percentage_not_assigned_delayed007 = 0;
    // $percentage_not_assigned_tobedelayed007 = 0;
    // $percentage_not_assigned_ontime007 = 0;
    // $percentage_not_assigned_delayed008 = 0;
    // $percentage_not_assigned_tobedelayed008 = 0;
    // $percentage_not_assigned_ontime008 = 0;
    // $percentage_not_assigned_delayed009 = 0;
    // $percentage_not_assigned_tobedelayed009 = 0;
    // $percentage_not_assigned_ontime009 = 0;
    //     $percentage_not_assigned_delayed0010 = 0;
    // $percentage_not_assigned_tobedelayed0010 = 0;
    // $percentage_not_assigned_ontime0010 = 0;

   // Prepare SQL statement
$stmt4 = $pdo->prepare("SELECT * FROM tbl_hm_applications_variation");
$stmt4->execute();

// Fetch all rows as objects (like WordPress get_results)
$applications_req3 = $stmt4->fetchAll(PDO::FETCH_OBJ);

// Loop through results
foreach ($applications_req3 as $application_req3) {

    $hm_application_id = $application_req3->hm_application_id ?? '';
    $hm_application_number = $application_req3->hm_application_number ?? '';
    $hm_register_id = $application_req3->hm_register_id ?? '';
    $tracking_no = $application_req3->tracking_no ?? '';
    $date_submitted3 = $application_req3->date_submitted ?? '';
    $brand_name = $application_req3->brand_name ?? '';
    $hm_generic_name = $application_req3->hm_generic_name ?? '';
    $dosage_form = $application_req3->dosage_form ?? '';

    // These may not exist in some records — use safe defaults
    $hm_mah_email = $application_req3->hm_mah_email ?? '';
    $hm_mah_country = $application_req3->hm_mah_country ?? '';
    $hm_ltr = $application_req3->hm_ltr ?? '';
    $hm_ltr_email = $application_req3->hm_ltr_email ?? '';
    $hm_manufacturer_country = $application_req3->hm_manufacturer_country ?? '';
    $hm_manufacturer_email = $application_req3->hm_manufacturer_email ?? '';

    $assessment_procedure = $application_req3->assessment_procedure ?? '';
    $hm_registration_number = $application_req3->hm_registration_number ?? '';
    $application_current_stage3 = $application_req3->application_current_stage ?? '';
    $application_process = $application_req3->application_process ?? '';
    $gmp_status = $application_req3->gmp_status ?? '';
    $assessment_procedure3 = $application_req3->assessment_procedure ?? '';

    // Assessment and query dates
    $date_first_assessment1 = $application_req3->date_first_assessment1 ?? '';
    $date_second_assessment1 = $application_req3->date_second_assessment1 ?? '';
    $date_query_assessment1 = $application_req3->date_query_assessment1 ?? '';
    $date_first_assessment2 = $application_req3->date_first_assessment2 ?? '';
    $date_second_assessment2 = $application_req3->date_second_assessment2 ?? '';
    $date_query_assessment2 = $application_req3->date_query_assessment2 ?? '';
    $date_first_assessment3 = $application_req3->date_first_assessment3 ?? '';
    $date_second_assessment3 = $application_req3->date_second_assessment3 ?? '';
    $date_query_assessment3 = $application_req3->date_query_assessment3 ?? '';
    $date_first_assessment4 = $application_req3->date_first_assessment4 ?? '';
    $date_second_assessment4 = $application_req3->date_second_assessment4 ?? '';

    // Responses and certificate details
    $date_response1 = $application_req3->date_response1 ?? '';
    $date_response2 = $application_req3->date_response2 ?? '';
    $date_response3 = $application_req3->date_response3 ?? '';
    $certificate_issue_date = $application_req3->certificate_issue_date ?? '';
    $certificate_expiry_date = $application_req3->certificate_expiry_date ?? '';
    $product_registration_number = $application_req3->product_registration_number ?? '';
    $withdraw_date = $application_req3->withdraw_date ?? '';
    $variation_category = $application_req3->variation_category ?? '';
    $variation_type3 = $application_req3->variation_type ?? '';
    $comments = $application_req3->comments ?? '';
    $final_decision = $application_req3->final_decision ?? '';
    $date_final_decision = $application_req3->date_final_decision ?? '';

    // Withdraw assessment notification dates
    $date_notification_withdraw_assessment1 = $application_req3->date_notification_withdraw_assessment1 ?? '';
    $date_notification_withdraw_assessment2 = $application_req3->date_notification_withdraw_assessment2 ?? '';
    $date_notification_withdraw_assessment3 = $application_req3->date_notification_withdraw_assessment3 ?? '';

    // Outcomes
    $outcome_assessment1 = $application_req3->outcome_assessment1 ?? '';
    $outcome_assessment2 = $application_req3->outcome_assessment2 ?? '';
    $outcome_assessment3 = $application_req3->outcome_assessment3 ?? '';
    $outcome_assessment4 = $application_req3->outcome_assessment4 ?? '';

    // Fallback if variation_type is NULL or empty
    if (empty($variation_type3)) {
        $variation_type3 = 'Vmin';
    }
    

      if ($application_current_stage3 == 5) {
        $count_pending_peer_review_variation += 1;
      }
      if ($application_current_stage3 == 25 || $application_current_stage3 == 26 ||  $application_current_stage3 == 27  || $application_current_stage3 == 17 || $application_current_stage3 == 29 || $application_current_stage3 == 39) {
        $count_awaiting_applicant_feedback_variation += 1;
        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage3' and assessment_pathway='$variation_type3' and process_type='Variation'");
        foreach ($applications_req_chart as $application_req_chart) {
          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed0025 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime05 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed0025 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned_variation = $count_awaiting_applicant_feedback_variation;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_variation = $count_awaiting_applicant_feedback_variation;

        if ($total_not_assigned_variation > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed0025 / $total_not_assigned_variation) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed005 / $total_not_assigned_variation) * 100,
            'ontime' => ($total_not_assigned_ontime0025 / $total_not_assigned_variation) * 100,
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
          $percentage_not_assigned_delayed05 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed05 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime0025 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed0025 = 0;
          $percentage_not_assigned_tobedelayed0025 = 0;
          $percentage_not_assigned_ontime0025 = 0;
        }
        ////////////////////////////////////////////////////////////////////
      }
      if ($application_current_stage3 == 18 || $application_current_stage3 == 11 || $application_current_stage3 == 12 || $application_current_stage3 == 13 || $application_current_stage3 == 9 || $application_current_stage3 == 8 || $application_current_stage3 == 19) {
        $count_second_assessment_completed_letter_not_sent_variation += 1;

        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage3' and assessment_pathway='$variation_type3' and process_type='Variation'");
        foreach ($applications_req_chart as $application_req_chart) {
          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed004 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime004 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed004 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        $total_not_assigned_variation = $count_second_assessment_completed_letter_not_sent_variation;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_variation = $count_second_assessment_completed_letter_not_sent_variation;

        if ($total_not_assigned_variation > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed004 / $total_not_assigned_variation) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed004 / $total_not_assigned_variation) * 100,
            'ontime' => ($total_not_assigned_ontime004 / $total_not_assigned_variation) * 100,
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
          $percentage_not_assigned_delayed004 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed004 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime004 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed004 = 0;
          $percentage_not_assigned_tobedelayed004 = 0;
          $percentage_not_assigned_ontime004 = 0;
        }
        ////////////////////////////////////////////////////////////////////
      }
      if ($application_current_stage3 == 24 || $application_current_stage3 == 10) {
        $count_approved_variation += 1;
      }
      if ($application_current_stage3 == 21 || $application_current_stage3 == 31 || $application_current_stage3 == 33) {

        $count_pending_first_assessment_add_data_variation += 1;

        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage3' and assessment_pathway='$variation_type3' and process_type='Variation'");
        foreach ($applications_req_chart as $application_req_chart) {

          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed0021 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime0021 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed0021 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned = $count_not_assigned;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_variation = $count_pending_first_assessment_add_data_variation;

        if ($total_not_assigned_variation > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed0021 / $total_not_assigned_variation) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed0021 / $total_not_assigned_variation) * 100,
            'ontime' => ($total_not_assigned_ontime0021 / $total_not_assigned_variation) * 100,
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
          $percentage_not_assigned_delayed0021 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed0021 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime0021 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed0021 = 0;
          $percentage_not_assigned_tobedelayed0021 = 0;
          $percentage_not_assigned_ontime0021 = 0;
        }
        ////////////////////////////////////////////////////////////////////
      }
      if ($application_current_stage3 == 35) {
        $count_pending_second_assessment_pending_variation += 1;

        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage3' and assessment_pathway='$variation_type3' and process_type='Variation'");
        foreach ($applications_req_chart as $application_req_chart) {

          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed0035 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime0035 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed0035 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned = $count_not_assigned;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_variation = $count_pending_second_assessment_pending_variation;

        if ($total_not_assigned_variation > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed0035 / $total_not_assigned_variation) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed0035 / $total_not_assigned_variation) * 100,
            'ontime' => ($total_not_assigned_ontime0035 / $total_not_assigned_variation) * 100,
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
          $percentage_not_assigned_delayed0035 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed0035 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime0035 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed0035 = 0;
          $percentage_not_assigned_tobedelayed0035 = 0;
          $percentage_not_assigned_ontime0035 = 0;
        }
        ////////////////////////////////////////////////////////////////////
      }
      if ($application_current_stage3 == 36) {
        $count_pending_first_assessment_pending_add_data_variation += 1;

        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage3' and assessment_pathway='$variation_type3' and process_type='Variation'");
        foreach ($applications_req_chart as $application_req_chart) {

          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed0036 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime0036 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed0036 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned = $count_not_assigned;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_variation = $count_pending_first_assessment_pending_add_data_variation;

        if ($total_not_assigned_variation > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed0036 / $total_not_assigned_variation) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed0036 / $total_not_assigned_variation) * 100,
            'ontime' => ($total_not_assigned_ontime0036 / $total_not_assigned_variation) * 100,
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
          $percentage_not_assigned_delayed0036 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed003 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime0036 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed0036 = 0;
          $percentage_not_assigned_tobedelayed0036 = 0;
          $percentage_not_assigned_ontime0036 = 0;
        }
        ////////////////////////////////////////////////////////////////////
      }
      if ($application_current_stage3 == 37) {
        $count_pending_second_assessment_pending_add_data_variation += 1;

        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage3' and assessment_pathway='$variation_type3' and process_type='Variation'");
        foreach ($applications_req_chart as $application_req_chart) {

          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed0037 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime0037 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed0037 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned = $count_not_assigned;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_variation = $count_pending_second_assessment_pending_add_data_variation;

        if ($total_not_assigned_variation > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed0037 / $total_not_assigned_variation) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed0037 / $total_not_assigned_variation) * 100,
            'ontime' => ($total_not_assigned_ontime0037 / $total_not_assigned_variation) * 100,
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
          $percentage_not_assigned_delayed0037 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed0037 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime0037 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed0037 = 0;
          $percentage_not_assigned_tobedelayed0037 = 0;
          $percentage_not_assigned_ontime0037 = 0;
        }
        ////////////////////////////////////////////////////////////////////
      }

      if ($application_current_stage3 == 22) {
        $count_pending_second_assessment_add_data_variation += 1;

        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage' and assessment_pathway='$assessment_procedure'");
        foreach ($applications_req_chart as $application_req_chart) {
          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed009 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime009 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed009 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
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
            'delayed' => ($total_not_assigned_delayed009 / $total_not_assigned) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed009 / $total_not_assigned) * 100,
            'ontime' => ($total_not_assigned_ontime009 / $total_not_assigned) * 100,
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
          $percentage_not_assigned_delayed009 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed009 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime009 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed009 = 0;
          $percentage_not_assigned_tobedelayed009 = 0;
          $percentage_not_assigned_ontime009 = 0;
        }
        ////////////////////////////////////////////////////////////////////


      }
      if ($application_current_stage3 == 23) {
        $count_withdrawn_variation += 1;
      }
      if ($application_current_stage3 == 14) {
        $count_rejected_variation += 1;
      }
      if ($application_current_stage3 == 4) {
        $count_pending_second_assessment_variation += 1;

        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage3' and assessment_pathway='$variation_type3' and process_type='Variation'");
        foreach ($applications_req_chart as $application_req_chart) {
          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed002 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime002 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed002 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        $total_not_assigned_variation = $count_pending_second_assessment_variation;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_variation = $count_pending_second_assessment_variation;

        if ($total_not_assigned_variation > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed002 / $total_not_assigned_variation) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed002 / $total_not_assigned_variation) * 100,
            'ontime' => ($total_not_assigned_ontime002 / $total_not_assigned_variation) * 100,
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
          $percentage_not_assigned_delayed002 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed002 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime002 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed002 = 0;
          $percentage_not_assigned_tobedelayed002 = 0;
          $percentage_not_assigned_ontime002 = 0;
        }
        ////////////////////////////////////////////////////////////////////
      }
      if ($application_current_stage3 == 3) {
        $count_pending_first_assessment_variation += 1;
        // echo $count_pending_first_assessment_variation. '<br>';
        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage3' and assessment_pathway='$variation_type3' and process_type='Variation'");
        foreach ($applications_req_chart as $application_req_chart) {

          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed003 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime003 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed003 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned = $count_not_assigned;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_variation = $count_pending_first_assessment_variation;

        if ($total_not_assigned_variation > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed003 / $total_not_assigned_variation) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed003 / $total_not_assigned_variation) * 100,
            'ontime' => ($total_not_assigned_ontime003 / $total_not_assigned_variation) * 100,
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
          $percentage_not_assigned_delayed003 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed003 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime003 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed003 = 0;
          $percentage_not_assigned_tobedelayed003 = 0;
          $percentage_not_assigned_ontime003 = 0;
        }
        ////////////////////////////////////////////////////////////////////

      }
      if ($application_current_stage3 == 6) {
        $count_under_approval_variation += 1;
      }
      if ($application_current_stage3 == 7) {
        $count_not_assessed_variation += 1;

        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage3' and assessment_pathway='$variation_type3' and process_type='Variation'");
        foreach ($applications_req_chart as $application_req_chart) {

          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed007 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime007 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed007 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned = $count_not_assigned;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_variation = $count_not_assessed_variation;

        if ($total_not_assigned_variation > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed007 / $total_not_assigned_variation) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed007 / $total_not_assigned_variation) * 100,
            'ontime' => ($total_not_assigned_ontime007 / $total_not_assigned_variation) * 100,
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
          $percentage_not_assigned_delayed007 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed007 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime007 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed007 = 0;
          $percentage_not_assigned_tobedelayed007 = 0;
          $percentage_not_assigned_ontime007 = 0;
        }
        ////////////////////////////////////////////////////////////////////

      }
      if ($application_current_stage3 == 38) {
        $count_manager_report_review_variation += 1;
      }



    //  -------------------------------------------------------------------------------------------------------------------
      ////////////////Renewal Applications//////////////////////


          //$applications_req4 = $wpdb->get_results("SELECT * from tbl_hm_applications_renewal");
    $stmt4 = $pdo->prepare("SELECT * FROM tbl_hm_applications_renewal");
$stmt4->execute();

// Fetch all rows as objects (like WordPress get_results)
$applications_req4 = $stmt4->fetchAll(PDO::FETCH_OBJ);

$count_approved_renewal = 0;
$count_all_applications_renewal = 0;
$count_pending_gmp_renewal = 0;
 $count_pending_peer_review_renewal = 0;
$count_pending_first_assessment_renewal = 0;
$count_pending_first_assessment_add_data_renewal = 0;
$count_under_approval_renewal = 0;
$count_manager_report_review_renewal = 0;
$count_not_assessed_renewal = 0;
$count_rejected_renewal = 0;
$count_withdrawn_renewal = 0;


// Loop through results
foreach ($applications_req4 as $application_req4) {

    $hm_application_id4 = $application_req4->hm_application_id ?? '';
    $reference_no4 = $application_req4->reference_no ?? '';
    $tracking_no4 = $application_req4->tracking_no ?? '';
    $date_submitted4 = $application_req4->date_submitted ?? '';
    $brand_name4 = $application_req4->brand_name ?? '';
    $hm_generic_name4 = $application_req4->hm_generic_name ?? '';
    $classification4 = $application_req4->classification ?? '';
    $category4 = $application_req4->category ?? '';
    $dosage_form4 = $application_req4->dosage_form ?? '';

    $hm_applicant_email4 = $application_req4->hm_applicant_email ?? '';
    $hm_mah_country4 = $application_req4->hm_mah_country ?? '';
    $hm_ltr4 = $application_req4->hm_ltr ?? '';
    $hm_ltr_email4 = $application_req4->hm_ltr_email ?? '';
    $hm_manufacturer_country4 = $application_req4->hm_manufacturer_country ?? '';
    $hm_manufacturer_email4 = $application_req4->hm_manufacturer_email ?? '';

    $assessment_procedure4 = $application_req4->assessment_procedure ?? '';
    $hm_registration_number4 = $application_req4->hm_registration_number ?? '';
    $application_current_stage4 = $application_req4->application_current_stage ?? '';
    $application_process4 = $application_req4->application_process ?? '';
    $gmp_status4 = $application_req4->gmp_status ?? '';

    $date_screening4 = $application_req4->date_screening ?? '';

    $date_first_assessment14 = $application_req4->date_first_assessment1 ?? '';
    $date_second_assessment14 = $application_req4->date_second_assessment1 ?? '';
    $date_query_assessment14 = $application_req4->date_query_assessment1 ?? '';

    $date_first_assessment24 = $application_req4->date_first_assessment2 ?? '';
    $date_second_assessment24 = $application_req4->date_second_assessment2 ?? '';
    $date_query_assessment24 = $application_req4->date_query_assessment2 ?? '';

    $date_first_assessment34 = $application_req4->date_first_assessment3 ?? '';
    $date_second_assessment34 = $application_req4->date_second_assessment3 ?? '';
    $date_query_assessment34 = $application_req4->date_query_assessment3 ?? '';

    $date_first_assessment44 = $application_req4->date_first_assessment4 ?? '';
    $date_second_assessment44 = $application_req4->date_second_assessment4 ?? '';

    $date_response14 = $application_req4->date_response1 ?? '';
    $date_response24 = $application_req4->date_response2 ?? '';
    $date_response34 = $application_req4->date_response3 ?? '';

      if ($application_current_stage4 == 19) {
        $count_pending_gmp_renewal += 1;
      }
      /*
else if($application_current_stage4==21)
{
	$count_pending_first_assessment_add_data_renewal+=1;
}
else if($application_current_stage4==3)
{
	$count_pending_first_assessment_renewal+=1;
}
*/
      if ($application_current_stage4 == 6) {
        $count_under_approval_renewal += 1;
      }
      if ($application_current_stage4 == 5) {
        $count_pending_peer_review_renewal += 1;
      }
      if ($application_current_stage4 == 4) {
  // ✅ Initialize counters first
$count_assessment_renewal = 0;
$total_not_assigned_delayed04 = 0;
$total_not_assigned_ontime04 = 0;
$total_not_assigned_tobedelayed04 = 0;

// Increment count safely
$count_assessment_renewal += 1;

// ✅ Prepare and execute PDO query
$stmtTimeline = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND process_type = 'Renewal'
");
$stmtTimeline->execute([':status_id' => $application_current_stage4]);
$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Iterate through timelines
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // Compute days since submission
    $days_processing_chart = 0;
    if (!empty($date_submitted4) && $date_submitted4 != '0000-00-00') {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted4)) / 86400;
    }

    // --- Categorize processing status ---
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed04 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime04 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed04 += 1;
    }
}

        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned_renewal = $count_pending_first_assessment_renewal;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_renewal = $count_assessment_renewal;

        if ($total_not_assigned_renewal > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed04 / $total_not_assigned_renewal) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed04 / $total_not_assigned_renewal) * 100,
            'ontime' => ($total_not_assigned_ontime04 / $total_not_assigned_renewal) * 100,
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
          $percentage_not_assigned_delayed04 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed04 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime04 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed04 = 0;
          $percentage_not_assigned_tobedelayed04 = 0;
          $percentage_not_assigned_ontime04 = 0;
        }
        ////////////////////////////////////////////////////////////////////
        //////////////End Values for the graphs///////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////
      }

      if ($application_current_stage4 == 35) {
        $count_pending_second_assessment_pending_renewal += 1;
        //echo $count_pending_second_assessment_pending_renewal;
        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage4' and process_type='Renewal'");
        foreach ($applications_req_chart as $application_req_chart) {
          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted4)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed035 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime035 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed035 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned_renewal = $count_pending_second_assessment_pending_renewal;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_renewal = $count_pending_second_assessment_pending_renewal;
        // echo  $total_not_assigned_renewal.'<br>';
        if ($total_not_assigned_renewal > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed035 / $total_not_assigned_renewal) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed035 / $total_not_assigned_renewal) * 100,
            'ontime' => ($total_not_assigned_ontime035 / $total_not_assigned_renewal) * 100,
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
          $percentage_not_assigned_delayed035 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed035 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime035 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed035 = 0;
          $percentage_not_assigned_tobedelayed035 = 0;
          $percentage_not_assigned_ontime035 = 0;
        }
        ////////////////////////////////////////////////////////////////////
      } else if ($application_current_stage4 == 38) {
        $count_manager_report_review_renewal += 1;
      }
      if ($application_current_stage4 == 3) {


        // ✅ Initialize counters first
$count_pending_first_assessment_renewal = 0;
$total_not_assigned_delayed03 = 0;
$total_not_assigned_ontime03 = 0;
$total_not_assigned_tobedelayed03 = 0;

// Increment safely
$count_pending_first_assessment_renewal += 1;

// ✅ Prepare and execute PDO query
$stmtTimeline = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND process_type = 'Renewal'
");
$stmtTimeline->execute([':status_id' => $application_current_stage4]);
$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Iterate timelines safely
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // ✅ Calculate processing days safely
    $days_processing_chart = 0;
    if (!empty($date_submitted4) && $date_submitted4 != '0000-00-00') {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted4)) / 86400;
    }

    // ✅ Categorize progress
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed03 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime03 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed03 += 1;
    }
}

        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned_renewal = $count_pending_first_assessment_renewal;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_renewal = $count_pending_first_assessment_renewal;

        if ($total_not_assigned_renewal > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed03 / $total_not_assigned_renewal) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed03 / $total_not_assigned_renewal) * 100,
            'ontime' => ($total_not_assigned_ontime03 / $total_not_assigned_renewal) * 100,
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
          $percentage_not_assigned_delayed03 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed03 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime03 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed03 = 0;
          $percentage_not_assigned_tobedelayed03 = 0;
          $percentage_not_assigned_ontime03 = 0;
        }
        ////////////////////////////////////////////////////////////////////

      }
      if ($application_current_stage4 == 10 || $application_current_stage4 == 24) {
        $count_approved_renewal += 1;
      }
      if ($application_current_stage4 == 25 || $application_current_stage4 == 17 || $application_current_stage4 == 26 || $application_current_stage4 == 27 || $application_current_stage4 == 39 || $application_current_stage4 == 29) {
// ✅ Initialize counters to prevent undefined variable warnings
$count_awaiting_applicant_feedback_renewal = 0;
$total_not_assigned_delayed025 = 0;
$total_not_assigned_ontime025 = 0;
$total_not_assigned_tobedelayed025 = 0;

// Increment counter safely
$count_awaiting_applicant_feedback_renewal += 1;

// --- Fetch timelines via PDO ---
$stmtTimeline = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND process_type = 'Renewal'
");
$stmtTimeline->execute([':status_id' => $application_current_stage4]);
$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // --- Determine which date to use ---
    $days_processing_chart = 0;
    if (in_array($application_current_stage4, [25, 17, 29])) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_query_assessment14 ?? '')) / 86400;
    } elseif ($application_current_stage4 == 26) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_query_assessment24 ?? '')) / 86400;
    } elseif ($application_current_stage4 == 27) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_query_assessment34 ?? '')) / 86400;
    } elseif ($application_current_stage4 == 39) {
        if (empty($date_query_assessment14) || $date_query_assessment14 == '0000-00-00') {
            $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted4 ?? '')) / 86400;
        } else {
            $days_processing_chart = (strtotime($datetoday) - strtotime($date_query_assessment14 ?? '')) / 86400;
        }
    }

    // --- Categorize by timeliness ---
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed025 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime025 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed025 += 1;
    }
}


        // echo "Total:". $total_awaiting_applicant_feedback." | Delayed:". $total_not_assigned_delayed25." | ToBeDelayed:". $total_not_assigned_tobedelayed25." | OnTime:". $total_not_assigned_ontime25."<br>";
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        $total_awaiting_applicant_feedback_renewal = $count_awaiting_applicant_feedback_renewal;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        if ($total_awaiting_applicant_feedback_renewal > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed025 / $total_awaiting_applicant_feedback_renewal) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed025 / $total_awaiting_applicant_feedback_renewal) * 100,
            'ontime' => ($total_not_assigned_ontime025 / $total_awaiting_applicant_feedback_renewal) * 100,
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
          $percentage_not_assigned_delayed025 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed025 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime025 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed025 = 0;
          $percentage_not_assigned_tobedelayed025 = 0;
          $percentage_not_assigned_ontime025 = 0;
        }
        ////////////////////////////////////////////////////////////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////

        //////////////////Notification MA - Awaiting Applicant Feedback/////////////////////////////

        //$hm_mah_email='imushimiyimana@rwandafda.gov.rw';
        if ($hm_applicant_email4 <> '') {
          $number_of_days = 90;
          if ($application_current_stage4 == 17) {
            $days_processing = (strtotime($datetoday) - strtotime($date_query_assessment14)) / 60 / 60 / 24;
          } else if ($application_current_stage4 == 25) {
            $days_processing = (strtotime($datetoday) - strtotime($date_query_assessment14)) / 60 / 60 / 24;
          } else if ($application_current_stage4 == 26) {
            $days_processing = (strtotime($datetoday) - strtotime($date_query_assessment24)) / 60 / 60 / 24;
          } else if ($application_current_stage4 == 27) {
            $days_processing = (strtotime($datetoday) - strtotime($date_query_assessment34)) / 60 / 60 / 24;
          }
          /*
else if($application_current_stage==29)
{
$days_processing=(strtotime($datetoday)-strtotime($date_query_assessment1))/60/60/24;
}
*/
          //$send_to='imushimiyimana@rwandafda.gov.rw';
          $send_to = $hm_applicant_email4;
          $notification_to_category = 'Applicant';
          if (($days_processing - $number_of_days == 30) || ($days_processing - $number_of_days == 10)) {

            //if(!($wpdb->get_results("SELECT * from tbl_hm_notifications where (notification_to='$dm_email') and (notification_month='$monthtoday' and notification_year='$yeartoday' and notification_week='$weektoday' and notification_type='$notification_type' and notification_to_category='$notification_to_category')")))
            $notification_type = 'Awaiting Query Response';
            $subject2 = "Rwanda FDA notification - MA-Application awaiting query response" . " " . $reference_no4;
            $message2 = "Dear Valued Client," . "\r\n" . "This serves to inform you that the application with Reference No. " . $reference_no4 . " is awaiting for query response. Please be reminded that you have to respond to the query within 90days starting from the date of the query reception.";
            //$headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" ."CC: irenemuto@gmail.com";
            $headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" . "CC: ragiraneza@rwandafda.gov.rw,tuwambajineza@rwandafda.gov.rw,dgasana@rwandafda.gov.rw,snkusi@rwandafda.gov.rw";

            if (!($wpdb->get_results("SELECT * from tbl_hm_notifications where notification_to='$send_to' and notification_date='$datetoday' and application_id='$hm_application_id4' and notification_type='$notification_type' and notification_to_category='$notification_to_category'"))) {
              $sent = wp_mail($send_to, $subject2, strip_tags($message2), $headers2);
              //mail($to2,$subject2,$message2,$headers2);
              $wpdb->insert(
                'tbl_hm_notifications',
                array(
                  'notification_to' => $send_to,
                  'notification_subject' => $subject2,
                  'notification_message' => $message2,
                  'notification_headers' => $headers2,
                  'notification_date' => $datetoday,
                  'notification_week' => $weektoday,
                  'notification_month' => $monthtoday,
                  'notification_year' => $yeartoday,
                  'notification_type' => $notification_type,
                  'notification_to_category' => $notification_to_category,
                  'application_id' => $hm_application_id4


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
            }
          } else if ($days_processing - $number_of_days == 0 || $days_processing > $number_of_days) {
            $notification_type = 'Alert: Awaiting Query Response';
            $subject2 = "Rwanda FDA notification - Alert: MA-Application awaiting query response" . " " . $reference_no4;
            $message2 = "Dear Valued Client," . "\r\n" . "This serves to inform you that you have exceeded 90days to reply to the query submitted to you for your product Market Authorization with Reference No. " . $reference_no4 . ". Please be reminded that failure to submit the response within 15 days starting from today, your application will be withdrawn";
            //$headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" ."CC: irenemuto@gmail.com";
            $headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" . "CC: ragiraneza@rwandafda.gov.rw,tuwambajineza@rwandafda.gov.rw,dgasana@rwandafda.gov.rw,snkusi@rwandafda.gov.rw";

            // Convert to PDO: check if notification already exists
            $stmtCheck = $pdo->prepare("
              SELECT * FROM tbl_hm_notifications
              WHERE notification_to = :send_to
                AND application_id = :application_id
                AND notification_type = :notification_type
                AND notification_to_category = :notification_to_category
            ");
            $stmtCheck->execute([
              ':send_to' => $send_to,
              ':application_id' => $hm_application_id4,
              ':notification_type' => $notification_type,
              ':notification_to_category' => $notification_to_category
            ]);
            if ($stmtCheck->rowCount() == 0) {
              $sent = wp_mail($send_to, $subject2, strip_tags($message2), $headers2);
              //mail($to2,$subject2,$message2,$headers2);
              $wpdb->insert(
                'tbl_hm_notifications',
                array(
                  'notification_to' => $send_to,
                  'notification_subject' => $subject2,
                  'notification_message' => $message2,
                  'notification_headers' => $headers2,
                  'notification_date' => $datetoday,
                  'notification_week' => $weektoday,
                  'notification_month' => $monthtoday,
                  'notification_year' => $yeartoday,
                  'notification_type' => $notification_type,
                  'notification_to_category' => $notification_to_category,
                  'application_id' => $hm_application_id4


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
            }
          }


          ////////////End Notification MA - Awaiting Applicant Feedback////////////////
        }
      }
      if ($application_current_stage4 == 8 || $application_current_stage4 == 11 || $application_current_stage4 == 12 || $application_current_stage4 == 13 || $application_current_stage4 == 9 || $application_current_stage4 == 18 || $application_current_stage4 == 19) {
        $count_queried_renewal += 1;
        //echo $count_queried;
        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage4' and process_type='Renewal'");
        foreach ($applications_req_chart as $application_req_chart) {
          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          // $days_processing_chart=(strtotime($datetoday)-strtotime($date_submitted))/60/60/24;
          if ($date_second_assessment14 == '' || $date_second_assessment14 == '0000-00-00') {
            $date_queried = $date_submitted4;
          } else {
            $date_queried = $date_second_assessment14;
            // echo $date_second_assessment1.' '.$reference_no;
          }
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_queried)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed08 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime08 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed08 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        $total_queried_renewal = $count_queried_renewal;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;

        if ($total_queried_renewal > 0) {
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed08 / $total_queried_renewal) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed08 / $total_queried_renewal) * 100,
            'ontime' => ($total_not_assigned_ontime08 / $total_queried_renewal) * 100,
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

          $percentage_not_assigned_delayed08 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed08 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime08 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed08 = 0;
          $percentage_not_assigned_tobedelayed08 = 0;
          $percentage_not_assigned_ontime08 = 0;
        }
        /////////////////////////
        //////////////End Values for the graphs///////////////
        /////////////////////////
        //////////////End Values for the graphs///////////////

        ////////////Notification for Query letters not sent - Renewal///////////////
        $dm_email = 'snkusi@rwandafda.gov.rw';
        //$dm_email='imushimiyimana@rwandafda.gov.rw';
        $notification_type = 'Query Letter not sent - Renewal';
        $notification_to_category = 'Staff';

        //if(!($wpdb->get_results("SELECT * from tbl_hm_notifications where (notification_to='$dm_email') and (notification_month='$monthtoday' and notification_year='$yeartoday' and notification_week='$weektoday' and notification_type='$notification_type' and notification_to_category='$notification_to_category')")))
        if (!($wpdb->get_results("SELECT * from tbl_hm_notifications where notification_to='$dm_email' and notification_date='$datetoday' and hm_registration_number='$tracking_no4' and notification_type='$notification_type' and notification_to_category='$notification_to_category'"))) {
          $subject2 = "Rwanda FDA notification - MA-Query Letter not sent - Renewal" . " " . $tracking_no4;
          $message2 = "The query letter for the application with Tracking No. " . $tracking_no4 . " is not yet sent. Please login to the Monitoring tool for action.";
          $headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" . "CC: imushimiyimana@rwandafda.gov.rw,dgasana@rwandafda.gov.rw,ragiraneza@rwandafda.gov.rw,tuwambajineza@rwandafda.gov.rw,vhabyalimana@rwandafda.gov.rw";
          //$headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" ."CC: imushimiyimana@yahoo.fr";

          //$headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" ."CC: imushimiyimana@rwandafda.gov.rw,vhabyalimana@rwandafda.gov.rw";
          $sent = wp_mail($dm_email, $subject2, strip_tags($message2), $headers2);
          //mail($to2,$subject2,$message2,$headers2);
          $wpdb->insert(
            'tbl_hm_notifications',
            array(
              'notification_to' => $dm_email,
              'notification_subject' => $subject2,
              'notification_message' => $message2,
              'notification_headers' => $headers2,
              'notification_date' => $datetoday,
              'notification_week' => $weektoday,
              'notification_month' => $monthtoday,
              'notification_year' => $yeartoday,
              'notification_type' => $notification_type,
              'notification_to_category' => $notification_to_category,
              'hm_registration_number' => $tracking_no4


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
        }
        ////////////End Notification for Query letters not sent - Renewal//////////////
      }
      //if($application_current_stage4==7 || $application_current_stage4==3)
      if ($application_current_stage4 == 7) {
        $count_not_assessed_renewal += 1;
        // echo $count_not_assessed_renewal;

        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage4' and process_type='Renewal'");
        foreach ($applications_req_chart as $application_req_chart) {
          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted4)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed07 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime01 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed07 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned_renewal = $count_pending_first_assessment_renewal;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_renewal = $count_not_assessed_renewal;

        if ($total_not_assigned_renewal > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed07 / $total_not_assigned_renewal) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed07 / $total_not_assigned_renewal) * 100,
            'ontime' => ($total_not_assigned_ontime07 / $total_not_assigned_renewal) * 100,
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
          $percentage_not_assigned_delayed07 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed07 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime07 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed07 = 0;
          $percentage_not_assigned_tobedelayed07 = 0;
          $percentage_not_assigned_ontime07 = 0;
        }
        ////////////////////////////////////////////////////////////////////

        ////////////Notification for Pending Application for Assignment(1st) - Renewal///////////////
        $dm_email = 'snkusi@rwandafda.gov.rw';
        //$dm_email='imushimiyimana@rwandafda.gov.rw';
        $notification_type = 'Pending Application for Assignment - Renewal';
        $notification_to_category = 'Staff';

        //if(!($wpdb->get_results("SELECT * from tbl_hm_notifications where (notification_to='$dm_email') and (notification_month='$monthtoday' and notification_year='$yeartoday' and notification_week='$weektoday' and notification_type='$notification_type' and notification_to_category='$notification_to_category')")))
        if (!($wpdb->get_results("SELECT * from tbl_hm_notifications where notification_to='$dm_email' and notification_date='$datetoday' and hm_registration_number='$tracking_no4' and notification_type='$notification_type' and notification_to_category='$notification_to_category'"))) {
          $subject2 = "Rwanda FDA notification - MA-Pending Application for Assignment - Renewal" . " " . $tracking_no4;
          $message2 = "The application with Tracking No. " . $tracking_no4 . " is awaiting for assignment since " . $date_submitted4 . ". Please login to the Monitoring tool for action.";
          $headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" . "CC: imushimiyimana@rwandafda.gov.rw,dgasana@rwandafda.gov.rw,ragiraneza@rwandafda.gov.rw,tuwambajineza@rwandafda.gov.rw,vhabyalimana@rwandafda.gov.rw";
          //$headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" ."CC: imushimiyimana@yahoo.fr";

          //$headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" ."CC: imushimiyimana@rwandafda.gov.rw,vhabyalimana@rwandafda.gov.rw";
          $sent = wp_mail($dm_email, $subject2, strip_tags($message2), $headers2);
          //mail($to2,$subject2,$message2,$headers2);
          $wpdb->insert(
            'tbl_hm_notifications',
            array(
              'notification_to' => $dm_email,
              'notification_subject' => $subject2,
              'notification_message' => $message2,
              'notification_headers' => $headers2,
              'notification_date' => $datetoday,
              'notification_week' => $weektoday,
              'notification_month' => $monthtoday,
              'notification_year' => $yeartoday,
              'notification_type' => $notification_type,
              'notification_to_category' => $notification_to_category,
              'hm_registration_number' => $tracking_no4


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
        }
        ////////////End Notification for Pending Application for Assignment(1st) - Renewal//////////////
      } else if ($application_current_stage4 == 21) {
        $count_pending_first_assessment_add_data_renewal += 1;
        /////////////////Select processing timelines//////////////////////
        $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage4' and process_type='Renewal'");
        foreach ($applications_req_chart as $application_req_chart) {
          $number_of_days_chart = intval($application_req_chart->number_of_days);
          // echo $number_of_days_chart;
          $half_days = round($number_of_days_chart / 2);
          $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted4)) / 60 / 60 / 24;
          //echo $days_processing.'<br>'.$number_of_days;
          //echo $days_processing;
          if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed08 += 1;
            //  echo "Status:Delayed | Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
            //echo $total_not_assigned_delayed;
            //$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
          } else if ($days_processing_chart < ($number_of_days_chart / 2)) {
            // $days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";
            $total_not_assigned_ontime08 += 1;
            // echo "Status:Ontime |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          } else if (($days_processing_chart >= ($number_of_days_chart / 2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed08 += 1;
            //echo $total_not_assigned_tobedelayed.' Days processing:'.$days_processing_chart.'<br>';
            // echo "Status:ToBeDelayed |Processing: $days_processing_chart | Allowed: $number_of_days_chart | Half: $half_days<br>";
          }
        }
        /////////////////End Select processing timelines//////////////////////
        ////////////////////////
        ////////////////Values for the graphs///////////////
        //$total_not_assigned=$count_not_assigned;
        // $total_not_assigned = $count_pending_first_assessment_add_data_renewal;
        //echo $total_not_assigned;
        //$total_not_assigned_delayed=total_not_assigned_delayed;

        //$total_not_assigned_ontime=0;
        /////////////////////////////////////////////////////////////////////////
        // Calculate percentages with sum == 100 logic
        $total_not_assigned_renewal = $count_pending_first_assessment_add_data_renewal;

        if ($total_not_assigned_renewal > 0) {
          // Raw percentages (floats)
          $raw_percentages = [
            'delayed' => ($total_not_assigned_delayed08 / $total_not_assigned_renewal) * 100,
            'tobedelayed' => ($total_not_assigned_tobedelayed08 / $total_not_assigned_renewal) * 100,
            'ontime' => ($total_not_assigned_ontime08 / $total_not_assigned_renewal) * 100,
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
          $percentage_not_assigned_delayed08 = $floored['delayed'];
          $percentage_not_assigned_tobedelayed08 = $floored['tobedelayed'];
          $percentage_not_assigned_ontime08 = $floored['ontime'];
        } else {
          $percentage_not_assigned_delayed08 = 0;
          $percentage_not_assigned_tobedelayed08 = 0;
          $percentage_not_assigned_ontime08 = 0;
        }
        ////////////////////////////////////////////////////////////////////
      }
// Initialize counters to prevent undefined variable warnings
$count_all_applications_renewal = 0;
$total_not_assigned_delayed02 = 0;
$total_not_assigned_ontime02 = 0;
$total_not_assigned_tobedelayed02 = 0;

// Example of your PDO fetch
$stmtRenewal = $pdo->prepare("SELECT * FROM tbl_hm_applications_renewal");
$stmtRenewal->execute();
$applications_req4 = $stmtRenewal->fetchAll(PDO::FETCH_OBJ);

foreach ($applications_req4 as $application_req4) {
    $application_current_stage = $application_req4->application_current_stage ?? '';
    $assessment_procedure = $application_req4->assessment_procedure ?? '';
    $date_submitted = $application_req4->date_submitted ?? null;

    $count_all_applications_renewal += 1;

    // Select processing timelines with PDO
    $stmtTimeline = $pdo->prepare("
        SELECT * FROM tbl_timelines 
        WHERE status_id = :status_id 
        AND assessment_pathway = :assessment_pathway
    ");
    $stmtTimeline->execute([
        ':status_id' => $application_current_stage,
        ':assessment_pathway' => $assessment_procedure
    ]);
    $applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

    foreach ($applications_req_chart as $application_req_chart) {
        $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
        $half_days = round($number_of_days_chart / 2);

        if ($date_submitted) {
            $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 86400; // 60*60*24
        } else {
            $days_processing_chart = 0;
        }

        if ($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed02 += 1;
        } elseif ($days_processing_chart < $half_days) {
            $total_not_assigned_ontime02 += 1;
        } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
            $total_not_assigned_tobedelayed02 += 1;
        }
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
          'delayed' => ($total_not_assigned_delayed02 / $total_not_assigned) * 100,
          'tobedelayed' => ($total_not_assigned_tobedelayed02 / $total_not_assigned) * 100,
          'ontime' => ($total_not_assigned_ontime02 / $total_not_assigned) * 100,
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
        $percentage_not_assigned_delayed02 = $floored['delayed'];
        $percentage_not_assigned_tobedelayed02 = $floored['tobedelayed'];
        $percentage_not_assigned_ontime02 = $floored['ontime'];
      } else {
        $percentage_not_assigned_delayed02 = 0;
        $percentage_not_assigned_tobedelayed02 = 0;
        $percentage_not_assigned_ontime02 = 0;
      }
      ////////////////////////////////////////////////////////////////////
    }
  }
    ///////////////////////End Select Renewal///////////////////////


  //  -------------------------------------------------------------------------------------------

    ///////////////////////////////Notifications////////////////////////////////////
    ///////////////////////Notifications for pending applications//////////////////////////
    //$dm_email='snkusi@rwandafda.gov.rw';
    $dm_email = 'snkusi@rwandafda.gov.rw';
    $notification_type = 'Pending Applications';
    $notification_to_category = 'Staff';

    if (!($wpdb->get_results("SELECT * from tbl_hm_notifications where (notification_to='$dm_email') and (notification_month='$monthtoday' and notification_year='$yeartoday' and notification_week='$weektoday' and notification_type='$notification_type' and notification_to_category='$notification_to_category')"))) {
      if ($count_not_assigned > 0 || $count_not_assessed > 0 || $count_pending_second_assessment_pending > 0 || $count_second_assessment_completed_letter_not_sent > 0) {
        $subject2 = "Rwanda FDA notification - MA-Pending Applications";
        $message2 = "Pending applications for assignment: " . $count_not_assigned . " for screening, " . $count_not_assessed . " for 1st assessment, " . $count_pending_second_assessment_pending . " for second assessment, and " . $count_second_assessment_completed_letter_not_sent . " query letters not sent. Please login to the Monitoring tool for action.";
        $headers2 = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>" . "\r\n" . "CC: imushimiyimana@rwandafda.gov.rw,vhabyalimana@rwandafda.gov.rw";
        //$headers = "From: notifications@rwandafda.gov.rw" . "\r\n" ."CC: imushimiyimana@rwandafda.gov.rw,rmuganga@rwandafda.gov.rw,vhabyalimana@rwandafda.gov.rw";
        $sent = wp_mail($dm_email, $subject2, strip_tags($message2), $headers2);
        //mail($to2,$subject2,$message2,$headers2);
        $wpdb->insert(
          'tbl_hm_notifications',
          array(
            'notification_to' => $dm_email,
            'notification_subject' => $subject2,
            'notification_message' => $message2,
            'notification_headers' => $headers2,
            'notification_date' => $datetoday,
            'notification_week' => $weektoday,
            'notification_month' => $monthtoday,
            'notification_year' => $yeartoday,
            'notification_type' => $notification_type,
            'notification_to_category' => $notification_to_category,
            'hm_registration_number' => 'Bulk'


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
      }
    }
    ////////////////////////End Notifications for pending applications////////////////////////

    ///////////////////////////////End Notifications///////////////////////////////////
    //////////////////////
    /*
$count_registered=2043;
$count_under_approval=134;
$count_peer_review=33;
$count_awaiting_applicant_feedback=593+20;
$count_pending_first_assessment_add_data=18+8;
$count_second_assessment_completed_letter_not_sent=11;
$count_screening=11;
$count_pending_first_assessment=100;
//$count_all_applications_under_process=1315;
$count_all_applications_under_process=1304;
$count_assessment=185-11;
*/
    /////////////////////////////



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