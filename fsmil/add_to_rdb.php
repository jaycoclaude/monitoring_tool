<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = intval($_POST['application_reference']);
    $service_requested = $_POST['service_requested'];
    $rdb_status = $_POST['rdb_status'];
    $user_id = $_SESSION['user_id'];
    $stage_id = $_POST['application_current_stage'] ?? '';

    // Function to format date
    function formatDate($date) {
        return !empty($date) ? date('Y-m-d', strtotime($date)) : null;
    }

    // First, update the main application table (ALWAYS UPDATE APPLICATION TABLE)
    $updateAppQuery = "UPDATE tbl_hm_applications_premise_food SET
        reference_no = :reference_no,
        tracking_no = :tracking_no,
        applicant_name = :applicant_name,
        application_date = :application_date,
        application_current_stage = :application_current_stage,
        premise_category = :premise_category,
        product_category = :product_category,
        product_type = :product_type,
        applicant_TIN_no = :applicant_TIN_no,
        applicant_telephone = :applicant_telephone,
        applicant_email = :applicant_email,
        country = :country,
        province = :province,
        district = :district,
        sector = :sector,
        cell = :cell,
        village = :village,
        gps_coordinates = :gps_coordinates,
        managing_director = :managing_director,
        responsible_technician = :responsible_technician,
        responsible_technician_telephone = :responsible_technician_telephone,
        date_submitted = :date_submitted,
        updated_by = :updated_by
      WHERE application_id = :application_id";

    $appStmt = $pdo->prepare($updateAppQuery);
    $appUpdated = $appStmt->execute([
        'reference_no' => $_POST['reference_no'],
        'tracking_no' => $_POST['tracking_no'],
        'applicant_name' => $_POST['applicant_name'],
        'application_date' => formatDate($_POST['application_date']),
        'application_current_stage' => $_POST['application_current_stage'],
        'premise_category' => $_POST['premise_category'],
        'product_category' => $_POST['product_category'],
        'product_type' => $_POST['product_type'],
        'applicant_TIN_no' => $_POST['applicant_TIN_no'],
        'applicant_telephone' => $_POST['applicant_telephone'],
        'applicant_email' => $_POST['applicant_email'],
        'country' => $_POST['country'],
        'province' => $_POST['province'],
        'district' => $_POST['district'],
        'sector' => $_POST['sector'],
        'cell' => $_POST['cell'],
        'village' => $_POST['village'],
        'gps_coordinates' => $_POST['gps_coordinates'],
        'managing_director' => $_POST['managing_director'],
        'responsible_technician' => $_POST['responsible_technician'],
        'responsible_technician_telephone' => $_POST['responsible_technician_telephone'],
        'date_submitted' => formatDate($_POST['date_submitted']),
        'updated_by' => $user_id,
        'application_id' => $app_id
    ]);

    if ($appUpdated) {
        // Check if application already exists in rdb_osc_applications
        $checkRdbQuery = "SELECT * FROM rdb_osc_applications 
                          WHERE application_reference = :app_id 
                          AND application_type = 'premise_food'";
        $checkRdbStmt = $pdo->prepare($checkRdbQuery);
        $checkRdbStmt->execute(['app_id' => $app_id]);
        $existingRdbApp = $checkRdbStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRdbApp) {
            // Update existing rdb_osc_applications record
            $updateRdbQuery = "UPDATE rdb_osc_applications 
                              SET current_status = :current_status, 
                                  applicant_name = :applicant_name,
                                  tracking_no = :tracking_no,
                                  application_date = :application_date,
                                  service_requested = :service_requested,
                                  updated_at = NOW() 
                              WHERE application_reference = :application_id 
                              AND application_type = 'premise_food'";
            
            $rdbStmt = $pdo->prepare($updateRdbQuery);
            $rdbUpdated = $rdbStmt->execute([
                'current_status' => $rdb_status,
                'applicant_name' => $_POST['applicant_name'],
                'tracking_no' => $_POST['tracking_no'],
                'application_date' => formatDate($_POST['application_date']),
                'service_requested' => $service_requested,
                'application_id' => $app_id
            ]);

            if ($rdbUpdated) {
                $message = "Application updated successfully! RDB OSC application also updated. Status: " . strtoupper($rdb_status);
            } else {
                $message = "Application updated successfully! RDB OSC application found but could not be updated.";
            }
        } else {
            // Insert new record into rdb_osc_applications
            $insertRdbQuery = "INSERT INTO rdb_osc_applications (
                tracking_no, 
                applicant_name, 
                service_requested, 
                application_reference, 
                application_type, 
                application_date, 
                current_status
            ) VALUES (
                :tracking_no,
                :applicant_name,
                :service_requested,
                :application_reference,
                'premise_food',
                :application_date,
                :current_status
            )";
            
            $rdbStmt = $pdo->prepare($insertRdbQuery);
            $rdbInserted = $rdbStmt->execute([
                'tracking_no' => $_POST['tracking_no'],
                'applicant_name' => $_POST['applicant_name'],
                'service_requested' => $service_requested,
                'application_reference' => $app_id,
                'application_date' => formatDate($_POST['application_date']),
                'current_status' => $rdb_status
            ]);

            if ($rdbInserted) {
                $message = "Application updated successfully and added to RDB OSC system! Status: " . strtoupper($rdb_status);
            } else {
                $message = "Application updated successfully but there was an error adding to RDB OSC system.";
            }
        }

        echo "<script>
            alert('$message');
            window.location.href='fsmil_page.php?stage_id=$stage_id';
        </script>";
    } else {
        echo "<script>
            alert('Error updating application record.');
            window.history.back();
        </script>";
    }
} else {
    header('Location: fsmil_page.php');
    exit;
}
?>