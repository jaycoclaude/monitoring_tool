<?php
$datetoday = date("Y-m-d");
$monthtoday = date("m");
$yeartoday = date("Y");
$weektoday = date("W");
// application_fsmil.php: Processes application data and calculates counts for dashboard display
// Initialize all counters to prevent undefined variable errors
$counts = [
    'pending_assessment' => 0,
    'under_assessment' => 0,
    'assessed' => 0,
    'queried' => 0,
    'query_letter_sent' => 0,
    'inspection_scheduling' => 0,
    'inspected' => 0,
    'iltc' => 0,
    'registered' => 0,
    'feedback_letter' => 0,
    'applied_for_reinspection' => 0,
    'applied_for_renewal' => 0,
    'rejected' => 0,
    'onhold' => 0,
    'withdrawn' => 0,
    'approved' => 0,
    'not_approved' => 0,
    'awaiting_payment' => 0,
    'closed' => 0,
    'expired' => 0,
    'backlog' => 0,
    'licensed_with_commitment' => 0,
    'all_applications' => 0,
    'all_applications_under_process' => 0,
        'expired_applications'          => 0,
    'awaiting_applicant_feedback'   => 0,
    'count_applied_for_reinspectiont' => 0,
];
$count_applied_for_reinspectiont =  0;
$count_expired_applications = 0;



// Dashboard helper (used later in fsmil_dashboard.php)
$applications_data = [
    'expired_applications'        => [],
    'awaiting_applicant_feedback' => [],
    'awaiting_gmp_inspection'     => [],
    'in_progress'                 => [],
    'approved'                    => [],
    'rejected'                    => [],
];

// Initialize percentage arrays for each stage
$percentages = [];
$stage_ids = [1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 14, 16];
foreach ($stage_ids as $stage) {
    $percentages[$stage] = [
        'delayed' => 0,
        'tobedelayed' => 0,
        'ontime' => 0,
    ];
}

// Debug: Log initialization
error_log('application_fsmil.php: Initialized counters and percentages');

// Helper function to calculate days between dates
function isValidDate($date) {
    if (!$date || $date === '0000-00-00' || $date === null) {
        return false;
    }
    $timestamp = strtotime($date);
    return $timestamp !== false && $timestamp > 0;
}

function getDaysBetween($start, $end) {
    if (isValidDate($start) && isValidDate($end)) {
        $days = (strtotime($end) - strtotime($start)) / 86400;
        return $days > 0 ? $days : 0; // prevent negative days
    }
    return 0;
}

// Helper function to calculate percentages
function calculatePercentages($total, $delayed, $tobedelayed, $ontime) {
    if ($total <= 0) {
        return ['delayed' => 0, 'tobedelayed' => 0, 'ontime' => 0];
    }

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
    $stmt = $pdo->prepare("SELECT * FROM tbl_hm_applications_premise_food");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Debug: Log fetched applications
    error_log('application_fsmil.php: Fetched ' . count($applications) . ' applications from tbl_hm_applications_premise_food');

    if (empty($applications)) {
        error_log('application_fsmil.php: No applications found in database');
    }

    foreach ($applications as $app) {
        // Extract fields with null coalescing
       $app_id = $app->hm_application_id ?? '';
        $current_stage = $app->application_current_stage ?? '';
        $premise_category = $app->premise_category ?? '';
        $date_submitted = $app->date_submitted ?? '';
        $date_screening = $app->date_screening ?? '';
        $date_assessment1 = $app->date_assessment1 ?? '';
        $date_assessment2 = $app->date_assessment2 ?? '';
        $date_assessment3 = $app->date_assessment3 ?? '';
        $date_inspection1 = $app->date_inspection1 ?? '';
        $date_inspection2 = $app->date_inspection2 ?? '';
        $date_inspection3 = $app->date_inspection3 ?? '';
        $date_response1 = $app->date_response1 ?? '';
        $date_response2 = $app->date_response2 ?? '';
        $date_response3 = $app->date_response3 ?? '';
        $date_query_assessment1 = $app->date_query_assessment1 ?? '';
        $date_query_assessment2 = $app->date_query_assessment2 ?? '';
        $date_query_assessment3 = $app->date_query_assessment3 ?? '';
        $reference_no = $app->reference_no ?? '';
        $tracking_no = $app->tracking_no ?? '';
        $assessment_procedure = $app->assessment_procedure ?? 'FULL ASSESSMENT';
        $tracking_no = $app->tracking_no ?? '';


        // Debug: Log current application
        error_log("application_fsmil.php: Processing app ID $app_id with stage $current_stage");

        // Increment counters based on stage
        switch ($current_stage) {
            case 1: // Not Assigned
                $counts['pending_assessment']++;
                // Calculate timeline metrics
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                // Debug: Log timelines
                error_log("application_fsmil.php: Fetched " . count($timelines) . " timelines for stage 1, app ID $app_id");

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_submitted, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[1] = calculatePercentages(
                    $counts['pending_assessment'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 1 percentages - Delayed: {$percentages[1]['delayed']}, To Be Delayed: {$percentages[1]['tobedelayed']}, On Time: {$percentages[1]['ontime']}");

                // Check for reminders
                if (isValidDate($date_submitted)) {
                    $days_processing = getDaysBetween($date_submitted, $datetoday);
                    if ($days_processing > ($days_allowed ?? 0)) {
                        error_log("application_fsmil.php: Reminder needed for app ID $app_id");
                    }
                }
                break;

            case 2:
            case 15: // Screening
                $counts['under_assessment']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                // Debug: Log timelines
                error_log("application_fsmil.php: Fetched " . count($timelines) . " timelines for stage $current_stage, app ID $app_id");

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_submitted, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[$current_stage] = calculatePercentages(
                    $counts['under_assessment'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage $current_stage percentages - Delayed: {$percentages[$current_stage]['delayed']}, To Be Delayed: {$percentages[$current_stage]['tobedelayed']}, On Time: {$percentages[$current_stage]['ontime']}");
                break;

            case 3: // Pending First Assessment
                $counts['assessed']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                // Fetch assignment
                $stmt_assign = $pdo->prepare("
                    SELECT assignment_date, staff_id 
                    FROM tbl_application_assignment 
                    WHERE application_id = :app_id AND stage_id = :stage_id
                ");
                $stmt_assign->execute([
                    ':app_id' => $app_id,
                    ':stage_id' => $current_stage
                ]);
                $assignments = $stmt_assign->fetchAll(PDO::FETCH_OBJ);

                $assignment_date = $date_submitted;
                $assigned_staff = '';
                if ($assignments) {
                    $assignment_date = $assignments[0]->assignment_date ?? $date_submitted;
                    $assigned_staff = $assignments[0]->staff_id ?? '';
                    // Debug: Log assignment
                    error_log("application_fsmil.php: Stage 3 assignment for app ID $app_id - Date: $assignment_date, Staff: $assigned_staff");
                }

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($assignment_date, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[3] = calculatePercentages(
                    $counts['assessed'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 3 percentages - Delayed: {$percentages[3]['delayed']}, To Be Delayed: {$percentages[3]['tobedelayed']}, On Time: {$percentages[3]['ontime']}");

                // Send notification if needed
                if ($assigned_staff && isValidDate($assignment_date)) {
                    $days_processing = getDaysBetween($assignment_date, $datetoday);
                    if (($assessment_procedure == 'FULL ASSESSMENT' && $days_processing - $days_allowed == 5) ||
                        (in_array($assessment_procedure, ['ABRIDGED', 'RECOGNITION']) && $days_processing - $days_allowed == 1)) {
                        $stmt_staff = $pdo->prepare("
                            SELECT staff_email, staff_names 
                            FROM tbl_staff 
                            WHERE staff_id = :staff_id
                        ");
                        $stmt_staff->execute([':staff_id' => $assigned_staff]);
                        $staff = $stmt_staff->fetch(PDO::FETCH_OBJ);

                        if ($staff) {
                            $send_to = $staff->staff_email;
                            $notification_type = 'Application Pending 1st Assessment';
                            $subject = "Rwanda FDA notification - MA-Application Pending 1st Assessment $reference_no";
                            $message = "The application with Reference No. $reference_no is pending in your account for 1st assessment. Please login to the Monitoring tool for action.";
                            $headers = "From: Rwanda FDA Notification<notification@rwandafda.gov.rw>\r\nCC: dgasana@rwandafda.gov.rw";

                            // Check for existing notification
                            $stmt_check = $pdo->prepare("
                                SELECT * 
                                FROM tbl_hm_notifications 
                                WHERE notification_to = :to 
                                  AND notification_date = :date 
                                  AND application_id = :id 
                                  AND notification_type = :type 
                                  AND notification_to_category = 'Staff'
                            ");
                            $stmt_check->execute([
                                ':to' => $send_to,
                                ':date' => $datetoday,
                                ':id' => $app_id,
                                ':type' => $notification_type
                            ]);
                            $existing = $stmt_check->fetchAll(PDO::FETCH_OBJ);

                            if (empty($existing)) {
                                $sent = mail($send_to, $subject, strip_tags($message), $headers);
                                error_log("application_fsmil.php: Notification " . ($sent ? "sent" : "failed") . " to $send_to for app ID $app_id");

                                // Insert notification
                                $stmt_insert = $pdo->prepare("
                                    INSERT INTO tbl_hm_notifications (
                                        notification_to, notification_subject, notification_message, 
                                        notification_headers, notification_date, notification_week, 
                                        notification_month, notification_year, notification_type, 
                                        notification_to_category, application_id
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ");
                                $stmt_insert->execute([
                                    $send_to, $subject, $message, $headers, $datetoday, $weektoday,
                                    $monthtoday, $yeartoday, $notification_type, 'Staff', $app_id
                                ]);
                                error_log("application_fsmil.php: Notification inserted for app ID $app_id");
                            }
                        }
                    }
                }
                break;

            case 4: // Assessment
                $counts['queried']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $stmt_assign = $pdo->prepare("
                    SELECT assignment_date 
                    FROM tbl_application_assignment 
                    WHERE application_id = :app_id AND stage_id = :stage_id
                ");
                $stmt_assign->execute([
                    ':app_id' => $app_id,
                    ':stage_id' => $current_stage
                ]);
                $assignments = $stmt_assign->fetchAll(PDO::FETCH_OBJ);

                $assignment_date = $assignments ? ($assignments[0]->assignment_date ?? $date_first_assessment1) : $date_first_assessment1;

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($assignment_date, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[4] = calculatePercentages(
                    $counts['queried'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 4 percentages - Delayed: {$percentages[4]['delayed']}, To Be Delayed: {$percentages[4]['tobedelayed']}, On Time: {$percentages[4]['ontime']}");
                break;

            case 5: // Query letter sent
                $counts['query_letter_sent']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_submitted, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[5] = calculatePercentages(
                    $counts['query_letter_sent'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 5 percentages - Delayed: {$percentages[5]['delayed']}, To Be Delayed: {$percentages[5]['tobedelayed']}, On Time: {$percentages[5]['ontime']}");
                break;

            case 6: // Inspection Scheduling
                $counts['inspection_scheduling']++;
                error_log("application_fsmil.php: Incremented under_approval to {$counts['applied_for_reinspection']}");
                break;

            case 7: // Inspected
                $counts['inspected']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_screening, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[7] = calculatePercentages(
                    $counts['inspected'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 7 percentages - Delayed: {$percentages[7]['delayed']}, To Be Delayed: {$percentages[7]['tobedelayed']}, On Time: {$percentages[7]['ontime']}");
                break;

            case 8: //ILTC
                $counts['iltc']++;
                error_log("application_fsmil.php: Incremented under_approval to {$counts['iltc']}");
                break;
            case 9:
            case 11:
            case 12:
                $counts['applied_for_reinspection']++;
                error_log("application_fsmil.php: Incremented under_approval to {$counts['applied_for_reinspection']}");
                break;
            case 13:
            case 14: //Not Approved
                 $counts['not_approved']++;
                error_log("application_fsmil.php: Incremented under_approval to {$counts['not_approved']}");
                break;
            case 14: //Approved
                 $counts['approved']++;
                error_log("application_fsmil.php: Incremented under_approval to {$counts['approved']}");
                break;
            case 18: // Queried or Second Assessment Completed
                //$counts['inspected']++;
                $counts['second_assessment_completed_letter_not_sent']++;
                $date_queried = isValidDate($date_second_assessment1) ? $date_second_assessment1 : $date_screening;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_queried, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[8] = calculatePercentages(
                    $counts['queried'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 8 percentages - Delayed: {$percentages[8]['delayed']}, To Be Delayed: {$percentages[8]['tobedelayed']}, On Time: {$percentages[8]['ontime']}");
                break;

            case 10: // Registered
                $counts['registered']++;
                error_log("application_fsmil.php: Incremented registered to {$counts['registered']}");
                break;

            case 14: // Rejected
                $counts['rejected']++;
                error_log("application_fsmil.php: Incremented rejected to {$counts['rejected']}");
                break;

            case 16: // On Hold
                $counts['onhold']++;
                error_log("application_fsmil.php: Incremented onhold to {$counts['onhold']}");
                break;

            case 19: // Pending GMP
                $counts['pending_gmp']++;
                error_log("application_fsmil.php: Incremented pending_gmp to {$counts['pending_gmp']}");
                break;

            case 20: // Passed Peer Review Pending GMP
                $counts['passed_peer_review_pending_gmp']++;
                error_log("application_fsmil.php: Incremented passed_peer_review_pending_gmp to {$counts['passed_peer_review_pending_gmp']}");
                break;

            case 21:
            case 31:
            case 33: // Pending First Assessment Additional Data
                $counts['pending_first_assessment_add_data']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_submitted, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[21] = calculatePercentages(
                    $counts['pending_first_assessment_add_data'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 21 percentages - Delayed: {$percentages[21]['delayed']}, To Be Delayed: {$percentages[21]['tobedelayed']}, On Time: {$percentages[21]['ontime']}");
                break;

            case 22:
            case 32:
            case 34: // Pending Second Assessment Additional Data
                $counts['pending_second_assessment_add_data']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                $applications_data = [
    'expired_applications' => [],
    'awaiting_applicant_feedback' => [],
    // Add other categories if used later
    'awaiting_gmp_inspection' => [],
    'in_progress' => [],
    'approved' => [],
    'rejected' => [],
];


                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_submitted, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[22] = calculatePercentages(
                    $counts['pending_second_assessment_add_data'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 22 percentages - Delayed: {$percentages[22]['delayed']}, To Be Delayed: {$percentages[22]['tobedelayed']}, On Time: {$percentages[22]['ontime']}");
                break;

            case 25:
            case 26:
            case 27:
            case 17:
            case 29:
            case 39: // Awaiting Applicant Feedback
                $counts['awaiting_applicant_feedback']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);

                    $start_date = $date_submitted;
                    if (in_array($current_stage, [25, 17, 29])) {
                        $start_date = $date_query_assessment1;
                    } elseif ($current_stage == 26) {
                        $start_date = $date_query_assessment2;
                    } elseif ($current_stage == 27) {
                        $start_date = $date_query_assessment3;
                    } elseif ($current_stage == 39) {
                        $start_date = isValidDate($date_query_assessment1) ? $date_query_assessment1 : $date_submitted;
                    }

                    $days_processing = getDaysBetween($start_date, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[25] = calculatePercentages(
                    $counts['awaiting_applicant_feedback'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 25 percentages - Delayed: {$percentages[25]['delayed']}, To Be Delayed: {$percentages[25]['tobedelayed']}, On Time: {$percentages[25]['ontime']}");
                break;

            case 30: // Expired Applications
                $counts['expired_applications']++;
                error_log("application_fsmil.php: Incremented expired_applications to {$counts['expired_applications']}");
                break;

            case 35: // Pending Second Assessment Pending
                $counts['pending_second_assessment_pending']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_first_assessment1, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[35] = calculatePercentages(
                    $counts['pending_second_assessment_pending'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 35 percentages - Delayed: {$percentages[35]['delayed']}, To Be Delayed: {$percentages[35]['tobedelayed']}, On Time: {$percentages[35]['ontime']}");
                break;

            case 36: // Pending First Assessment Pending Additional Data
                $counts['pending_first_assessment_pending_add_data']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_submitted, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[36] = calculatePercentages(
                    $counts['pending_first_assessment_pending_add_data'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 36 percentages - Delayed: {$percentages[36]['delayed']}, To Be Delayed: {$percentages[36]['tobedelayed']}, On Time: {$percentages[36]['ontime']}");
                break;

            case 37: // Pending Second Assessment Pending Additional Data
                $counts['pending_second_assessment_pending_add_data']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_submitted, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[37] = calculatePercentages(
                    $counts['pending_second_assessment_pending_add_data'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 37 percentages - Delayed: {$percentages[37]['delayed']}, To Be Delayed: {$percentages[37]['tobedelayed']}, On Time: {$percentages[37]['ontime']}");
                break;

            case 38: // Manager Report Review
                $counts['manager_report_review']++;
                $stmt_timeline = $pdo->prepare("
                    SELECT number_of_days 
                    FROM tbl_timelines 
                    WHERE status_id = :status_id AND assessment_pathway = :pathway
                ");
                $stmt_timeline->execute([
                    ':status_id' => $current_stage,
                    ':pathway' => $assessment_procedure
                ]);
                $timelines = $stmt_timeline->fetchAll(PDO::FETCH_OBJ);

                $delayed = 0;
                $tobedelayed = 0;
                $ontime = 0;

                foreach ($timelines as $timeline) {
                    $days_allowed = intval($timeline->number_of_days ?? 0);
                    $half_days = round($days_allowed / 2);
                    $days_processing = getDaysBetween($date_submitted, $datetoday);

                    if ($days_processing > $days_allowed) {
                        $delayed++;
                    } elseif ($days_processing < $half_days) {
                        $ontime++;
                    } else {
                        $tobedelayed++;
                    }
                }

                $percentages[38] = calculatePercentages(
                    $counts['manager_report_review'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application_fsmil.php: Stage 38 percentages - Delayed: {$percentages[38]['delayed']}, To Be Delayed: {$percentages[38]['tobedelayed']}, On Time: {$percentages[38]['ontime']}");
                break;
        }

        // Calculate backlog for non-finalized applications
        //if (!in_array($current_stage, [10, 14, 16, 23, 28, 30])) { 
       if (in_array($current_stage, [1,2,3,4,5,6,7,8,12,13,24])) {
            $counts['all_applications_under_process']++;
           // if (isValidDate($date_submitted)) {
           if (isValidDate($date_submitted) && !in_array($current_stage, [17,25,26,27,29,39])) {
    // Calculate all the "rounds" (same as SQL logic)
    $round0 = getDaysBetween($app->date_submitted, $datetoday);

    // round1: from date_submitted → date_query_assessment1
    $round1 = (isValidDate($app->date_query_assessment1))
        ? getDaysBetween($app->date_submitted, $app->date_query_assessment1)
        : 0;

    // round2: from date_response1 → (date_query_assessment2 OR today)
    $round2 = (isValidDate($app->date_response1))
        ? (isValidDate($app->date_query_assessment2)
            ? getDaysBetween($app->date_response1, $app->date_query_assessment2)
            : getDaysBetween($app->date_response1, $datetoday))
        : 0;

    // round3: from date_response2 → date_query_assessment3 (if both valid)
    $round3 = (isValidDate($app->date_response2) && isValidDate($app->date_query_assessment3))
        ? getDaysBetween($app->date_response2, $app->date_query_assessment3)
        : 0;

    // round4: from date_response3 → today (if valid)
    $round4 = (isValidDate($app->date_response3))
        ? getDaysBetween($app->date_response3, $datetoday)
        : 0;

    // Final processing days (match SQL’s CASE logic)
    if (isValidDate($app->date_query_assessment1)) {
        // Application had at least one query cycle
        $days_processing = $round1 + $round2 + $round3 + $round4;
    } else {
        // No query yet — just days since submission
        $days_processing = $round0;
    }

    // Determine backlog thresholds (same as SQL conditions)
    $is_backlog = false;
    if ($assessment_procedure === "FULL ASSESSMENT" && $days_processing > 365) {
        $is_backlog = true;
    } elseif (
        in_array($assessment_procedure, ["ABRIDGED", "RECOGNITION"]) &&
        $days_processing > 90
    ) {
        $is_backlog = true;
    }

    // Count backlog if applicable
    if ($is_backlog) {
        $counts['backlog']++;
        error_log("application_fsmil.php: Incremented backlog to {$counts['backlog']} for app ID $app_id");
    }
}
 else {
                error_log("application_fsmil.php: Invalid date_submitted for app ID $app_id - skipping backlog");
            }
        }

        // Increment total applications
        $counts['all_applications']++;
        error_log("application_fsmil.php: Incremented all_applications to {$counts['all_applications']}");
    }

    // Assign counts to individual variables for dashboard compatibility
    $count_pending_assessment = $counts['pending_assessment'];
    $count_under_assessment = $counts['under_assessment'];
    $count_assessed = $counts['assessed'];
    $count_queried = $counts['queried'];
    $count_query_letter_sent = $counts['query_letter_sent'];
    $count_inspection_scheduling = $counts['inspection_scheduling'];
    $count_inspected = $counts['inspected'];
    $count_iltc = $counts['iltc'];
    $count_registered = $counts['registered'];
    $count_feedback_letter = $counts['feedback_letter'];
    $count_applied_for_reinspection = $counts['applied_for_reinspection'];
    $count_applied_for_renewal = $counts['applied_for_renewal'];
    $count_rejected = $counts['rejected'];
    $count_onhold = $counts['onhold'];
    $count_withdrawn = $counts['withdrawn'];
    $count_approved = $counts['approved'];
    $count_not_approved = $counts['not_approved'];
    $count_awaiting_payment = $counts['awaiting_payment'];
    $count_closed = $counts['closed'];
    $count_expired = $counts['expired'];
    $count_backlog = $counts['backlog'];
    $count_licensed_with_commitment = $counts['licensed_with_commitment'];
    $count_all_applications = $counts['all_applications'];
    $count_all_applications_under_process=$counts['all_applications_under_process'];

    // Assign percentages to stage-specific variables
    foreach ($stage_ids as $stage) {
        ${"percentage_not_assigned_delayed$stage"} = $percentages[$stage]['delayed'];
        ${"percentage_not_assigned_tobedelayed$stage"} = $percentages[$stage]['tobedelayed'];
        ${"percentage_not_assigned_ontime$stage"} = $percentages[$stage]['ontime'];
    }

    // Debug: Log final counts
    error_log('application_fsmil.php: Final counts - ' . json_encode($counts));
    error_log('application_fsmil.php: Final percentages - ' . json_encode($percentages));

} catch (PDOException $e) {
    error_log('application_fsmil.php: Database error - ' . $e->getMessage());
} catch (Exception $e) {
    error_log('application_fsmil.php: General error - ' . $e->getMessage());
}
?>