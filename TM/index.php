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
$completedTasks = array_filter(
    $tasks,
    fn($t) =>
    $t['status'] == 'completed' &&
        ($t['assigned_to_name'] === $current_staff_name || $t['assigned_by_email'] === $current_staff_email)
);
$inboxTasks = array_filter(
    $tasks,
    fn($t) =>    $t['assigned_to_name'] === $current_staff_name &&        in_array($t['status'], ['pending', 'in_progress', 'review'])
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">


<div class="page-header">
    <h1 class="page-title"><i class="fas fa-tasks"></i> My Assignments</h1>
    <div class="action-buttons">
        <?php if (can('create_task')): ?>
            <a href="create.php" class="btn btn-success"><i class="fas fa-plus"></i> New Assignment</a>
        <?php endif; ?>
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
            <span class="tab-count pulse"><?= count($createdTasks) ?></span>
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
    if (can('edit') && $task['assigned_by_name'] === $current_staff_name) {
        $actionButtons[] = "<a href='edit.php?id={$task['task_id']}' class='view-btn edit'><i class='fas fa-edit'></i> Edit</a>";
    }

    if (can('delete_task') && $task['assigned_by_name'] === $current_staff_name) {
        $actionButtons[] = "<a href='delete.php?id={$task['task_id']}' onclick=\"return confirm('Are you sure?');\" class='view-btn delete'><i class='fas fa-trash'></i> Delete</a>";
    }


    $buttonsHtml = '<div class="card-actions">' . implode('', $actionButtons) . '</div>';

    return "
    <div class='card {$priorityClass[0]}'>
    <div class='card-header'>
        <h3>" . htmlspecialchars($task['title']) . "</h3>
        <div style='display:flex; gap:6px; flex-wrap:wrap;'>
            <span class='status {$statusClass}'>
                <i class='fas fa-spinner'></i> {$statusText}
            </span>
            <span class='priority-badge {$priorityClass[1]}'>
                {$priorityClass[3]} {$priorityClass[2]}
            </span>
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
</div>

";
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