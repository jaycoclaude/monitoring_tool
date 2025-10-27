<?php
$datetoday=date("Y-m-d");
$user_id=$_GET['cur'];
$passcode=$_GET['app'];
//echo $datetoday;
$applications_user = $wpdb->get_results("SELECT * from tbl_hm_users where user_id='$user_id' and user_passcode='$passcode'");

if($applications_user)
{
?>
<head>
  <title>MA- Monitoring-Expired</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
#para1 {
  outline: #8FBC8F solid 5px;
  margin: auto;  
  padding: 20px;
  text-align: center;
}
#myDiv {
  //border: 1px solid red;
  margin-left: 0px;
}
.status-card {
  margin-bottom: 20px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.status-card .panel-heading {
  background-color: #337ab7;
  color: white;
}
.well-ontime {
  background-color: #73B194;
  color: white;
}
.well-tobedelayed {
  background-color: #ece2c2;
  color: #333;
}
.well-delayed {
  background-color: #D59281;
  color: white;
}
</style>
<script>
$(document).ready(function(){
  $("#myInput").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#myTable tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });
});
</script>

</head>
<?php
// Initialize counters for the chart
$total_not_assigned_ontime2 = 0;
$total_not_assigned_delayed2 = 0;
$total_not_assigned_tobedelayed2 = 0;
$count_screening = 0;

$count_expired_products=0;
$count_about_to_expire_products=0;
$count_active=0;
$count_under_renewal=0;

$applications_req2 = $wpdb->get_results("SELECT * from tbl_hm_applications where application_current_stage=2 order by date_submitted");
if(isset($_GET['by'])){
    $letter=$_GET['by'];
    if ($letter=='All') {
        $applications_req2 = $wpdb->get_results("SELECT * from tbl_hm_applications where application_current_stage=2 order by date_submitted");
    } else {
        $applications_req2 = $wpdb->get_results("SELECT * from tbl_hm_applications WHERE brand_name LIKE '" . $letter . "%' and application_current_stage=2 order by brand_name");
    }
}

// First loop to calculate chart data
foreach($applications_req2 as $application_req2) {
    $hm_application_id = $application_req2->hm_application_id;
    $date_submitted = $application_req2->date_submitted;
    $assessment_procedure = $application_req2->assessment_procedure;
    $application_current_stage = $application_req2->application_current_stage;
    
    $applications_req_chart = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage' and assessment_pathway='$assessment_procedure'");
    
    foreach($applications_req_chart as $application_req_chart) {
        $number_of_days_chart = intval($application_req_chart->number_of_days);
        $half_days = round($number_of_days_chart/2);
        $days_processing_chart = (strtotime($datetoday) - strtotime($date_submitted)) / 60 / 60 / 24;
        
        if($days_processing_chart > $number_of_days_chart) {
            $total_not_assigned_delayed2 += 1; 
        } 
        elseif($days_processing_chart < ($number_of_days_chart/2)) {
            $total_not_assigned_ontime2 += 1;
        }
        elseif(($days_processing_chart >= ($number_of_days_chart/2)) && ($days_processing_chart <= $number_of_days_chart)) {
            $total_not_assigned_tobedelayed2 += 1;
        }
    }
    $count_screening++;
}

// Calculate percentages
$total_not_assigned = $count_screening;
$percentage_not_assigned_delayed2 = $total_not_assigned > 0 ? round(($total_not_assigned_delayed2/$total_not_assigned)*100) : 0;
$percentage_not_assigned_tobedelayed2 = $total_not_assigned > 0 ? round(($total_not_assigned_tobedelayed2/$total_not_assigned)*100) : 0;
$percentage_not_assigned_ontime2 = $total_not_assigned > 0 ? round(($total_not_assigned_ontime2/$total_not_assigned)*100) : 0;
?>

<!-- Status Summary Card with Bar Chart -->
<div class="panel panel-default status-card">
    <div class="panel-heading">
        <h3 class="panel-title">Application Status Summary (Screening Stage)</h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <!-- Chart Column -->
            <div class="col-md-8">
                <canvas id="statusBarChart" style="height: 250px;"></canvas>
            </div>
            
            <!-- Stats Column -->
            <div class="col-md-4">
                <div class="well well-ontime">
                    <h4>On Time: <?php echo $total_not_assigned_ontime2; ?> (<?php echo $percentage_not_assigned_ontime2; ?>%)</h4>
                </div>
                <div class="well well-tobedelayed">
                    <h4>To Be Delayed: <?php echo $total_not_assigned_tobedelayed2; ?> (<?php echo $percentage_not_assigned_tobedelayed2; ?>%)</h4>
                </div>
                <div class="well well-delayed">
                    <h4>Delayed: <?php echo $total_not_assigned_delayed2; ?> (<?php echo $percentage_not_assigned_delayed2; ?>%)</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('statusBarChart').getContext('2d');
    
    // Define colors
    const barColors = [
        '#73B194', // On Time (green)
        '#ece2c2', // To Be Delayed (yellow)
        '#D59281'  // Delayed (red)
    ];
    
    // Create the bar chart
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['On Time', 'To Be Delayed', 'Delayed'],
            datasets: [{
                label: 'Number of Applications',
                data: [
                    <?php echo $total_not_assigned_ontime2; ?>,
                    <?php echo $total_not_assigned_tobedelayed2; ?>,
                    <?php echo $total_not_assigned_delayed2; ?>
                ],
                backgroundColor: barColors,
                borderColor: barColors.map(color => color.replace('0.7', '1')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        callback: function(value) {
                            if (value % 1 === 0) {
                                return value;
                            }
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw;
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<input id="myInput" type="text" placeholder="Search: Enter the text here......">
<div id="myDiv" style="width:100%; overflow: scroll;">
<br>

<div class="table-responsive">
    <table class="table table-striped">
    <tr>
    <td colspan="11"><p><?php echo '<a href="'.esc_url(add_query_arg('cur', $user_id,add_query_arg('app', $passcode,get_permalink(4669))).'">';?>Back to Dashboard</a></p></td>
    </tr>
    <tr>
<td><b>No.</b></td>
    <td><b>Reference No.</b></td>
    <td><b>Brand Name</b></td>
    <td><b>Generic Name</b></td>
    <td><b>Manufacturer</b></td>
    <td><b>Assessment Pathway</b></td>
    <td><b>Application Date</b></td>
    <td><b>Process Timeline</b></td>
    <!--td><b>Overall Timeline</b></td-->
    </tr>

<?php
    $i=0;
foreach($applications_req2 as $application_req2){
$hm_application_id=$application_req2->hm_application_id;
$reference_no=$application_req2->reference_no;
$tracking_no=$application_req2->tracking_no;
$date_submitted=$application_req2->date_submitted;
$brand_name=$application_req2->brand_name;
$hm_generic_name=$application_req2->hm_generic_name;
$classification=$application_req2->classification;
$category=$application_req2->category;
$dosage_form=$application_req2->dosage_form;
$hm_mah_email=$application_req2->hm_mah_email;
$hm_mah_country=$application_req2->hm_mah_country;
$hm_ltr=$application_req2->hm_ltr;
$hm_ltr_email=$application_req2->hm_ltr_email;
$hm_manufacturer_country=$application_req2->hm_manufacturer_country;
$hm_manufacturer_email=$application_req2->hm_manufacturer_email;
$assessment_procedure=$application_req2->assessment_procedure;
$hm_registration_number=$application_req2->hm_registration_number;
$application_current_stage=$application_req2->application_current_stage;
$application_process=$application_req2->application_process;
$gmp_status=$application_req2->gmp_status;
$hm_manufacturer_name=$application_req2->hm_manufacturer_name;
$assessment_procedure=$application_req2->assessment_procedure;
/////////////////Select processing timelines//////////////////////
$applications_req3 = $wpdb->get_results("SELECT * from tbl_timelines where status_id='$application_current_stage' and assessment_pathway='$assessment_procedure'");
foreach($applications_req3 as $application_req3)
{
$number_of_days=$application_req3->number_of_days;
$days_processing=(strtotime($datetoday)-strtotime($date_submitted))/60/60/24;

if($days_processing>$number_of_days)
{
$days_processing_monitoring="<strong><font color='blue'>(".$number_of_days.")</font><br><font color='red'>".number_format($days_processing - $number_of_days)."<br>Delay</font></strong>";
}
else
{
$days_processing_monitoring="<strong><font color='blue'>".number_format($number_of_days - $days_processing)."<br>On time</font></strong>";    
}
}
/////////////////End Select processing timelines//////////////////////
////////////////
//////////////////////Select application assignment//////////////////////////
$applications_req4 = $wpdb->get_results("SELECT * from tbl_application_assignment where application_id='$hm_application_id' and stage_id=4");
foreach($applications_req4 as $application_req4)
{
$assignment_date=$application_req4->assignment_date;
$submission_date=$application_req4->submission_date;
}

/////////////////////End Select application assignment////////////////////////////
$start_date = strtotime($datetoday);
$end_date =strtotime($date_submitted);
$days_diff = ($start_date- $end_date)/60/60/24;
$total_days_control='';
if($days_diff>30)
{
$total_days_control="<strong><font color='red'>Delayed</font></strong>";
}

echo '<tbody id="myTable"><tr><td><a href="'.esc_url(add_query_arg('ab_num',$hm_application_id,add_query_arg('cur', $user_id,add_query_arg('app', $passcode, get_permalink(4750))))).'">'. ++$i."</td><td>". $reference_no. "</td><td>". $brand_name. "</td><td>". $hm_generic_name. "</td><td> " . $hm_manufacturer_name . "</td><td> " . $assessment_procedure . "</td><td> " . $date_submitted."</td><td>". $days_processing_monitoring. "</td></tr>";

}
?>
</tbody>
</table>
    </div>

<?php
}
else{
     echo  '<div class="alert alert-danger"><p>' . __('Not allowed to access. Please login').'</p></div>';
}