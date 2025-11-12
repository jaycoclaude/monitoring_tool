<?php
require_once 'includes/config.php';

$sql = "SELECT
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
        hm_expiry_date
    FROM tbl_hm_register
    WHERE hm_product_status = 'Registered'
    ORDER BY hm_registration_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registered Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <style>
    body {
        background: #f8f9fa;
        font-family: "open sans",Helvetica, Arial, sans-serif;
        color: #212529;
        padding: 0;
        margin: 0;
    }

    /* Header Image */
    .header-image {
        display: block;
        width: 100%;
        height: auto;
        max-height: 220px;
        object-fit: cover;
        border-bottom: 3px solid #0d6efd;
        margin-bottom: 30px;
    }

    .container-fluid {
        max-width: 95%;
        margin: auto;
        padding: 0 15px 40px 15px;
    }

    h2 {
        font-weight: 700;
        color: #3F351FFF;
    }

    .table-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
        overflow: hidden;
    }

    .table {
        margin-bottom: 0;
    }

    .table thead {
        background: #FDCD0DFF;
        color: #fff;
        text-align: center;
    }

    .table th, .table td {
        vertical-align: middle;
        font-size: 0.93rem;
        max-width: 400px;
        /* Allow wrapping on long text */
        /*white-space: normal;
        word-wrap: break-word;
        word-break: break-word;*/
    }

    .table td {
        background: #fff;
    }

    .search-box {
        max-width: 420px;
        margin-left: auto;
    }

    .input-group-text {
        font-weight: 600;
    }

    /* Optional: limit specific columns (like strength) if needed */
    .col-strength {
        max-width: 120px;
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .container-fluid {
            max-width: 100%;
            padding: 10px;
        }
        .search-box {
            margin-top: 15px;
            max-width: 100%;
        }
        .table-container {
            overflow-x: auto;
        }
    }

    .footer-text {
        text-align: center;
        color: #6c757d;
        font-size: 0.85rem;
        margin-top: 20px;
    }
</style>
</head>
<body>
<img src="register-header.png" alt="Registered Products Header" class="header-image">

<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-lg-8">
            <h2 class="text-primary fw-bold mb-1">Registered Pharmaceutical Products</h2>
            <p class="text-muted mb-0">
                Total: <strong><span id="total-count"><?php echo count($products); ?></span></strong> products
                
            </p>
        </div>
        <div class="col-lg-4 mt-3 mt-lg-0">
            <div class="input-group search-box">
                <span class="input-group-text bg-white border-end-0">
                    Search
                </span>
                <input type="text" id="searchInput" class="form-control border-start-0" 
                       placeholder="Type brand, generic, reg no, manufacturer..." 
                       autocomplete="off">
                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                    Clear
                </button>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 table-bordered" id="productsTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Reg. No.</th>
                        <th>Brand Name</th>
                        <th>Generic Name</th>
                        <th>Strength</th>
                        <th>Form</th>
                        <th>Pack</th>
                        <th>Shelf Life</th>
                        <th>Manufacturer</th>
                        <th>Country</th>
                        <th>MAH</th>
                        <th>LTR</th>
                        <th>Reg. Date</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php $i = 1; foreach ($products as $p): ?>
                        <tr class="product-row">
                            <td><strong><?php echo $i++; ?></strong></td>
                            <td data-search="<?php echo htmlspecialchars($p->hm_registration_number); ?>">
                                <?php echo htmlspecialchars($p->hm_registration_number); ?>
                            </td>
                            <td data-search="<?php echo htmlspecialchars($p->hm_product_brand_name); ?>">
                                <strong><?php echo htmlspecialchars($p->hm_product_brand_name); ?></strong>
                            </td>
                            <td data-search="<?php echo htmlspecialchars($p->hm_generic_name); ?>">
                                <?php echo htmlspecialchars($p->hm_generic_name); ?>
                            </td>
                            <td class="col-strength text-center"><?php echo htmlspecialchars($p->hm_dosage_strength); ?></td>
                            <td><?php echo htmlspecialchars($p->hm_dosage_form); ?></td>
                            <td><?php echo htmlspecialchars($p->hm_pack_size); ?></td>
                            <td><?php echo htmlspecialchars($p->hm_shelf_life); ?></td>
                            <td data-search="<?php echo htmlspecialchars($p->hm_manufacturer_name); ?>">
                                <?php echo htmlspecialchars($p->hm_manufacturer_name); ?>
                            </td>
                            <td class="text-muted"><?php echo htmlspecialchars($p->hm_manufacturer_country); ?></td>
                            <td><?php echo htmlspecialchars($p->hm_mah); ?></td>
                            <td><?php echo htmlspecialchars($p->hm_ltr); ?></td>
                            <td><?php echo htmlspecialchars($p->hm_registration_date); ?></td>
                            <td>
                               <?php echo htmlspecialchars($p->hm_expiry_date); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 text-center text-muted small">
        Last updated: <?php echo date('d M Y, h:i A'); ?> 
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live Search - Pure JavaScript (no jQuery)
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearSearch');
    const tableBody = document.getElementById('tableBody');
    const rows = tableBody.querySelectorAll('.product-row');
    const noResults = document.getElementById('noResults');
    const searchTermSpan = document.getElementById('searchTerm');
    const totalCount = document.getElementById('total-count').textContent;
    const showingCount = document.getElementById('showing-count');

    function filterTable() {
        const term = searchInput.value.trim().toLowerCase();
        let visible = 0;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const cells = row.querySelectorAll('[data-search]');
            let match = term === '' || text.includes(term);

            // Highlight matching text
            cells.forEach(cell => {
                const cellText = cell.getAttribute('data-search').toLowerCase();
                if (term && cellText.includes(term)) {
                    match = true;
                }
            });

            if (match) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update counters
        showingCount.textContent = visible;

        // Show/hide no results
        if (visible === 0 && term !== '') {
            noResults.classList.add('show');
            searchTermSpan.textContent = term;
        } else {
            noResults.classList.remove('show');
        }
    }

    searchInput.addEventListener('input', filterTable);
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        filterTable();
        searchInput.focus();
    });

    // Initial state
    filterTable();
});
</script>
</body>
</html>

<?php $pdo = null; ?>