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

// Get current user's staff_id
$db = getDB();
$stmt = $db->prepare("SELECT staff_id, staff_names FROM tbl_staff WHERE user_id = :user_id AND staff_status = 1 LIMIT 1");
$stmt->execute([':user_id' => $current_user_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
$current_staff_id = $staff['staff_id'] ?? 0;

// Filter tasks if search term exists
if ($searchTerm !== '') {
    $tasks = array_filter($tasks, function($t) use ($searchTerm) {
        return stripos($t['title'], $searchTerm) !== false ||
               stripos($t['description'], $searchTerm) !== false ||
               stripos($t['assigned_by_name'], $searchTerm) !== false ||
               stripos($t['assigned_to_name'], $searchTerm) !== false;
    });
}

// Separate tasks for tabs
$createdTasks   = array_filter($tasks, fn($t) => $t['assigned_by_email'] === $_SESSION['user_email']);
$inboxTasks     = array_filter($tasks, fn($t) => $t['assigned_to_name'] === $staff['staff_names'] && $t['status'] != 'completed');
$completedTasks = array_filter($tasks, fn($t) => $t['assigned_to_name'] === $staff['staff_names'] && $t['status'] == 'completed');

// Pagination setup
$tasksPerPage = 6;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

function paginateTasks($tasks, $tasksPerPage, $currentPage) {
    $totalTasks = count($tasks);
    $totalPages = ceil($totalTasks / $tasksPerPage);
    $start = ($currentPage - 1) * $tasksPerPage;
    $tasksForPage = array_slice($tasks, $start, $tasksPerPage);
    return [$tasksForPage, $totalPages];
}

list($createdTasksPage, $createdPages)   = paginateTasks($createdTasks, $tasksPerPage, $currentPage);
list($inboxTasksPage, $inboxPages)       = paginateTasks($inboxTasks, $tasksPerPage, $currentPage);
list($completedTasksPage, $completedPages) = paginateTasks($completedTasks, $tasksPerPage, $currentPage);

require_once 'header.php';
?>

<style>
/* --- Modern Rounded Tabs --- */
.tabs-container { margin-bottom: 24px; }
.tabs-line { display: flex; border-bottom: 2px solid #e5e5ea; gap: 12px; }
.tab-link {
    padding: 12px 20px;
    background: transparent;
    border: none;
    font-size: 16px;
    font-weight: 600;
    color: #6e6e73;
    cursor: pointer;
    position: relative;
    transition: color 0.2s;
    border-radius: 12px 12px 0 0;
}
.tab-link .tab-count { font-weight: 500; color: #0f5e8a; margin-left: 6px; }
.tab-link.active { color: #0f5e8a; }
.tab-link.active::after {
    content: "";
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 3px;
    background: #0f5e8a;
    border-radius: 3px 3px 0 0;
}

/* Cards grid - responsive 3 per row */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr); /* mobile default */
    gap: 16px;
}
@media (min-width: 768px) {
    .cards-grid { grid-template-columns: repeat(2, 1fr); } /* tablets */
}
@media (min-width: 1024px) {
    .cards-grid { grid-template-columns: repeat(3, 1fr); } /* desktops */
}

/* Modern Card Design */
.card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
    padding: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.card-header h3 { font-size: 1.2rem; font-weight: 600; margin: 0; color: #222; }
.card-meta { font-size: 0.85em; color: #555; margin-bottom: 12px; }
.status {
    font-size: 0.85em;
    padding: 4px 10px;
    border-radius: 12px;
    text-transform: capitalize;
    font-weight: 500;
}
.status.pending { background: #fce8b2; color: #856404; }
.status.in_progress { background: #d1ecf1; color: #0c5460; }
.status.completed { background: #d4edda; color: #155724; }
.attachments { font-size: 0.85em; margin-top: 10px; color: #333; }
.view-btn {
    display: inline-block;
    margin-top: 14px;
    padding: 8px 18px;
    font-size: 0.9em;
    background: #0f5e8a;
    color: #fff;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.2s, transform 0.2s;
}
.view-btn:hover { background: #094965; transform: translateY(-1px); }
.no-tasks { text-align: center; padding: 40px 0; color: #888; }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-tasks"></i> My Assignments</h1>
    <div class="action-buttons">
        <a href="create.php" class="btn btn-success">
            <i class="fas fa-plus"></i> New Assignment
        </a>
    </div>
</div>

<div class="search-form" style="margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 8px; align-items: center;">
        <input type="text" name="search" placeholder="Search assignments..." 
               value="<?= htmlspecialchars($searchTerm) ?>" 
               style="flex: 1; padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc;">
        <button type="submit" class="btn btn-primary" style="padding: 8px 12px;">
            <i class="fas fa-search"></i> Search
        </button>
        <?php if ($searchTerm !== ''): ?>
            <a href="index.php" class="btn btn-ghost" style="padding: 8px 12px;">
                <i class="fas fa-times"></i> Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabs -->
<div class="tabs-container">
    <div class="tabs-line">
        <button class="tab-link active" onclick="openTab(event, 'Created')">
            Created <span class="tab-count">(<?= count($createdTasks) ?>)</span>
        </button>
        <button class="tab-link" onclick="openTab(event, 'Inbox')">
            Inbox <span class="tab-count">(<?= count($inboxTasks) ?>)</span>
        </button>
        <button class="tab-link" onclick="openTab(event, 'Completed')">
            Completed <span class="tab-count">(<?= count($completedTasks) ?>)</span>
        </button>
    </div>
</div>

<?php
function renderTaskCard($task) {
    $attachments = !empty($task['attachments']) ? implode(', ', $task['attachments']) : 'None';
    return "
    <div class='card'>
        <div class='card-header'>
            <h3>" . htmlspecialchars($task['title']) . "</h3>
            <span class='status " . getStatusClass($task['status']) . "'>" . ucfirst(str_replace('_',' ',$task['status'])) . "</span>
        </div>
        <div class='card-meta'>
            <div><i class='fas fa-user-check'></i> <strong>Assigned By:</strong> " . htmlspecialchars($task['assigned_by_name']) . "</div>
            <div><i class='fas fa-user'></i> <strong>Assigned To:</strong> " . htmlspecialchars($task['assigned_to_name']) . "</div>
            <div><i class='fas fa-calendar-alt'></i> <strong>Created:</strong> " . date('M d, Y', strtotime($task['created_at'])) . "</div>
        </div>
        <p>" . htmlspecialchars(substr($task['description'],0,150)) . "...</p>
        <div class='attachments'><i class='fas fa-paperclip'></i> <strong>Attachments:</strong> {$attachments}</div>
        <a class='view-btn' href='view.php?id={$task['task_id']}'><i class='fas fa-eye'></i> View Details</a>
    </div>";
}
?>

<div id="Created" class="tab-content" style="display:block;">
    <div class="cards-grid">
        <?php if(empty($createdTasksPage)): ?>
            <div class="no-tasks"><h3>No created tasks</h3></div>
        <?php else: foreach($createdTasksPage as $task): echo renderTaskCard($task); endforeach; endif; ?>
    </div>
</div>

<div id="Inbox" class="tab-content" style="display:none;">
    <div class="cards-grid">
        <?php if(empty($inboxTasksPage)): ?>
            <div class="no-tasks"><h3>No inbox tasks</h3></div>
        <?php else: foreach($inboxTasksPage as $task): echo renderTaskCard($task); endforeach; endif; ?>
    </div>
</div>

<div id="Completed" class="tab-content" style="display:none;">
    <div class="cards-grid">
        <?php if(empty($completedTasksPage)): ?>
            <div class="no-tasks"><h3>No completed tasks</h3></div>
        <?php else: foreach($completedTasksPage as $task): echo renderTaskCard($task); endforeach; endif; ?>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(c => c.style.display = 'none');
    const links = document.querySelectorAll('.tab-link');
    links.forEach(l => l.classList.remove('active'));
    document.getElementById(tabName).style.display = 'block';
    evt.currentTarget.classList.add('active');
}
</script>

<?php require_once 'footer.php'; ?>
