<?php
$datetoday = date("Y-m-d");
$user_id   = $_GET['cur'] ?? '';
$passcode  = $_GET['app'] ?? '';

// helper functions
function isValidDate($date) {
    return !empty($date) && $date !== "0000-00-00";
}

function getDaysBetween($start, $end) {
    if (isValidDate($start) && isValidDate($end)) {
        $days = (strtotime($end) - strtotime($start)) / 86400;
        return $days > 0 ? $days : 0; // prevent negative days
    }
    return 0;
}

// verify user
$applications_user = $wpdb->get_results(
    $wpdb->prepare("SELECT * FROM tbl_hm_users WHERE user_id=%s AND user_passcode=%s", $user_id, $passcode)
);

if ($applications_user) {
    $applications_req2 = $wpdb->get_results(
        "SELECT * FROM tbl_hm_applications 
         WHERE application_current_stage NOT IN (10,14,28,30,23,16)
		
         ORDER BY date_submitted"
    );

    $i = 0;
?>
<div class="table-responsive">
<table class="table table-striped">
<tr>
<td colspan="9">
<a href="<?php echo esc_url(add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4669)))); ?>">Back to Dashboard</a>
</td>
</tr>
<tr>
    <td><b>No.</b></td>
    <td><b>Reference No.</b></td>
    <td><b>Brand Name</b></td>
    <td><b>Generic Name</b></td>
    <td><b>Assessment Pathway</b></td>
    <td><b>Application Date</b></td>
    <td><b>Application Stage</b></td>
    <td><b>Time monitor (days)</b></td>
</tr>

<?php
    foreach ($applications_req2 as $app) {
        // fetch stage description
        $status_result = $wpdb->get_results(
            $wpdb->prepare("SELECT status_description FROM tbl_hm_applications_status WHERE status_id=%d", $app->application_current_stage)
        );
        $status_description = $status_result ? $status_result[0]->status_description : '';

    if (!isValidDate($app->date_submitted)) {
    continue; // skip apps with no submission date
	}

        // multi-round calculation
        $round0 = getDaysBetween($app->date_submitted, $datetoday);
        $round1 = getDaysBetween($app->date_submitted, $app->date_query_assessment1);
        $round2 = getDaysBetween($app->date_response1, $app->date_query_assessment2);
        $round3 = getDaysBetween($app->date_response2, $app->date_query_assessment3);
        $round4 = getDaysBetween($app->date_response3, $datetoday);

        // total backlog logic
       $days_backlog = 0;

		if ($round1 > 0) {
			$days_backlog = $round1 + $round2 + $round3 + $round4;
		} else {
			$days_backlog = $round0; // only include Round0 if no assessment rounds
		}
		

        // backlog thresholds
        $is_backlog = false;
        if ($app->assessment_procedure == "FULL ASSESSMENT" && $days_backlog > 365) {
            $is_backlog = true;
        } elseif (($app->assessment_procedure == "ABRIDGED" || $app->assessment_procedure == "RECOGNITION") && $days_backlog > 90) {
            $is_backlog = true;
        }

        if ($is_backlog) {
            $i++;
            echo '<tr>
                <td><a href="'.esc_url(add_query_arg('ab_num', $app->hm_application_id, add_query_arg('cur', $user_id, add_query_arg('app', $passcode, get_permalink(4750))))).'" target="_blank">'. $i .'</a></td>
                <td>'.esc_html($app->reference_no).'</td>
                <td>'.esc_html($app->brand_name).'</td>
                <td>'.esc_html($app->hm_generic_name).'</td>
                <td>'.esc_html($app->assessment_procedure).'</td>
                <td>'.esc_html($app->date_submitted).'</td>
                <td>'.esc_html($status_description).'</td>
                <td>'.number_format($days_backlog).'</td>
            </tr>';
        }
    }
?>
</table>
</div>
<?php
} else {
    echo '<div class="alert alert-danger"><p>Not allowed to access. Please login</p></div>';
}
?>