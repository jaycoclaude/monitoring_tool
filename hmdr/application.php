<?php
// application.php: Processes application data and calculates counts for dashboard display
// Initialize all counters to prevent undefined variable errors
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

// Initialize percentage arrays for each stage
$percentages = [];
$stage_ids = [1, 2, 3, 4, 5, 7, 8, 15, 21, 22, 25, 35, 36, 37, 38];
foreach ($stage_ids as $stage) {
    $percentages[$stage] = [
        'delayed' => 0,
        'tobedelayed' => 0,
        'ontime' => 0,
    ];
}

// Debug: Log initialization
error_log('application.php: Initialized counters and percentages');

// Helper function to calculate days between dates
// function getDaysBetween($start_date, $end_date) {
//     if (!isValidDate($start_date) || !isValidDate($end_date)) {
//         return 0;
//     }
//     return (strtotime($end_date) - strtotime($start_date)) / 86400;
// }

// // Helper function to validate date
// function isValidDate($date) {
//     return !empty($date) && $date !== '0000-00-00' && strtotime($date) !== false;
// }

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
    $stmt = $pdo->prepare("SELECT * FROM tbl_hm_applications");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Debug: Log fetched applications
    error_log('application.php: Fetched ' . count($applications) . ' applications from tbl_hm_applications');

    if (empty($applications)) {
        error_log('application.php: No applications found in database');
    }

    foreach ($applications as $app) {
        // Extract fields with null coalescing
        $app_id = $app->hm_application_id ?? '';
        $current_stage = $app->application_current_stage ?? '';
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

        // Debug: Log current application
        error_log("application.php: Processing app ID $app_id with stage $current_stage");

        // Increment counters based on stage
        switch ($current_stage) {
            case 1: // Not Assigned
                $counts['not_assigned']++;
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
                error_log("application.php: Fetched " . count($timelines) . " timelines for stage 1, app ID $app_id");

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
                    $counts['not_assigned'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application.php: Stage 1 percentages - Delayed: {$percentages[1]['delayed']}, To Be Delayed: {$percentages[1]['tobedelayed']}, On Time: {$percentages[1]['ontime']}");

                // Check for reminders
                if (isValidDate($date_submitted)) {
                    $days_processing = getDaysBetween($date_submitted, $datetoday);
                    if ($days_processing > ($days_allowed ?? 0)) {
                        error_log("application.php: Reminder needed for app ID $app_id");
                    }
                }
                break;

            case 2:
            case 15: // Screening
                $counts['screening']++;
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
                error_log("application.php: Fetched " . count($timelines) . " timelines for stage $current_stage, app ID $app_id");

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
                    $counts['screening'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application.php: Stage $current_stage percentages - Delayed: {$percentages[$current_stage]['delayed']}, To Be Delayed: {$percentages[$current_stage]['tobedelayed']}, On Time: {$percentages[$current_stage]['ontime']}");
                break;

            case 3: // Pending First Assessment
                $counts['pending_first_assessment']++;
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
                    error_log("application.php: Stage 3 assignment for app ID $app_id - Date: $assignment_date, Staff: $assigned_staff");
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
                    $counts['pending_first_assessment'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application.php: Stage 3 percentages - Delayed: {$percentages[3]['delayed']}, To Be Delayed: {$percentages[3]['tobedelayed']}, On Time: {$percentages[3]['ontime']}");

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
                                error_log("application.php: Notification " . ($sent ? "sent" : "failed") . " to $send_to for app ID $app_id");

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
                                error_log("application.php: Notification inserted for app ID $app_id");
                            }
                        }
                    }
                }
                break;

            case 4: // Assessment
                $counts['assessment']++;
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
                    $counts['assessment'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application.php: Stage 4 percentages - Delayed: {$percentages[4]['delayed']}, To Be Delayed: {$percentages[4]['tobedelayed']}, On Time: {$percentages[4]['ontime']}");
                break;

            case 5: // Peer Review
                $counts['peer_review']++;
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
                    $counts['peer_review'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application.php: Stage 5 percentages - Delayed: {$percentages[5]['delayed']}, To Be Delayed: {$percentages[5]['tobedelayed']}, On Time: {$percentages[5]['ontime']}");
                break;

            case 6: // Under Approval
                $counts['under_approval']++;
                error_log("application.php: Incremented under_approval to {$counts['under_approval']}");
                break;

            case 7: // Not Assessed
                $counts['not_assessed']++;
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
                    $counts['not_assessed'],
                    $delayed,
                    $tobedelayed,
                    $ontime
                );

                // Debug: Log percentages
                error_log("application.php: Stage 7 percentages - Delayed: {$percentages[7]['delayed']}, To Be Delayed: {$percentages[7]['tobedelayed']}, On Time: {$percentages[7]['ontime']}");
                break;

            case 8:
            case 9:
            case 11:
            case 12:
            case 13:
            case 18: // Queried or Second Assessment Completed
                $counts['queried']++;
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
                error_log("application.php: Stage 8 percentages - Delayed: {$percentages[8]['delayed']}, To Be Delayed: {$percentages[8]['tobedelayed']}, On Time: {$percentages[8]['ontime']}");
                break;

            case 10: // Registered
                $counts['registered']++;
                error_log("application.php: Incremented registered to {$counts['registered']}");
                break;

            case 14: // Rejected
                $counts['rejected']++;
                error_log("application.php: Incremented rejected to {$counts['rejected']}");
                break;

            case 16: // On Hold
                $counts['onhold']++;
                error_log("application.php: Incremented onhold to {$counts['onhold']}");
                break;

            case 19: // Pending GMP
                $counts['pending_gmp']++;
                error_log("application.php: Incremented pending_gmp to {$counts['pending_gmp']}");
                break;

            case 20: // Passed Peer Review Pending GMP
                $counts['passed_peer_review_pending_gmp']++;
                error_log("application.php: Incremented passed_peer_review_pending_gmp to {$counts['passed_peer_review_pending_gmp']}");
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
                error_log("application.php: Stage 21 percentages - Delayed: {$percentages[21]['delayed']}, To Be Delayed: {$percentages[21]['tobedelayed']}, On Time: {$percentages[21]['ontime']}");
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
                error_log("application.php: Stage 22 percentages - Delayed: {$percentages[22]['delayed']}, To Be Delayed: {$percentages[22]['tobedelayed']}, On Time: {$percentages[22]['ontime']}");
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
                error_log("application.php: Stage 25 percentages - Delayed: {$percentages[25]['delayed']}, To Be Delayed: {$percentages[25]['tobedelayed']}, On Time: {$percentages[25]['ontime']}");
                break;

            case 30: // Expired Applications
                $counts['expired_applications']++;
                error_log("application.php: Incremented expired_applications to {$counts['expired_applications']}");
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
                error_log("application.php: Stage 35 percentages - Delayed: {$percentages[35]['delayed']}, To Be Delayed: {$percentages[35]['tobedelayed']}, On Time: {$percentages[35]['ontime']}");
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
                error_log("application.php: Stage 36 percentages - Delayed: {$percentages[36]['delayed']}, To Be Delayed: {$percentages[36]['tobedelayed']}, On Time: {$percentages[36]['ontime']}");
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
                error_log("application.php: Stage 37 percentages - Delayed: {$percentages[37]['delayed']}, To Be Delayed: {$percentages[37]['tobedelayed']}, On Time: {$percentages[37]['ontime']}");
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
                error_log("application.php: Stage 38 percentages - Delayed: {$percentages[38]['delayed']}, To Be Delayed: {$percentages[38]['tobedelayed']}, On Time: {$percentages[38]['ontime']}");
                break;
        }

        // Calculate backlog for non-finalized applications
        if (!in_array($current_stage, [10, 14, 16, 23, 28, 30])) {
            $counts['all_applications_under_process']++;
            if (isValidDate($date_submitted)) {
                $days_round0 = getDaysBetween($date_submitted, $datetoday);
                $days_round1 = getDaysBetween($date_submitted, $date_query_assessment1);
                $days_round2 = getDaysBetween($date_response1, $date_query_assessment2);
                $days_round3 = getDaysBetween($date_response2, $date_query_assessment3);
                $days_round4 = getDaysBetween($date_response3, $datetoday);

                $days_processing = $days_round1 > 0 ? ($days_round1 + $days_round2 + $days_round3 + $days_round4) : $days_round0;

                $is_backlog = false;
                if ($assessment_procedure == "FULL ASSESSMENT" && $days_processing > 365) {
                    $is_backlog = true;
                } elseif (in_array($assessment_procedure, ["ABRIDGED", "RECOGNITION"]) && $days_processing > 90) {
                    $is_backlog = true;
                }

                if ($is_backlog) {
                    $counts['backlog']++;
                    error_log("application.php: Incremented backlog to {$counts['backlog']} for app ID $app_id");
                }
            } else {
                error_log("application.php: Invalid date_submitted for app ID $app_id - skipping backlog");
            }
        }

        // Increment total applications
        $counts['all_applications']++;
        error_log("application.php: Incremented all_applications to {$counts['all_applications']}");
    }

    // Assign counts to individual variables for dashboard compatibility
    $count_all_applications = $counts['all_applications'];
    $count_backlog = $counts['backlog'];
    $count_under_approval = $counts['under_approval'];
    $count_not_assigned = $counts['not_assigned'];
    $count_peer_review = $counts['peer_review'];
    $count_queried = $counts['queried'];
    $count_assessment = $counts['assessment'];
    $count_screening = $counts['screening'];
    $count_all_applications_under_process = $counts['all_applications_under_process'];
    $count_not_assessed = $counts['not_assessed'];
    $count_registered = $counts['registered'];
    $count_second_assessment_completed = $counts['second_assessment_completed'];
    $count_onhold = $counts['onhold'];
    $count_pending_gmp = $counts['pending_gmp'];
    $count_rejected = $counts['rejected'];
    $count_passed_peer_review_pending_gmp = $counts['passed_peer_review_pending_gmp'];
    $count_pending_first_assessment_add_data = $counts['pending_first_assessment_add_data'];
    $count_pending_second_assessment_add_data = $counts['pending_second_assessment_add_data'];
    $count_awaiting_applicant_feedback = $counts['awaiting_applicant_feedback'];
    $count_second_assessment_completed_letter_not_sent = $counts['second_assessment_completed_letter_not_sent'];
    $count_pending_first_assessment = $counts['pending_first_assessment'];
    $count_expired_applications = $counts['expired_applications'];
    $count_pending_first_assessment_pending = $counts['pending_first_assessment_pending'];
    $count_pending_second_assessment_pending = $counts['pending_second_assessment_pending'];
    $count_pending_first_assessment_pending_add_data = $counts['pending_first_assessment_pending_add_data'];
    $count_pending_second_assessment_pending_add_data = $counts['pending_second_assessment_pending_add_data'];
    $count_manager_report_review = $counts['manager_report_review'];

    // Assign percentages to stage-specific variables
    foreach ($stage_ids as $stage) {
        ${"percentage_not_assigned_delayed$stage"} = $percentages[$stage]['delayed'];
        ${"percentage_not_assigned_tobedelayed$stage"} = $percentages[$stage]['tobedelayed'];
        ${"percentage_not_assigned_ontime$stage"} = $percentages[$stage]['ontime'];
    }

    // Debug: Log final counts
    error_log('application.php: Final counts - ' . json_encode($counts));
    error_log('application.php: Final percentages - ' . json_encode($percentages));

} catch (PDOException $e) {
    error_log('application.php: Database error - ' . $e->getMessage());
} catch (Exception $e) {
    error_log('application.php: General error - ' . $e->getMessage());
}
?>