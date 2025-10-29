<?php
require_once '../includes/auth.php';
require_once 'data.php';

$current_staff = $_SESSION['user_id'];

if (!$current_staff) {
    header('Location: ../index.php');
    exit();
}

$tasks = getTasks($current_staff); 
$searchTerm = trim($_GET['search'] ?? '');

// Filter tasks if search term exists
if ($searchTerm !== '') {
    $tasks = array_filter($tasks, function($t) use ($searchTerm) {
        return stripos($t['title'], $searchTerm) !== false ||
               stripos($t['description'], $searchTerm) !== false ||
               stripos($t['assigned_by_name'], $searchTerm) !== false ||
               stripos($t['assigned_to_name'], $searchTerm) !== false;
    });
}

// Pagination
$tasksPerPage = 6;
$totalTasks = count($tasks);
$totalPages = ceil($totalTasks / $tasksPerPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Slice tasks for current page
$start = ($currentPage - 1) * $tasksPerPage;
$tasksForPage = array_slice($tasks, $start, $tasksPerPage);
?>
<?php require_once 'header.php'; ?>

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
               style="flex: 1; padding: 8px 12px; border-radius: 5px; border: 1px solid #ccc;">
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

<div class="cards-grid">
    <?php if (empty($tasksForPage)): ?>
        <div class="no-tasks">
            <h3><i class="fas fa-exclamation-circle"></i> No assignments found</h3>
            <p>Try adjusting your search or 
                <a href="create.php" class="btn btn-success small-btn">
                    <i class="fas fa-plus"></i> create one
                </a>.
            </p>
        </div>
    <?php else: foreach ($tasksForPage as $task): ?>
        <div class="card" onclick="location.href='view.php?id=<?= $task['task_id'] ?>'">
            <div class="card-header">
                <h3><i class="fas fa-file-alt"></i> <?= htmlspecialchars($task['title']) ?></h3>
                <span class="status <?= getStatusClass($task['status']) ?>">
                    <i class="fas fa-info-circle"></i> <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                </span>
            </div>
            <p class="card-description">
                <strong> Description:</strong> 
                <?= htmlspecialchars(substr($task['description'], 0, 120)) ?>...
            </p>
            <div class="meta">
                <span class="meta-item">
                    <strong>Due Date:</strong> <?= formatDate($task['due_date']) ?>
                </span>
                <span class="meta-item">
                    <strong>Assigned To:</strong> <?= htmlspecialchars($task['assigned_to_name']) ?>
                </span>
                <span class="meta-item">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Priority:</strong> <?= getPriorityBadge($task['priority']) ?>
                </span>
            </div>
            <div class="card-actions">
                <a href="view.php?id=<?= $task['task_id'] ?>" class="btn btn-primary small-btn">
                    <i class="fas fa-eye"></i> View Details
                </a>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <div class="pagination" style="margin-top: 20px; text-align: center;">
        <?php if ($currentPage > 1): ?>
            <a href="?search=<?= urlencode($searchTerm) ?>&page=<?= $currentPage-1 ?>" class="btn btn-ghost">&laquo; Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?search=<?= urlencode($searchTerm) ?>&page=<?= $i ?>" 
               class="btn <?= $i === $currentPage ? 'btn-primary' : 'btn-ghost' ?>">
               <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="?search=<?= urlencode($searchTerm) ?>&page=<?= $currentPage+1 ?>" class="btn btn-ghost">Next &raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
