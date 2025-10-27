<?php
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
?>