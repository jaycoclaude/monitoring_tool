<?php

require_once '../includes/config.php'; // PDO connection assumed to be set up here
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
        // ✅ Initialize variables before use
$count_awaiting_applicant_feedback_variation = 0;
$total_not_assigned_delayed0025 = 0;
$total_not_assigned_ontime05 = 0;
$total_not_assigned_tobedelayed0025 = 0;

// ✅ Increment the counter
$count_awaiting_applicant_feedback_variation += 1;

// ✅ Prepare PDO query
$stmtTimeline = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway 
      AND process_type = 'Variation'
      LIMIT 20
");
$stmtTimeline->execute([
    ':status_id' => $application_current_stage3,
    ':assessment_pathway' => $variation_type3
]);

$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Loop through results safely
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    // ✅ Calculate processing days safely
    $days_processing_chart = 0;
    if (!empty($date_submitted3) && $date_submitted3 != '0000-00-00') {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 86400;
    }

    // ✅ Categorize processing timeline
    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed0025 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime05 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed0025 += 1;
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
          $total_not_assigned_tobedelayed005 = 0;
          $total_not_assigned_ontime0025 = 0;
        

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
        // ✅ Initialize variables to prevent undefined warnings
$count_second_assessment_completed_letter_not_sent_variation = $count_second_assessment_completed_letter_not_sent_variation ?? 0;
$total_not_assigned_delayed004 = $total_not_assigned_delayed004 ?? 0;
$total_not_assigned_ontime004 = $total_not_assigned_ontime004 ?? 0;
$total_not_assigned_tobedelayed004 = $total_not_assigned_tobedelayed004 ?? 0;

// ✅ Increment counter
$count_second_assessment_completed_letter_not_sent_variation += 1;

// ✅ Fetch timelines using PDO
$stmtTimeline = $pdo->prepare("
    SELECT *
    FROM tbl_timelines
    WHERE status_id = :status_id
      AND assessment_pathway = :assessment_pathway
      AND process_type = 'Variation'
    LIMIT 20
");
$stmtTimeline->execute([
    ':status_id' => $application_current_stage3 ?? '',
    ':assessment_pathway' => $variation_type3 ?? ''
]);

$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Process timeline data
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    if (!empty($date_submitted3)) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 86400; // seconds → days
    } else {
        $days_processing_chart = 0;
    }

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed004 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime004 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed004 += 1;
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

        // ✅ Initialize variables to prevent "undefined variable" warnings
$count_pending_first_assessment_add_data_variation = $count_pending_first_assessment_add_data_variation ?? 0;
$total_not_assigned_delayed0021 = $total_not_assigned_delayed0021 ?? 0;
$total_not_assigned_ontime0021 = $total_not_assigned_ontime0021 ?? 0;
$total_not_assigned_tobedelayed0021 = $total_not_assigned_tobedelayed0021 ?? 0;

// ✅ Increment counter
$count_pending_first_assessment_add_data_variation += 1;

// ✅ Fetch timelines safely via PDO
$stmtTimeline = $pdo->prepare("
    SELECT *
    FROM tbl_timelines
    WHERE status_id = :status_id
      AND assessment_pathway = :assessment_pathway
      AND process_type = 'Variation'
    LIMIT 20
");
$stmtTimeline->execute([
    ':status_id' => $application_current_stage3 ?? '',
    ':assessment_pathway' => $variation_type3 ?? ''
]);

$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Process timelines
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    if (!empty($date_submitted3)) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 86400; // seconds to days
    } else {
        $days_processing_chart = 0;
    }

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed0021 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime0021 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed0021 += 1;
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
       // ✅ Initialize variables to prevent undefined warnings
$count_pending_second_assessment_pending_variation = $count_pending_second_assessment_pending_variation ?? 0;
$total_not_assigned_delayed0035 = $total_not_assigned_delayed0035 ?? 0;
$total_not_assigned_ontime0035 = $total_not_assigned_ontime0035 ?? 0;
$total_not_assigned_tobedelayed0035 = $total_not_assigned_tobedelayed0035 ?? 0;

// ✅ Increment counter
$count_pending_second_assessment_pending_variation += 1;

// ✅ Prepare and execute PDO safely
$stmtTimeline = $pdo->prepare("
    SELECT *
    FROM tbl_timelines
    WHERE status_id = :status_id
      AND assessment_pathway = :assessment_pathway
      AND process_type = 'Variation'
    LIMIT 20
");
$stmtTimeline->execute([
    ':status_id' => $application_current_stage3 ?? '',
    ':assessment_pathway' => $variation_type3 ?? ''
]);

$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Process timelines
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    if (!empty($date_submitted3)) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 86400; // seconds → days
    } else {
        $days_processing_chart = 0;
    }

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed0035 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime0035 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed0035 += 1;
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
      // ✅ Initialize variables to avoid undefined variable warnings
$count_pending_first_assessment_pending_add_data_variation = 0;
$total_not_assigned_delayed0036 = 0;
$total_not_assigned_ontime0036 = 0;
$total_not_assigned_tobedelayed0036 = 0;

// ✅ Increment counter
$count_pending_first_assessment_pending_add_data_variation += 1;

// ✅ Use PDO instead of $wpdb
$stmtTimeline = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway 
      AND process_type = 'Variation'
    LIMIT 20
");
$stmtTimeline->execute([
    ':status_id' => $application_current_stage3 ?? '',
    ':assessment_pathway' => $variation_type3 ?? ''
]);

$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Process timelines
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    if (!empty($date_submitted3)) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 86400; // 60*60*24
    } else {
        $days_processing_chart = 0;
    }

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed0036 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime0036 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed0036 += 1;
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
       // ✅ Initialize variables to prevent undefined variable warnings
$count_pending_second_assessment_variation = 0;
$total_not_assigned_delayed002 = 0;
$total_not_assigned_ontime002 = 0;
$total_not_assigned_tobedelayed002 = 0;

// ✅ Increment counter
$count_pending_second_assessment_variation += 1;

// ✅ Fetch timelines using PDO (safe and efficient)
$stmtTimeline = $pdo->prepare("
    SELECT * 
    FROM tbl_timelines 
    WHERE status_id = :status_id 
      AND assessment_pathway = :assessment_pathway 
      AND process_type = 'Variation'
    LIMIT 20
");
$stmtTimeline->execute([
    ':status_id' => $application_current_stage3 ?? '',
    ':assessment_pathway' => $variation_type3 ?? ''
]);

$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Process timeline calculations
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    if (!empty($date_submitted3)) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 86400; // 60*60*24
    } else {
        $days_processing_chart = 0;
    }

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed002 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime002 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed002 += 1;
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
       // ✅ Initialize variables to prevent "undefined variable" warnings
$count_pending_first_assessment_variation = $count_pending_first_assessment_variation ?? 0;
$total_not_assigned_delayed003 = $total_not_assigned_delayed003 ?? 0;
$total_not_assigned_ontime003 = $total_not_assigned_ontime003 ?? 0;
$total_not_assigned_tobedelayed003 = $total_not_assigned_tobedelayed003 ?? 0;

// ✅ Increment counter
$count_pending_first_assessment_variation += 1;

// ✅ Prepare and execute PDO statement safely
$stmtTimeline = $pdo->prepare("
    SELECT *
    FROM tbl_timelines
    WHERE status_id = :status_id
      AND assessment_pathway = :assessment_pathway
      AND process_type = 'Variation'
    LIMIT 20
");
$stmtTimeline->execute([
    ':status_id' => $application_current_stage3 ?? '',
    ':assessment_pathway' => $variation_type3 ?? ''
]);

$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Process timelines
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    if (!empty($date_submitted3)) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 86400; // seconds → days
    } else {
        $days_processing_chart = 0;
    }

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed003 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime003 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed003 += 1;
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
        // ✅ Initialize variables to prevent "undefined variable" warnings
$count_not_assessed_variation = 0;
$total_not_assigned_delayed007 = 0;
$total_not_assigned_ontime007 = 0;
$total_not_assigned_tobedelayed007 = 0;

// ✅ Increment counter
$count_not_assessed_variation += 1;

// ✅ Use PDO to safely query the timelines
$stmtTimeline = $pdo->prepare("
    SELECT *
    FROM tbl_timelines
    WHERE status_id = :status_id
      AND assessment_pathway = :assessment_pathway
      AND process_type = 'Variation'
    LIMIT 20
");
$stmtTimeline->execute([
    ':status_id' => $application_current_stage3 ?? '',
    ':assessment_pathway' => $variation_type3 ?? ''
]);

$applications_req_chart = $stmtTimeline->fetchAll(PDO::FETCH_OBJ);

// ✅ Process timelines
foreach ($applications_req_chart as $application_req_chart) {
    $number_of_days_chart = intval($application_req_chart->number_of_days ?? 0);
    $half_days = round($number_of_days_chart / 2);

    if (!empty($date_submitted3)) {
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted3)) / 86400; // 60*60*24
    } else {
        $days_processing_chart = 0;
    }

    if ($days_processing_chart > $number_of_days_chart) {
        $total_not_assigned_delayed007 += 1;
    } elseif ($days_processing_chart < $half_days) {
        $total_not_assigned_ontime007 += 1;
    } elseif ($days_processing_chart >= $half_days && $days_processing_chart <= $number_of_days_chart) {
        $total_not_assigned_tobedelayed007 += 1;
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
    }
?>