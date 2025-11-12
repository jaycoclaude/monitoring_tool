<?php
$datetoday = date("Y-m-d");
$monthtoday = date("m");
$yeartoday = date("Y");
$weektoday = date("W");

// Initialize all dashboard counters
$counts = [
    'under_approval' => 0,
    'not_assigned' => 0,
    'peer_review' => 0,
    'queried' => 0,
    'second_assessment' => 0,
    'first_assessment' => 0,
    'assessment' => 0,
    'screening' => 0,
    'all_applications' => 0,
    'all_applications_under_process' => 0,
    'not_assessed' => 0,
    'registered' => 0,
    'second_assessment_completed' => 0,
    'onhold' => 0,
    'pending_gmp' => 0,
    'rejected' => 0,
    'passed_peer_review_pending_gmp' => 0,
    'pending_first_assessment_add_data' => 0,
    'pending_second_assessment_add_data' => 0,
    'backlog' => 0,
    'awaiting_applicant_feedback' => 0,
    'second_assessment_completed_letter_not_sent' => 0,
    'pending_first_assessment' => 0,
    'expired_applications' => 0,
    'pending_first_assessment_pending' => 0,
    'pending_second_assessment_pending' => 0,
    'pending_first_assessment_pending_add_data' => 0,
    'pending_second_assessment_pending_add_data' => 0,
    'manager_report_review' => 0,
];

// Define stages that need timeline tracking
$stage_ids = [1, 2, 3, 4, 5, 7, 8, 15, 21, 22, 25, 35, 36, 37, 38];

// Initialize percentages
$percentages = [];
foreach ($stage_ids as $stage) {
    $percentages[$stage] = ['delayed' => 0, 'tobedelayed' => 0, 'ontime' => 0];
}

// CENTRALIZED COUNTERS: One array for all stages
$stage_counters = [];
foreach ($stage_ids as $stage) {
    $stage_counters[$stage] = ['delayed' => 0, 'tobedelayed' => 0, 'ontime' => 0];
}

error_log('application.php: Initialized counters and stage tracking');

// Helper: Valid date check
function isValidDate($date) {
    return $date && $date !== '0000-00-00' && $date !== null && strtotime($date) > 0;
}

// Helper: Days between two dates
function getDaysBetween($start, $end) {
    if (isValidDate($start) && isValidDate($end)) {
        $days = (strtotime($end) - strtotime($start)) / 86400;
        return $days > 0 ? floor($days) : 0;
    }
    return 0;
}

// Helper: Calculate percentages with 100% rounding
function calculatePercentages($total, $delayed, $tobedelayed, $ontime) {
    if ($total <= 0) return ['delayed' => 0, 'tobedelayed' => 0, 'ontime' => 0];

    $raw = [
        'delayed' => ($delayed / $total) * 100,
        'tobedelayed' => ($tobedelayed / $total) * 100,
        'ontime' => ($ontime / $total) * 100,
    ];

    $floored = [];
    $remainders = [];
    $total_floor = 0;

    foreach ($raw as $key => $val) {
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

    return $floored;
}

try {
    // Fetch all applications
    $stmt = $pdo->prepare("SELECT * FROM tbl_hm_applications_cosmetics");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_OBJ);

    error_log("application_chcr.php: Fetched " . count($applications) . " applications");

    foreach ($applications as $app) {
        // Extract fields
        $app_id = $app->hm_application_id ?? '';
        $current_stage = (int)($app->application_current_stage ?? 0);
        $assessment_procedure = $app->assessment_procedure ?? '';
        $date_submitted = $app->date_submitted ?? '';
        $date_screening = $app->date_screening ?? '';
        $date_first_assessment1 = $app->date_first_assessment1 ?? '';
        $date_second_assessment1 = $app->date_second_assessment1 ?? '';
        $date_query_assessment1 = $app->date_query_assessment1 ?? '';
        $date_query_assessment2 = $app->date_query_assessment2 ?? '';
        $date_response1 = $app->date_response1 ?? '';
        $date_response2 = $app->date_response2 ?? '';
        $date_query_assessment3 = $app->date_query_assessment3 ?? '';
        $date_response3 = $app->date_response3 ?? '';
        $reference_no = $app->reference_no ?? '';

        error_log("Processing app ID: $app_id | Stage: $current_stage");

        // Always count total
        $counts['all_applications']++;

        // Determine if under process
        $process_stages = [1,2,3,4,5,6,7,8,9,11,12,13,15,17,18,19,20,21,22,24,25,26,27,29,31,32,33,34,35,36,37,38,39];
        if (in_array($current_stage, $process_stages)) {
            $counts['all_applications_under_process']++;
        }

        // Backlog logic (only for non-awaiting stages)
        if (isValidDate($date_submitted) && !in_array($current_stage, [10,14,16,17,23,25,26,27,28,29,30,39,100])) {


            $round0 = getDaysBetween($date_submitted, $datetoday);
            $round1 = isValidDate($date_query_assessment1) ? getDaysBetween($date_submitted, $date_query_assessment1) : 0;
            $round2 = isValidDate($date_response1) ? (isValidDate($date_query_assessment2) ? getDaysBetween($date_response1, $date_query_assessment2) : getDaysBetween($date_response1, $datetoday)) : 0;
            $round3 = (isValidDate($date_response2) && isValidDate($date_query_assessment3)) ? getDaysBetween($date_response2, $date_query_assessment3) : 0;
            $round4 = isValidDate($date_response3) ? getDaysBetween($date_response3, $datetoday) : 0;

            $days_processing = isValidDate($date_query_assessment1) ? ($round1 + $round2 + $round3 + $round4) : $round0;

            $is_backlog = false;
            if ($assessment_procedure === "FULL ASSESSMENT" && $days_processing > 365) {
                $is_backlog = true;
            } elseif (in_array($assessment_procedure, ["ABRIDGED", "RECOGNITION"]) && $days_processing > 90) {
                $is_backlog = true;
            }

            if ($is_backlog) {
                $counts['backlog']++;
            }
        }

        // Main stage switch
        switch ($current_stage) {
            case 1: // Not Assigned
                $counts['not_assigned']++;
                $days_processing = getDaysBetween($date_submitted, $datetoday);
                $days_allowed = getTimelineDays($pdo, 1, $assessment_procedure);
                updateStageCounters(1, $days_processing, $days_allowed, $stage_counters);
                break;

            case 2: // Screening
                $counts['screening']++;
                $days_processing = getDaysBetween($date_submitted, $datetoday);
                $days_allowed = getTimelineDays($pdo, 2, $assessment_procedure);
                updateStageCounters(2, $days_processing, $days_allowed, $stage_counters);
                break;

            case 3: // Pending First Assessment
                $counts['pending_first_assessment']++;
                $assignment_date = getAssignmentDate($pdo, $app_id, 3) ?? $date_submitted;
                $days_processing = getDaysBetween($assignment_date, $datetoday);
                $days_allowed = getTimelineDays($pdo, 3, $assessment_procedure);
                updateStageCounters(3, $days_processing, $days_allowed, $stage_counters);
                /*
                sendPendingAssessmentReminder($pdo, $app_id, $assignment_date, $days_allowed, $assessment_procedure, $reference_no, $datetoday, $weektoday, $monthtoday, $yeartoday);
                */
                break;

            case 4: // Pending Assessment
                $counts['assessment']++;
                $assignment_date = getAssignmentDate($pdo, $app_id, 4) ?? $date_first_assessment1;
                $days_processing = getDaysBetween($assignment_date, $datetoday);
                $days_allowed = getTimelineDays($pdo, 4, $assessment_procedure);
                updateStageCounters(4, $days_processing, $days_allowed, $stage_counters);
                break;

            case 5: // Peer Review
                $counts['peer_review']++;
                $days_processing = getDaysBetween($date_submitted, $datetoday);
                $days_allowed = getTimelineDays($pdo, 5, $assessment_procedure);
                updateStageCounters(5, $days_processing, $days_allowed, $stage_counters);
                break;

            case 7: // Not Assessed
                $counts['not_assessed']++;
                $days_processing = getDaysBetween($date_screening, $datetoday);
                $days_allowed = getTimelineDays($pdo, 7, $assessment_procedure);
                updateStageCounters(7, $days_processing, $days_allowed, $stage_counters);
                break;

            case 8: case 9: case 11: case 12: case 13: case 18:
                $counts['queried']++;
                $counts['second_assessment_completed_letter_not_sent']++;
                $start = isValidDate($date_second_assessment1) ? $date_second_assessment1 : $date_screening;
                $days_processing = getDaysBetween($start, $datetoday);
                $days_allowed = getTimelineDays($pdo, 8, $assessment_procedure);
                updateStageCounters(8, $days_processing, $days_allowed, $stage_counters);
                break;

            case 21: case 31: case 33:
                $counts['pending_first_assessment_add_data']++;
                $days_processing = getDaysBetween($date_submitted, $datetoday);
                $days_allowed = getTimelineDays($pdo, 21, $assessment_procedure);
                updateStageCounters(21, $days_processing, $days_allowed, $stage_counters);
                break;

            case 22: case 32: case 34:
                $counts['pending_second_assessment_add_data']++;
                $days_processing = getDaysBetween($date_submitted, $datetoday);
                $days_allowed = getTimelineDays($pdo, 22, $assessment_procedure);
                updateStageCounters(22, $days_processing, $days_allowed, $stage_counters);
                break;

            case 25: case 26: case 27: case 17: case 29: case 39:
                $counts['awaiting_applicant_feedback']++;
                $start = $date_submitted;
                if (in_array($current_stage, [25,17,29])) $start = $date_query_assessment1;
                elseif ($current_stage == 26) $start = $date_query_assessment2;
                elseif ($current_stage == 27) $start = $date_query_assessment3;
                $days_processing = getDaysBetween($start, $datetoday);
                $days_allowed = getTimelineDays($pdo, 25, $assessment_procedure);
                updateStageCounters(25, $days_processing, $days_allowed, $stage_counters);
                break;

            case 35:
                $counts['pending_second_assessment_pending']++;
                $days_processing = getDaysBetween($date_first_assessment1, $datetoday);
                $days_allowed = getTimelineDays($pdo, 35, $assessment_procedure);
                updateStageCounters(35, $days_processing, $days_allowed, $stage_counters);
                break;

            case 36:
                $counts['pending_first_assessment_pending_add_data']++;
                $days_processing = getDaysBetween($date_submitted, $datetoday);
                $days_allowed = getTimelineDays($pdo, 36, $assessment_procedure);
                updateStageCounters(36, $days_processing, $days_allowed, $stage_counters);
                break;

            case 37:
                $counts['pending_second_assessment_pending_add_data']++;
                $days_processing = getDaysBetween($date_submitted, $datetoday);
                $days_allowed = getTimelineDays($pdo, 37, $assessment_procedure);
                updateStageCounters(37, $days_processing, $days_allowed, $stage_counters);
                break;

            case 38:
                $counts['manager_report_review']++;
                $days_processing = getDaysBetween($date_submitted, $datetoday);
                $days_allowed = getTimelineDays($pdo, 38, $assessment_procedure);
                updateStageCounters(38, $days_processing, $days_allowed, $stage_counters);
                break;

            // Final states (no timeline)
            case 6:  $counts['under_approval']++; break;
            case 10: $counts['registered']++; break;
            case 14: $counts['rejected']++; break;
            case 16: $counts['onhold']++; break;
            case 19: $counts['pending_gmp']++; break;
            case 20: $counts['passed_peer_review_pending_gmp']++; break;
            case 30: $counts['expired_applications']++; break;
        }
    }

    // Finalize percentages for all tracked stages
    foreach ($stage_ids as $stage) {
        $c = $stage_counters[$stage];
        $total = 0;
        switch ($stage) {
            case 1: $total = $counts['not_assigned']; break;
            case 2: $total = $counts['screening']; break;
            case 3: $total = $counts['pending_first_assessment']; break;
            case 4: $total = $counts['assessment']; break;
            case 5: $total = $counts['peer_review']; break;
            case 7: $total = $counts['not_assessed']; break;
            case 8: $total = $counts['queried']; break;
            case 21: $total = $counts['pending_first_assessment_add_data']; break;
            case 22: $total = $counts['pending_second_assessment_add_data']; break;
            case 25: $total = $counts['awaiting_applicant_feedback']; break;
            case 35: $total = $counts['pending_second_assessment_pending']; break;
            case 36: $total = $counts['pending_first_assessment_pending_add_data']; break;
            case 37: $total = $counts['pending_second_assessment_pending_add_data']; break;
            case 38: $total = $counts['manager_report_review']; break;
        }
        $percentages[$stage] = calculatePercentages($total, $c['delayed'], $c['tobedelayed'], $c['ontime']);
        error_log("Stage $stage % → Delayed: {$percentages[$stage]['delayed']}, TBD: {$percentages[$stage]['tobedelayed']}, OnTime: {$percentages[$stage]['ontime']}");
    }

    // Export to dashboard variables
    foreach ($counts as $key => $val) {
        ${"count_$key"} = $val;
    }
    foreach ($stage_ids as $stage) {
        ${"percentage_delayed_stage$stage"} = $percentages[$stage]['delayed'];
        ${"percentage_tobedelayed_stage$stage"} = $percentages[$stage]['tobedelayed'];
        ${"percentage_ontime_stage$stage"} = $percentages[$stage]['ontime'];
    }

    error_log("application.php: Processing complete. Total apps: {$counts['all_applications']}, Backlog: {$counts['backlog']}");

} catch (Exception $e) {
    error_log("application.php ERROR: " . $e->getMessage());
}

// HELPER FUNCTIONS
function getTimelineDays($pdo, $stage, $pathway) {
    $stmt = $pdo->prepare("SELECT number_of_days FROM tbl_timelines WHERE status_id = ? AND assessment_pathway = ?");
    $stmt->execute([$stage, $pathway]);
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    return $row ? intval($row->number_of_days) : 0;
}

function getAssignmentDate($pdo, $app_id, $stage) {
    $stmt = $pdo->prepare("SELECT assignment_date FROM tbl_application_assignment WHERE application_id = ? AND stage_id = ? ORDER BY assignment_date DESC LIMIT 1");
    $stmt->execute([$app_id, $stage]);
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    return $row->assignment_date ?? null;
}

function updateStageCounters($stage, $days_processing, $days_allowed, &$counters) {
    if ($days_allowed <= 0) return;
    $half = round($days_allowed / 2);
    if ($days_processing > $days_allowed) {
        $counters[$stage]['delayed']++;
    } elseif ($days_processing < $half) {
        $counters[$stage]['ontime']++;
    } else {
        $counters[$stage]['tobedelayed']++;
    }
}
/*
function sendPendingAssessmentReminder($pdo, $app_id, $assign_date, $days_allowed, $procedure, $ref, $today, $week, $month, $year) {
    if (!$assign_date || $days_allowed <= 0) return;
    $days_used = getDaysBetween($assign_date, $today);
    $trigger = ($procedure === 'FULL ASSESSMENT' && $days_used - $days_allowed == 5) ||
               (in_array($procedure, ['ABRIDGED', 'RECOGNITION']) && $days_used - $days_allowed == 1);

    if (!$trigger) return;

    // Get staff
    $stmt = $pdo->prepare("SELECT s.staff_email, s.staff_names FROM tbl_application_assignment a JOIN tbl_staff s ON a.staff_id = s.staff_id WHERE a.application_id = ? AND a.stage_id = 3 LIMIT 1");
    $stmt->execute([$app_id]);
    $staff = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$staff) return;

    $to = $staff->staff_email;
    $subject = "Rwanda FDA - MA-Application Pending 1st Assessment $ref";
    $message = "The application with Reference No. $ref is pending in your account for 1st assessment. Please login to the Monitoring tool.";
    $headers = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>\r\nCC: dgasana@rwandafda.gov.rw";

    // Check duplicate
    $check = $pdo->prepare("SELECT 1 FROM tbl_hm_notifications WHERE application_id = ? AND notification_date = ? AND notification_type = 'Application Pending 1st Assessment'");
    $check->execute([$app_id, $today]);
    if ($check->fetch()) return;

    $sent = mail($to, $subject, strip_tags($message), $headers);
    if ($sent) {
        $pdo->prepare("INSERT INTO tbl_hm_notifications (...) VALUES (...)")->execute([...]);
    }
}
*/
?>