<?php
session_start();
require_once '../includes/config.php';

// Check permissions
$access = $_SESSION['user_access'] ?? 0;
if ($access <> 100 && $access <> 4) {
    die('You do not have permission to access this page. <br><a href="javascript:history.back()">Click here to go back</a>');
}

$user_id = $_SESSION['user_id'];
$stage_id = $_GET['stage_id'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Format date function
        function formatDate($date) {
            return !empty($date) ? date('Y-m-d', strtotime($date)) : null;
        }
        
        $app_id = intval($_POST['hm_application_id']);
        
        // Update application table
        $updateAppQuery = "UPDATE tbl_hm_applications SET
            reference_no = :reference_no,
            tracking_no = :tracking_no,
            brand_name = :brand_name,
            hm_generic_name = :hm_generic_name,
            product_strength = :product_strength,
            dosage_form = :dosage_form,
            hm_pack_size = :hm_pack_size,
            hm_packaging_type = :hm_packaging_type,
            hm_shelf_life = :hm_shelf_life,
            hm_manufacturer_name = :hm_manufacturer_name,
            hm_manufacturer_address = :hm_manufacturer_address,
            hm_manufacturer_country = :hm_manufacturer_country,
            hm_mah = :hm_mah,
            hm_ltr = :hm_ltr,
            application_current_stage = '10',
            updated_by = :updated_by,
            updated_at = NOW()
            WHERE hm_application_id = :hm_application_id";
            
        $appData = [
            'reference_no' => $_POST['reference_no'],
            'tracking_no' => $_POST['tracking_no'],
            'brand_name' => $_POST['brand_name'],
            'hm_generic_name' => $_POST['hm_generic_name'],
            'product_strength' => $_POST['product_strength'],
            'dosage_form' => $_POST['dosage_form'],
            'hm_pack_size' => $_POST['hm_pack_size'],
            'hm_packaging_type' => $_POST['hm_packaging_type'],
            'hm_shelf_life' => $_POST['hm_shelf_life'],
            'hm_manufacturer_name' => $_POST['hm_manufacturer_name'],
            'hm_manufacturer_address' => $_POST['hm_manufacturer_address'],
            'hm_manufacturer_country' => $_POST['hm_manufacturer_country'],
            'hm_mah' => $_POST['hm_mah'],
            'hm_ltr' => $_POST['hm_ltr'],
            'updated_by' => $user_id,
            'hm_application_id' => $app_id
        ];
        
        $stmt = $pdo->prepare($updateAppQuery);
        $stmt->execute($appData);
        
        // Determine current status based on product status
        $current_status = ($_POST['hm_product_status'] == 'Rejected') ? 'Rejected' : 'Registered';
        
        // Check if product already exists in register table by application number
        $checkQuery = "SELECT hm_register_id FROM tbl_hm_register WHERE hm_application_number = :hm_application_number";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute(['hm_application_number' => $_POST['reference_no']]);
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingRecord) {
            // UPDATE existing record in register table
            $updateRegisterQuery = "UPDATE tbl_hm_register SET
                hm_registration_number = :hm_registration_number,
                hm_product_brand_name = :hm_product_brand_name,
                hm_generic_name = :hm_generic_name,
                hm_dosage_strength = :hm_dosage_strength,
                hm_dosage_form = :hm_dosage_form,
                hm_pack_size = :hm_pack_size,
                hm_packaging_type = :hm_packaging_type,
                hm_shelf_life = :hm_shelf_life,
                hm_manufacturer_name = :hm_manufacturer_name,
                hm_manufacturer_address = :hm_manufacturer_address,
                hm_manufacturer_country = :hm_manufacturer_country,
                hm_mah = :hm_mah,
                hm_ltr = :hm_ltr,
                hm_registration_date = :hm_registration_date,
                hm_expiry_date = :hm_expiry_date,
                hm_product_status = :hm_product_status,
                current_status = :current_status,
                hm_mah_email = :hm_mah_email,
                hm_ltr_email = :hm_ltr_email,
                created_at = NOW()
                WHERE hm_application_number = :hm_application_number";
            
            $registerData = [
                'hm_application_number' => $_POST['reference_no'],
                'hm_registration_number' => $_POST['hm_registration_number'],
                'hm_product_brand_name' => $_POST['brand_name'],
                'hm_generic_name' => $_POST['hm_generic_name'],
                'hm_dosage_strength' => $_POST['product_strength'],
                'hm_dosage_form' => $_POST['dosage_form'],
                'hm_pack_size' => $_POST['hm_pack_size'],
                'hm_packaging_type' => $_POST['hm_packaging_type'],
                'hm_shelf_life' => $_POST['hm_shelf_life'],
                'hm_manufacturer_name' => $_POST['hm_manufacturer_name'],
                'hm_manufacturer_address' => $_POST['hm_manufacturer_address'],
                'hm_manufacturer_country' => $_POST['hm_manufacturer_country'],
                'hm_mah' => $_POST['hm_mah'],
                'hm_ltr' => $_POST['hm_ltr'],
                'hm_registration_date' => formatDate($_POST['hm_registration_date']),
                'hm_expiry_date' => formatDate($_POST['hm_expiry_date']),
                'hm_product_status' => $_POST['hm_product_status'],
                'current_status' => $current_status,
                'hm_mah_email' => $_POST['hm_mah_email'],
                'hm_ltr_email' => $_POST['hm_ltr_email']
            ];
            
            $stmt = $pdo->prepare($updateRegisterQuery);
            $stmt->execute($registerData);
            $action = 'updated';
        } else {
            // INSERT new record into register table
            $insertRegisterQuery = "INSERT INTO tbl_hm_register (
                hm_application_number,
                hm_registration_number,
                hm_product_brand_name,
                hm_generic_name,
                hm_dosage_strength,
                hm_dosage_form,
                hm_pack_size,
                hm_packaging_type,
                hm_shelf_life,
                hm_manufacturer_name,
                hm_manufacturer_address,
                hm_manufacturer_country,
                hm_mah,
                hm_ltr,
                hm_registration_date,
                hm_expiry_date,
                hm_product_status,
                current_status,
                hm_mah_email,
                hm_ltr_email
            ) VALUES (
                :hm_application_number,
                :hm_registration_number,
                :hm_product_brand_name,
                :hm_generic_name,
                :hm_dosage_strength,
                :hm_dosage_form,
                :hm_pack_size,
                :hm_packaging_type,
                :hm_shelf_life,
                :hm_manufacturer_name,
                :hm_manufacturer_address,
                :hm_manufacturer_country,
                :hm_mah,
                :hm_ltr,
                :hm_registration_date,
                :hm_expiry_date,
                :hm_product_status,
                :current_status,
                :hm_mah_email,
                :hm_ltr_email
            )";
            
            $registerData = [
                'hm_application_number' => $_POST['reference_no'],
                'hm_registration_number' => $_POST['hm_registration_number'],
                'hm_product_brand_name' => $_POST['brand_name'],
                'hm_generic_name' => $_POST['hm_generic_name'],
                'hm_dosage_strength' => $_POST['product_strength'],
                'hm_dosage_form' => $_POST['dosage_form'],
                'hm_pack_size' => $_POST['hm_pack_size'],
                'hm_packaging_type' => $_POST['hm_packaging_type'],
                'hm_shelf_life' => $_POST['hm_shelf_life'],
                'hm_manufacturer_name' => $_POST['hm_manufacturer_name'],
                'hm_manufacturer_address' => $_POST['hm_manufacturer_address'],
                'hm_manufacturer_country' => $_POST['hm_manufacturer_country'],
                'hm_mah' => $_POST['hm_mah'],
                'hm_ltr' => $_POST['hm_ltr'],
                'hm_registration_date' => formatDate($_POST['hm_registration_date']),
                'hm_expiry_date' => formatDate($_POST['hm_expiry_date']),
                'hm_product_status' => $_POST['hm_product_status'],
                'current_status' => $current_status,
                'hm_mah_email' => $_POST['hm_mah_email'],
                'hm_ltr_email' => $_POST['hm_ltr_email']
            ];
            
            $stmt = $pdo->prepare($insertRegisterQuery);
            $stmt->execute($registerData);
            $action = 'added';
        }
        
        $pdo->commit();
        
        echo "<script>
            alert('Product successfully {$action} to register!');
            window.location.href = 'hmdr_page.php?stage_id=10';
        </script>";
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Fetch application data if app_id is provided
$application = null;
$existingRegisterData = null;
if (isset($_GET['app_id'])) {
    $app_id = intval($_GET['app_id']);
    
    $sql = "SELECT * FROM tbl_hm_applications WHERE hm_application_id = :app_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['app_id' => $app_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If application found, check if it exists in register table
    if ($application) {
        $checkQuery = "SELECT * FROM tbl_hm_register WHERE hm_application_number = :app_number";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute(['app_number' => $application['reference_no']]);
        $existingRegisterData = $checkStmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Determine which data to use for form fields
$formData = $existingRegisterData ?: $application;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product to Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }
        
        .section-header {
            background: linear-gradient(135deg, var(--primary-color), #34495e);
            color: white;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 6px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .required::after {
            content: " *";
            color: var(--danger-color);
        }
        
        .search-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .product-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .existing-record-card {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .status-registered {
            border-left: 4px solid var(--success-color);
        }
        
        .status-rejected {
            border-left: 4px solid var(--danger-color);
        }
        
        .data-source-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="fas fa-plus-circle me-2"></i>
            <?php echo $existingRegisterData ? 'Update Product in Register' : 'Add Product to Register'; ?>
        </h2>
        <a href="hmdr_page.php?stage_id=<?php echo 10 ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <!-- Search Section -->
    <div class="search-section card">
        <div class="section-header">
            <h4 class="mb-0"><i class="fas fa-search me-2"></i>Search Application</h4>
        </div>
        
        <form method="GET" class="row g-3">
            <input type="hidden" name="stage_id" value="<?php echo $stage_id; ?>">
            <div class="col-md-8">
                <label class="form-label">Search by Reference No, Brand Name, or Tracking No</label>
                <input type="text" name="search" class="form-control" placeholder="Enter search terms..." 
                       value="<?php echo $_GET['search'] ?? ''; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </div>
        </form>
        
        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
            <?php
            $search = '%' . $_GET['search'] . '%';
            $searchQuery = "SELECT hm_application_id, reference_no, tracking_no, brand_name, product_strength, 
                           application_current_stage 
                    FROM tbl_hm_applications 
                    WHERE (reference_no LIKE :search OR brand_name LIKE :search OR tracking_no LIKE :search)
                    LIMIT 10";
            $searchStmt = $pdo->prepare($searchQuery);
            $searchStmt->execute(['search' => $search]);
            $results = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if ($results): ?>
                <div class="mt-4">
                    <h5 class="mb-3">Search Results:</h5>
                    <div class="list-group">
                        <?php foreach ($results as $result): ?>
                            <a href="?stage_id=<?php echo $stage_id; ?>&app_id=<?php echo $result['hm_application_id']; ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($result['brand_name']); ?></h6>
                                    <small>Ref: <?php echo htmlspecialchars($result['reference_no']); ?></small>
                                </div>
                                <p class="mb-1">Strength: <?php echo htmlspecialchars($result['product_strength']); ?></p>
                                <small>Tracking: <?php echo htmlspecialchars($result['tracking_no']); ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mt-3">
                    No applications found matching your search criteria.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Product Information Card -->
    <?php if ($application): ?>
        <div class="product-info-card">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-2"><?php echo htmlspecialchars($application['brand_name']); ?></h4>
                    <p class="mb-1"><strong>Reference No:</strong> <?php echo htmlspecialchars($application['reference_no']); ?></p>
                    <p class="mb-1"><strong>Tracking No:</strong> <?php echo htmlspecialchars($application['tracking_no']); ?></p>
                    <p class="mb-0"><strong>Generic Name:</strong> <?php echo htmlspecialchars($application['hm_generic_name']); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <?php echo $existingRegisterData ? 'Update Existing Record' : 'Ready for Registration'; ?>
                    </span>
                    <?php if ($existingRegisterData): ?>
                        <br><small class="text-light mt-1 d-block">Form pre-filled with register data</small>
                    <?php else: ?>
                        <br><small class="text-light mt-1 d-block">Form pre-filled with application data</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Existing Record Notice -->
    <?php if ($existingRegisterData): ?>
        <div class="existing-record-card">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-2"><i class="fas fa-database me-2"></i>Existing Record Found</h5>
                    <p class="mb-1">This product is already in the register. The form is pre-filled with register data.</p>
                    <p class="mb-0"><strong>Current Registration Number:</strong> <?php echo htmlspecialchars($existingRegisterData['hm_registration_number']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Form -->
    <?php if ($application): ?>
    <form method="POST" class="card p-4">
        <input type="hidden" name="hm_application_id" value="<?php echo $application['hm_application_id']; ?>">
        
        <!-- Basic Product Information -->
        <div class="form-section">
            <div class="section-header">
                <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Product Information</h4>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label required">Reference No</label>
                    <input type="text" name="reference_no" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['reference_no'] ?? $formData['hm_application_number']); ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label required">Tracking No</label>
                    <input type="text" name="tracking_no" class="form-control" 
                           value="<?php echo htmlspecialchars($application['tracking_no']); ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label required">Brand Name</label>
                    <input type="text" name="brand_name" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['brand_name'] ?? $formData['hm_product_brand_name']); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label required">Generic Name</label>
                    <input type="text" name="hm_generic_name" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_generic_name']); ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label required">Product Strength</label>
                    <input type="text" name="product_strength" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['product_strength'] ?? $formData['hm_dosage_strength']); ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label required">Dosage Form</label>
                    <input type="text" name="dosage_form" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['dosage_form'] ?? $formData['hm_dosage_form']); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Pack Size</label>
                    <input type="text" name="hm_pack_size" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_pack_size']); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Packaging Type</label>
                    <input type="text" name="hm_packaging_type" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_packaging_type']); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Shelf Life</label>
                    <input type="text" name="hm_shelf_life" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_shelf_life']); ?>">
                </div>
            </div>
        </div>

        <!-- Manufacturer Information -->
        <div class="form-section">
            <div class="section-header">
                <h4 class="mb-0"><i class="fas fa-industry me-2"></i>Manufacturer Information</h4>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Manufacturer Name</label>
                    <input type="text" name="hm_manufacturer_name" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_manufacturer_name']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Manufacturer Country</label>
                    <input type="text" name="hm_manufacturer_country" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_manufacturer_country']); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label">Manufacturer Address</label>
                    <textarea name="hm_manufacturer_address" class="form-control" rows="3" value="<?php echo htmlspecialchars($formData['hm_manufacturer_address']); ?>"><?php echo htmlspecialchars($formData['hm_manufacturer_address']); ?></textarea>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">MAH (Marketing Authorization Holder)</label>
                    <input type="text" name="hm_mah" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_mah']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">LTR (Local Technical Representative)</label>
                    <input type="text" name="hm_ltr" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_ltr']); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">MAH Email</label>
                    <input type="email" name="hm_mah_email" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_applicant_email'] ?? $formData['hm_mah_email']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">LTR Email</label>
                    <input type="email" name="hm_ltr_email" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['hm_ltr_email']); ?>">
                </div>
            </div>
        </div>

        <!-- Registration Information -->
        <div class="form-section">
            <div class="section-header">
                <h4 class="mb-0"><i class="fas fa-certificate me-2"></i>Registration Information</h4>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label required">Registration Number</label>
                    <input type="text" name="hm_registration_number" class="form-control" 
                           value="<?php echo $existingRegisterData ? htmlspecialchars($existingRegisterData['hm_registration_number']) : ''; ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label required">Registration Date</label>
                    <input type="date" name="hm_registration_date" class="form-control" 
                           value="<?php echo $existingRegisterData ? htmlspecialchars($existingRegisterData['hm_registration_date']) : ''; ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label required">Expiry Date</label>
                    <input type="date" name="hm_expiry_date" class="form-control" 
                           value="<?php echo $existingRegisterData ? htmlspecialchars($existingRegisterData['hm_expiry_date']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Product Status</label>
                    <select name="hm_product_status" class="form-select" required id="productStatus">
                        <option value="Registered" <?php echo ($formData['hm_product_status'] == 'Registered' || !isset($formData['hm_product_status'])) ? 'selected' : ''; ?>>Registered</option>
                        <option value="Rejected" <?php echo ($formData['hm_product_status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Current Status</label>
                    <input type="text" class="form-control" id="currentStatusDisplay" 
                           value="<?php echo htmlspecialchars($formData['current_status'] ?? 'Registered'); ?>" readonly 
                           style="background-color: #e8f5e8; font-weight: bold;">
                    <small class="form-text text-muted">This is automatically set based on Product Status</small>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
            <a href="hmdr_page.php?stage_id=<?php echo $stage_id; ?>" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
            <button type="submit" class="btn <?php echo $existingRegisterData ? 'btn-warning' : 'btn-success'; ?>">
                <i class="fas fa-save me-2"></i><?php echo $existingRegisterData ? 'Update Register' : 'Add to Register'; ?>
            </button>
        </div>
    </form>
    <?php else: ?>
        <div class="card p-5 text-center">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">Search for an application to add to register</h4>
            <p class="text-muted">Use the search box above to find products ready for registration.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Client-side validation and status update
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const productStatus = document.getElementById('productStatus');
        const currentStatusDisplay = document.getElementById('currentStatusDisplay');
        
        // Update current status based on product status selection
        if (productStatus && currentStatusDisplay) {
            productStatus.addEventListener('change', function() {
                if (this.value === 'Rejected') {
                    currentStatusDisplay.value = 'Rejected';
                    currentStatusDisplay.style.backgroundColor = '#f8d7da';
                } else {
                    currentStatusDisplay.value = 'Registered';
                    currentStatusDisplay.style.backgroundColor = '#e8f5e8';
                }
            });
            
            // Initialize status display color
            if (productStatus.value === 'Rejected') {
                currentStatusDisplay.style.backgroundColor = '#f8d7da';
            }
        }
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        valid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields marked with *');
                }
            });
        }
        
        // Set default dates if not already set (for new records)
        const regDateField = document.querySelector('input[name="hm_registration_date"]');
        if (regDateField && !regDateField.value) {
            regDateField.valueAsDate = new Date();
        }
        
        const expiryDateField = document.querySelector('input[name="hm_expiry_date"]');
        if (expiryDateField && !expiryDateField.value) {
            const today = new Date();
            const fiveYearsLater = new Date(today.getFullYear() + 5, today.getMonth(), today.getDate());
            expiryDateField.valueAsDate = fiveYearsLater;
        }
    });
</script>
</body>
</html>