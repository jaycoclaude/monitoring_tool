<?php
require_once '../includes/auth.php';
require_once 'data.php';

$current_user_id = $_SESSION['user_id'];
if (!$current_user_id) {
    header('Location: ../index.php');
    exit();
}

$tasks = getTasks($current_user_id);
$searchTerm = trim($_GET['search'] ?? '');

$db = getDB();
$stmt = $db->prepare("SELECT staff_id, staff_names, staff_email FROM tbl_staff WHERE user_id = :user_id AND staff_status = 1 LIMIT 1");
$stmt->execute([':user_id' => $current_user_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
$current_staff_name = $staff['staff_names'] ?? '';
$current_staff_email = $staff['staff_email'] ?? $_SESSION['staff_email'] ?? '';

if ($searchTerm !== '') {
    $tasks = array_filter($tasks, function ($t) use ($searchTerm) {
        return stripos($t['title'], $searchTerm) !== false ||
            stripos($t['description'], $searchTerm) !== false ||
            stripos($t['assigned_by_name'], $searchTerm) !== false ||
            stripos($t['assigned_to_name'], $searchTerm) !== false;
    });
}

$createdTasks   = array_filter($tasks, fn($t) => $t['assigned_by_email'] === $current_staff_email);
$inboxTasks     = array_filter($tasks, fn($t) => $t['assigned_to_name'] === $current_staff_name && $t['status'] != 'completed');
$completedTasks = array_filter($tasks, fn($t) =>
    $t['status'] == 'completed' &&
    ($t['assigned_to_name'] === $current_staff_name || $t['assigned_by_email'] === $current_staff_email)
);
$inboxTasks = array_filter( $tasks,    fn($t) =>    $t['assigned_to_name'] === $current_staff_name &&        in_array($t['status'], ['pending', 'in_progress', 'review'])
);

$tasksPerPage = 6;
$createdPage   = max(1, intval($_GET['created_page'] ?? 1));
$inboxPage     = max(1, intval($_GET['inbox_page'] ?? 1));
$completedPage = max(1, intval($_GET['completed_page'] ?? 1));

function paginateTasks($tasks, $tasksPerPage, $currentPage)
{
    $totalTasks = count($tasks);
    $totalPages = ceil($totalTasks / $tasksPerPage);
    $start = ($currentPage - 1) * $tasksPerPage;
    return [array_slice($tasks, $start, $tasksPerPage), $totalPages];
}

list($createdTasksPage, $createdPages)     = paginateTasks($createdTasks, $tasksPerPage, $createdPage);
list($inboxTasksPage, $inboxPages)         = paginateTasks($inboxTasks, $tasksPerPage, $inboxPage);
list($completedTasksPage, $completedPages) = paginateTasks($completedTasks, $tasksPerPage, $completedPage);

require_once 'header.php';
?>

<style>
    :root {

        --primary: #0f5e8a;
        --primary-light: #0f5e8a;
        --primary-dark: #0f5e8a;
        --primary-soft: rgba(30, 58, 138, 0.1);


        --success: #30D158;
        --success-light: #4DE37B;
        --success-soft: rgba(48, 209, 88, 0.1);

        /* Warning Yellow */
        --warning: #F9BF74FF;
        --warning-light: #F9BF74FF;
        --warning-soft: rgba(255, 214, 10, 0.1);

        /* Danger Red */
        --danger: #FF453A;
        --danger-light: #FF6B64;
        --danger-soft: rgba(255, 69, 58, 0.1);

        /* Info Teal */
        --info: #5AC8FA;
        --info-light: #7FD9FF;
        --info-soft: rgba(90, 200, 250, 0.1);

        /* Neutrals */
        --light: #FFFFFF;
        --light-gray: #F2F2F7;
        --medium-gray: #D1D1D6;
        --dark-gray: #01090C99;
        --dark: #021017FF;

        /* Border radius */
        --border-radius: 16px;
        --border-radius-sm: 12px;
        --border-radius-lg: 20px;

        /* Shadows */
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
        --shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.12);

        /* Transitions */
        --transition-fast: all 0.2s ease;
        --transition: all 0.3s ease;
        --transition-slow: all 0.5s ease;
    }


    * {
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        font-family: 'Segoe UI', system-ui, -Elegant-system, sans-serif;
        color: var(--dark);
        line-height: 1.6;
    }

    /* --- Page Header --- */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        padding: 24px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        animation: slideInDown 0.6s ease;
        border: 1px solid var(--medium-gray);
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-title i {
        color: var(--primary);
        font-size: 1.8rem;
    }

    .action-buttons .btn {
        padding: 12px 24px;
        border-radius: var(--border-radius-sm);
        font-weight: 600;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: var(--shadow-sm);
    }

    .action-buttons .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    /* --- Search Form --- */
    .search-form {
        margin-bottom: 32px;
        background: #fff;
        padding: 24px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        animation: fadeIn 0.5s ease 0.1s both;
        border: 1px solid var(--medium-gray);
    }

    .search-form form {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-form input {
        flex: 1;
        min-width: 280px;
        padding: 14px 20px;
        border-radius: var(--border-radius-sm);
        border: 2px solid var(--medium-gray);
        font-size: 1rem;
        transition: var(--transition);
        background: var(--light);
        color: var(--dark);
    }

    .search-form input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px var(--primary-soft);
        outline: none;
        background: #fff;
    }

    .search-form .btn {
        padding: 14px 24px;
        border-radius: var(--border-radius-sm);
        font-weight: 600;
        transition: var(--transition);
    }

    .search-form .btn:hover {
        transform: translateY(-2px);
    }

    /* --- Tabs Container --- */
    .tabs-container {
        margin-bottom: 40px;
        background: #fff;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        animation: fadeIn 0.5s ease 0.2s both;
        border: 1px solid var(--medium-gray);
    }

    .tabs-line {
        display: flex;
        border-bottom: 1px solid var(--medium-gray);
        gap: 0;
        padding: 0 20px;
        background: var(--light);
    }

    .tab-link {
        padding: 20px 28px;
        background: transparent;
        border: none;
        font-size: 1rem;
        font-weight: 600;
        color: var(--dark-gray);
        cursor: pointer;
        position: relative;
        transition: var(--transition);
        border-radius: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .tab-link .tab-count {
        font-weight: 500;
        color: var(--dark-gray);
        background: var(--medium-gray);
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.85em;
        transition: var(--transition);
    }

    .tab-link.active {
        color: var(--primary-dark);
        background: #fff;
    }

    .tab-link.active::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        border-radius: 3px 3px 0 0;
    }

    .tab-link.active .tab-count {
        background: var(--primary);
        color: white;
    }

    .tab-link:hover:not(.active) {
        background: rgba(93, 138, 168, 0.05);
        color: var(--primary-dark);
    }

    .tab-link:hover:not(.active) .tab-count {
        background: var(--primary-light);
        color: white;
    }

    .tab-content {
        padding: 32px;
        background: #fff;
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        animation: fadeInUp 0.5s ease;
    }

    /* --- Cards Grid --- */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 24px;
    }

    @media (min-width: 640px) {
        .cards-grid {
            grid-template-columns: repeat(1, 1fr);
        }
    }

    @media (min-width: 768px) {
        .cards-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .cards-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    /* --- Card Styles --- */
    .card {
        background: #fff;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 24px;
        transition: var(--transition);
        border-left: 6px solid transparent;
        display: flex;
        flex-direction: column;
        gap: 18px;
        height: fit-content;
        position: relative;
        overflow: hidden;
        animation: cardSlideIn 0.5s ease both;
        border: 1px solid var(--medium-gray);
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        transform: scaleX(0);
        transition: var(--transition);
        transform-origin: left;
    }

    .card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }

    .card:hover::before {
        transform: scaleX(1);
    }

    /* Priority Borders & Badge */
    .card.border-low {
        border-left-color: var(--primary);
    }

    .card.border-medium {
        border-left-color: var(--warning);
    }

    .card.border-high {
        border-left-color: var(--danger);
    }

    .card.border-urgent {
        border-left-color: #c53030;
    }

    .priority-badge {
        font-size: 0.75em;
        font-weight: 600;
        text-transform: uppercase;
        padding: 6px 12px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #fff;
        transition: var(--transition);
    }

    .priority-low {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        box-shadow: 0 2px 8px rgba(93, 138, 168, 0.3);
        animation: pulse 2s infinite, shake 0.5s ease-in-out infinite alternate;
    }

    .priority-medium {
        background: linear-gradient(135deg, var(--warning) 0%, var(--warning-light) 100%);
        color: #7c2d12;
        box-shadow: 0 2px 8px rgba(230, 180, 90, 0.3);
        animation: pulse 2s infinite, shake 0.5s ease-in-out infinite alternate;
    }

    .priority-high {
        background: linear-gradient(135deg, var(--danger) 0%, var(--danger-light) 100%);
        box-shadow: 0 2px 8px rgba(230, 124, 115, 0.3);
        animation: pulse 2s infinite, shake 0.5s ease-in-out infinite alternate;
    }

    .priority-urgent {
        background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%);
        box-shadow: 0 2px 8px rgba(197, 48, 48, 0.3);
        animation: pulse 2s infinite, shake 0.5s ease-in-out infinite alternate;
    }

    /* Card Header */
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0;
        gap: 12px;
        flex-wrap: wrap;
    }

    .card-header h3 {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0;
        color: var(--dark);
        display: flex;
        align-items: flex-start;
        gap: 10px;
        word-break: break-word;
        line-height: 1.4em;
        flex: 1;
    }

    .card-header h3 i {
        flex-shrink: 0;
        margin-top: 4px;
        color: var(--primary);
        font-size: 1.2rem;
    }

    .card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 0.85em;
    }

    .card-meta>div {
        background: var(--light-gray);
        padding: 8px 14px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8em;
        transition: var(--transition);
        border: 1px solid transparent;
    }

    .card-meta>div:hover {
        background: white;
        border-color: var(--medium-gray);
        transform: translateY(-1px);
    }

    /* Status Styles */
    .status {
        font-size: 0.8em;
        padding: 8px 14px;
        border-radius: 20px;
        text-transform: capitalize;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }

    .status.pending {
        background: linear-gradient(135deg, var(--warning-soft) 0%, #fff9ed 100%);
        color: #92400e;
        border: 1px solid var(--warning-light);
    }

    .status.pending::before {
        content: "\f017";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
    }

    .status.in-progress {
        background: linear-gradient(135deg, var(--info-soft) 0%, #f0fdff 100%);
        color: #0e4c63;
        border: 1px solid var(--info-light);
        animation: pulse 2s infinite, shake 0.5s ease-in-out infinite alternate;
    }

    .status.in-progress::before {
        content: "\f110";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        animation: spin 2s linear infinite;
    }

    .status.completed {
        background: linear-gradient(135deg, var(--success-soft) 0%, #f0fff4 100%);
        color: #166534;
        border: 1px solid var(--success-light);
        animation: pulse 2s infinite, shake 0.5s ease-in-out infinite alternate;
    }

    .status.completed::before {
        content: "\f00c";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
    }

    .status.overdue {
        background: linear-gradient(135deg, var(--danger-soft) 0%, #fef2f2 100%);
        color: #991b1b;
        border: 1px solid var(--danger-light);
        animation: pulse 2s infinite, shake 0.5s ease-in-out infinite alternate;
    }

    .status.overdue::before {
        content: "\f06a";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.05);
            opacity: 0.9;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes shake {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(3px);
        }
    }

    .description-label {
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--dark);
        font-size: 0.95em;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .description-label::before {
        content: '\f15c';
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        color: var(--primary);
        font-size: 0.9em;
    }

    .description {
        background: var(--light-gray);
        padding: 16px;
        border-radius: var(--border-radius-sm);
        color: var(--dark-gray);
        font-size: 0.9em;
        line-height: 1.6em;
        max-height: 120px;
        overflow-y: auto;
        transition: var(--transition);
        border: 1px solid transparent;
    }

    .description:hover {
        background: white;
        border-color: var(--medium-gray);
    }

    .attachments {
        font-size: 0.85em;
        margin-top: 8px;
        color: var(--dark-gray);
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        padding: 10px 0;
    }

    .attachments i {
        margin-right: 6px;
        color: var(--primary);
    }

    /* Button Styles */
    .view-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
        padding: 10px 18px;
        font-size: 0.85em;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: #fff;
        border-radius: var(--border-radius-sm);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
    }

    .view-btn:hover {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        transform: translateY(-2px);
        color: #fff;
        text-decoration: none;
        box-shadow: var(--shadow);
    }

    .view-btn.edit {
        background: linear-gradient(135deg, var(--success) 0%, var(--success-light) 100%);
    }

    .view-btn.edit:hover {
        background: linear-gradient(135deg, #4fa174 0%, var(--success) 100%);
    }

    .view-btn.delete {
        background: linear-gradient(135deg, var(--danger) 0%, var(--danger-light) 100%);
    }

    .view-btn.delete:hover {
        background: linear-gradient(135deg, #d45a50 0%, var(--danger) 100%);
    }

    /* Empty States */
    .empty-state {
        text-align: center;
        padding: 80px 40px;
        color: var(--dark-gray);
        grid-column: 1 / -1;
        animation: fadeIn 0.8s ease;
    }

    .empty-state i {
        font-size: 5em;
        margin-bottom: 24px;
        color: var(--medium-gray);
        opacity: 0.7;
    }

    .empty-state h3 {
        font-weight: 600;
        margin-bottom: 16px;
        color: var(--dark-gray);
        font-size: 1.5rem;
    }

    .empty-state p {
        color: var(--dark-gray);
        margin-bottom: 32px;
        max-width: 450px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.7;
        font-size: 1.05rem;
    }

    .empty-state .btn {
        padding: 14px 28px;
        border-radius: var(--border-radius-sm);
        font-weight: 600;
        transition: var(--transition);
    }

    .empty-state .btn:hover {
        transform: translateY(-3px);
    }

    /* Pagination */
    .pagination {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin-top: 40px;
        flex-wrap: wrap;
    }

    .pagination a {
        padding: 10px 16px;
        background: var(--light-gray);
        border-radius: var(--border-radius-sm);
        text-decoration: none;
        color: var(--dark);
        transition: var(--transition);
        font-weight: 600;
        min-width: 44px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
    }

    .pagination a.active,
    .pagination a:hover {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: var(--shadow);
        border-color: var(--primary);
    }

    /* Export Button */
    .export-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        border-radius: var(--border-radius-sm);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
    }

    .export-btn:hover {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        color: white;
        text-decoration: none;
        transform: translateY(-3px);
        box-shadow: var(--shadow);
    }

    /* Card Actions */
    .card-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    /* Due Date */
    .due-date {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85em;
        color: var(--dark-gray);
        margin-top: 6px;
        padding: 8px 12px;
        background: var(--light-gray);
        border-radius: var(--border-radius-sm);
        border-left: 3px solid var(--primary);
    }

    .due-date.overdue {
        color: #c53030;
        font-weight: 600;
        background: #fef2f2;
        border-left-color: #c53030;
        animation: pulse 2s infinite;
    }

    /* Loading Animation */
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255, 255, 255, .3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Additional Animations */
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes cardSlideIn {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Stagger animation for cards */
    .cards-grid .card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .cards-grid .card:nth-child(2) {
        animation-delay: 0.2s;
    }

    .cards-grid .card:nth-child(3) {
        animation-delay: 0.3s;
    }

    .cards-grid .card:nth-child(4) {
        animation-delay: 0.4s;
    }

    .cards-grid .card:nth-child(5) {
        animation-delay: 0.5s;
    }

    .cards-grid .card:nth-child(6) {
        animation-delay: 0.6s;
    }

    /* Tab content animations */
    .tab-content {
        animation: fadeInUp 0.5s ease;
    }

    .status.review {
        background: linear-gradient(135deg, #fff3cd 0%, #fff9e6 100%);
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .status.review::before {
        content: "\f0d1";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
    }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-tasks"></i> My Assignments</h1>
    <div class="action-buttons">
        <a href="create.php" class="btn btn-success"><i class="fas fa-plus"></i> New Assignment</a>
    </div>
</div>

<div class="search-form">
    <form method="GET" id="searchForm">
        <input type="text" name="search" placeholder="Search assignments by title, description, or assignee..."
            value="<?= htmlspecialchars($searchTerm) ?>"
            id="searchInput">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
        <?php if ($searchTerm !== ''): ?>
            <a href="index.php" class="btn btn-ghost"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="tabs-container">
    <div class="tabs-line">
        <button class="tab-link active" onclick="openTab(event, 'Created')" id="createdTab">
            <i class="fas fa-paper-plane"></i> Created by Me
            <span class="tab-count"><?= count($createdTasks) ?></span>
        </button>
        <button class="tab-link" onclick="openTab(event, 'Inbox')" id="inboxTab">
            <i class="fas fa-inbox"></i> Assigned to Me
            <span class="tab-count"><?= count($inboxTasks) ?></span>
        </button>
        <button class="tab-link" onclick="openTab(event, 'Completed')" id="completedTab">
            <i class="fas fa-check-circle"></i> Completed by Me
            <span class="tab-count"><?= count($completedTasks) ?></span>
        </button>
    </div>

    <div id="Created" class="tab-content" style="display:block;">
        <div class="cards-grid">
            <?php if (empty($createdTasksPage)): ?>
                <div class="empty-state">
                    <i class="fas fa-paper-plane"></i>
                    <h3>No assignments created yet</h3>
                    <p>You haven't created any assignments. </p>
                    <!-- <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create New Assignment</a> -->
                </div>
            <?php else:
                foreach ($createdTasksPage as $task):
                    echo renderTaskCard($task);
                endforeach;
            endif; ?>
        </div>
        <?php renderPagination('created', $createdPages, $createdPage, $searchTerm); ?>
    </div>

    <div id="Inbox" class="tab-content" style="display:none;">
        <div class="cards-grid">
            <?php if (empty($inboxTasksPage)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No assignments in your inbox</h3>
                    <p>You don't have any pending assignments. When someone assigns you a task, it will appear here for you to work on.</p>
                </div>
            <?php else:
                foreach ($inboxTasksPage as $task):
                    echo renderTaskCard($task);
                endforeach;
            endif; ?>
        </div>
        <?php renderPagination('inbox', $inboxPages, $inboxPage, $searchTerm); ?>
    </div>

    <div id="Completed" class="tab-content" style="display:none;">
        <div style="margin-bottom: 20px;">
            <a href="completed_tasks_report.php" target="_blank" class="export-btn">
                <i class="fas fa-file-pdf"></i> Export Report
            </a>
        </div>
        <div class="cards-grid">
            <?php if (empty($completedTasksPage)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>No completed assignments</h3>
                    <p>You haven't completed any assignments yet. Complete tasks assigned to you to track your progress and achievements.</p>
                </div>
            <?php else:
                foreach ($completedTasksPage as $task):
                    echo renderTaskCard($task);
                endforeach;
            endif; ?>
        </div>
        <?php renderPagination('completed', $completedPages, $completedPage, $searchTerm); ?>
    </div>
</div>

<?php
function renderTaskCard($task)
{
    global $current_staff_name;
    $assignedBy = ($task['assigned_by_name'] === $current_staff_name) ? 'Me' : htmlspecialchars($task['assigned_by_name']);
    $assignedTo = ($task['assigned_to_name'] === $current_staff_name) ? 'Me' : htmlspecialchars($task['assigned_to_name']);
    $attachments = !empty($task['attachments']) ? implode(', ', $task['attachments']) : 'None';

    $statusClass = str_replace('_', '-', $task['status']);
    $statusText  = ucfirst(str_replace('_', ' ', $task['status']));

    // Priority array: [border-class, badge-class, badge-text, icon]
    $priorityClass = match ($task['priority']) {
        'low' => ['border-low', 'priority-low', 'Low', '<i class="fas fa-arrow-down"></i>'],
        'medium' => ['border-medium', 'priority-medium', 'Medium', '<i class="fas fa-arrow-right"></i>'],
        'high' => ['border-high', 'priority-high', 'High', '<i class="fas fa-arrow-up"></i>'],
        'urgent' => ['border-urgent', 'priority-urgent', 'Urgent', '<i class="fas fa-exclamation-triangle"></i>'],
        default => ['', 'priority-low', 'N/A', '<i class="fas fa-question"></i>']
    };

    $statusClass = match ($task['status']) {
        'pending'      => 'pending',
        'in_progress'  => 'in-progress',
        'completed'    => 'completed',
        'review'       => 'review',   // <-- NEW
        default        => 'pending',
    };

    $statusText = match ($task['status']) {
        'review' => 'Under Review',
        default  => ucfirst(str_replace('_', ' ', $task['status']))
    };

    // Due date handling
    $dueDateHtml = '';
    if (!empty($task['due_date'])) {
        $dueDate = date('M d, Y', strtotime($task['due_date']));
        $isOverdue = strtotime($task['due_date']) < time() && $task['status'] !== 'completed';
        $dueDateClass = $isOverdue ? 'overdue' : '';
        $dueDateIcon = $isOverdue ? 'fas fa-exclamation-triangle' : 'fas fa-clock';
        $dueDateHtml = "<div class='due-date $dueDateClass'><i class='$dueDateIcon'></i> <strong>Due:</strong> $dueDate</div>";
    }

    $actionButtons = [];
    $actionButtons[] = "<a href='view.php?id={$task['task_id']}' class='view-btn'><i class='fas fa-eye'></i> View Details</a>";
    if ($task['assigned_by_name'] === $current_staff_name) {
        $actionButtons[] = "<a href='edit.php?id={$task['task_id']}' class='view-btn edit'><i class='fas fa-edit'></i> Edit</a>";
        $actionButtons[] = "<a href='delete.php?id={$task['task_id']}' onclick=\"return confirm('Are you sure you want to delete this assignment?');\" class='view-btn delete'><i class='fas fa-trash'></i> Delete</a>";
    }

    $buttonsHtml = '<div class="card-actions">' . implode('', $actionButtons) . '</div>';

    return "
    <div class='card {$priorityClass[0]}'>
        <div class='card-header'>
            <h3>" . htmlspecialchars($task['title']) . "</h3>
            <div style='display:flex; gap:6px; flex-wrap:wrap;'>
                <span class='status {$statusClass}'>{$statusText}</span>
                <span class='priority-badge {$priorityClass[1]}'>{$priorityClass[3]} {$priorityClass[2]}</span>
            </div>
        </div>

        <div class='card-meta'>
            <div><i class='fas fa-user-check'></i> <strong>By:</strong> {$assignedBy}</div>
            <div><i class='fas fa-user'></i> <strong>To:</strong> {$assignedTo}</div>
            <div><i class='fas fa-calendar-alt'></i> <strong>Created:</strong> " . date('M d, Y', strtotime($task['created_at'])) . "</div>
        </div>

        {$dueDateHtml}

        <div>
            <div class='description-label'>Description</div>
            <div class='description'>" . nl2br(htmlspecialchars($task['description'])) . "</div>
        </div>

        <div class='attachments'><i class='fas fa-paperclip'></i> <strong>Attachments:</strong> {$attachments}</div>

        {$buttonsHtml}
    </div>";
}

function renderPagination($tab, $totalPages, $currentPage, $searchTerm)
{
    if ($totalPages <= 1) return;
    echo '<div class="pagination">';
    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $searchParam = $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '';
        echo "<a href='?{$tab}_page=$prevPage$searchParam' title='Previous Page'><i class='fas fa-chevron-left'></i></a>";
    }

    // Calculate page range to show
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $searchParam = $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '';
        echo "<a class='$active' href='?{$tab}_page=$i$searchParam'>$i</a>";
    }

    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $searchParam = $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '';
        echo "<a href='?{$tab}_page=$nextPage$searchParam' title='Next Page'><i class='fas fa-chevron-right'></i></a>";
    }
    echo '</div>';
}
?>

<script>
    function openTab(evt, tabName) {
        // Add loading animation to tab content
        const tabContent = document.getElementById(tabName);
        tabContent.style.opacity = '0.7';

        // Hide all tab contents
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(c => {
            c.style.display = 'none';
            c.style.opacity = '0';
        });

        // Remove active class from all tabs
        const links = document.querySelectorAll('.tab-link');
        links.forEach(l => l.classList.remove('active'));

        // Show the selected tab content and mark tab as active
        setTimeout(() => {
            tabContent.style.display = 'block';
            setTimeout(() => {
                tabContent.style.opacity = '1';
            }, 50);
        }, 200);

        evt.currentTarget.classList.add('active');

        // Update URL without reloading page
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName.toLowerCase());
        window.history.replaceState({}, '', url);
    }

    // Preserve active tab on page reload
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');

        if (activeTab) {
            const tabName = activeTab.charAt(0).toUpperCase() + activeTab.slice(1);
            const tabButton = document.getElementById(tabName + 'Tab');
            if (tabButton) {
                tabButton.click();
            }
        }

        // Add loading state to buttons on click
        const buttons = document.querySelectorAll('.view-btn, .btn');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.getAttribute('href') && !this.classList.contains('disabled')) {
                    this.classList.add('disabled');
                    const originalText = this.innerHTML;
                    this.innerHTML = '<span class="loading"></span> Loading...';

                    // Revert after 3 seconds if still on page
                    setTimeout(() => {
                        if (this.classList.contains('disabled')) {
                            this.classList.remove('disabled');
                            this.innerHTML = originalText;
                        }
                    }, 3000);
                }
            });
        });

        // Focus search input on page load if it's empty
        const searchInput = document.getElementById('searchInput');
        if (searchInput && searchInput.value === '') {
            setTimeout(() => {
                searchInput.focus();
            }, 500);
        }

        // Add smooth scrolling to pagination
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');

                // Add fade out animation
                document.querySelector('.cards-grid').style.opacity = '0.7';
                document.querySelector('.pagination').style.opacity = '0.7';

                setTimeout(() => {
                    window.location.href = href;
                }, 300);
            });
        });

        // Add hover effects to cards with delay
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });
</script>

<?php require_once 'footer.php'; ?>