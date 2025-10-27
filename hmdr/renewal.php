
   
   <?php

   require_once '../includes/config.php'; // PDO connection assumed to be set up here
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
      LIMIT 20
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
      LIMIT 20
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
      LIMIT 20
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
// ✅ Initialize all counters before use
$count_not_assessed_renewal = 0;
$total_not_assigned_delayed07 = 0;
$total_not_assigned_ontime01 = 0;
$total_not_assigned_tobedelayed07 = 0;

// ✅ Increment the counter safely
$count_not_assessed_renewal += 1;

// ✅ Use PDO instead of $wpdb
$stmtTimeline = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND process_type = 'Renewal'
      LIMIT 20
");
$stmtTimeline->execute([':status_id' => $application_current_stage4]);
$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Loop through results safely
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // ✅ Calculate days processing safely
    $days_processing_chart = 0;
    if (!empty($date_submitted4) && $date_submitted4 != '0000-00-00') {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted4)) / 86400;
    }

    // ✅ Categorize by processing status
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed07 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime01 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed07 += 1;
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
        $total_not_assigned_tobedelayed07 = 0;
        $total_not_assigned_ontime07 = 0;

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

// ✅ Fetch all timelines once (only 163 rows, very fast)
$stmtAllTimelines = $pdo->prepare("SELECT * FROM tbl_timelines");
$stmtAllTimelines->execute();
$timelines_data = $stmtAllTimelines->fetchAll(PDO::FETCH_OBJ);

// ✅ Build an indexed lookup table for quick access
$timelines_lookup = [];
foreach ($timelines_data as $row) {
    $key = $row->status_id . '|' . $row->assessment_pathway;
    $timelines_lookup[$key] = $row;
}

// ✅ Fetch all applications
$stmtRenewal = $pdo->prepare("SELECT * FROM tbl_hm_applications_renewal");
$stmtRenewal->execute();
$applications_req4 = $stmtRenewal->fetchAll(PDO::FETCH_OBJ);

$debug_count = 0;

foreach ($applications_req4 as $application_req4) {
    $application_current_stage = $application_req4->application_current_stage ?? '';
    $assessment_procedure = $application_req4->assessment_procedure ?? '';
    $date_submitted = $application_req4->date_submitted ?? null;

    $count_all_applications_renewal += 1;

    // ✅ Build lookup key
    $key = $application_current_stage . '|' . $assessment_procedure;

    // ✅ Optional limited debug (only print first 10)
    if ($debug_count < 10) {
       
        $debug_count++;
    }

    // ✅ Check if timeline data exists for this key
    if (!isset($timelines_lookup[$key])) {
        continue; // Skip if no matching timeline
    }

    $application_req_chart = $timelines_lookup[$key];
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    if ($date_submitted) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 86400; // days
    } else {
        $days_processing_chart = 0;
    }

    // ✅ Count based on processing time
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed02 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime02 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed02 += 1;
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
    ?>