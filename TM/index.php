<?php require_once 'header.php'; ?>

<div class="page-header">
    <h1 class="page-title">My Assignments</h1>
    <div class="action-buttons">
        <button class="btn btn-primary" onclick="personalReport('')">
            <i class="fas fa-file-alt"></i> Generate Report
        </button>
        <a href="create.php" class="btn btn-success">
            <i class="fas fa-plus"></i> New Assignment
        </a>
    </div>
</div>

<div class="search-form">
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search assignments..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
    </form>
</div>

<div class="cards-grid">
<?php
$allTasks = getTasks();
$searchTerm = $_GET['search'] ?? '';
$tasks = $searchTerm ? searchTasks($searchTerm, $allTasks) : $allTasks;

if (empty($tasks)): ?>
    <div class="no-tasks">
        <h3>No assignments found</h3>
        <p>Try adjusting your search or <a href="create.php" class="btn btn-success small-btn">create a new assignment</a>.</p>
    </div>
<?php else: foreach ($tasks as $task): ?>
    <div class="card" onclick="location.href='view.php?id=<?php echo $task['id']; ?>'">
        <div class="card-header">
            <h3><?php echo htmlspecialchars($task['title']); ?></h3>
            <span class="status <?php echo getStatusClass($task['status']); ?>">
                <?php echo ucfirst(str_replace('-', ' ', $task['status'])); ?>
            </span>
        </div>
        <p class="card-description"><?php echo htmlspecialchars($task['description']); ?></p>
        <div class="meta">
            <span class="meta-item"><i class="far fa-calendar-alt"></i> <?php echo formatDate($task['dueDate']); ?></span>
            <span class="meta-item"><i class="fas fa-user"></i> <?php echo htmlspecialchars($task['to']); ?></span>
            <span class="meta-item"><?php echo getPriorityBadge($task['priority']); ?></span>
        </div>
        <div class="card-actions">
            <a href="view.php?id=<?php echo $task['id']; ?>" class="btn btn-primary small-btn">View Details</a>
        </div>
    </div>
<?php endforeach; endif; ?>
</div>

<?php require_once 'footer.php'; ?>
