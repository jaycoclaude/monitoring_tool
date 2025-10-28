<?php
require_once 'data.php';
$currentUser = getCurrentUser();
$allTasks   = getTasks();

// ---------- FILTER ----------
$tab        = $_GET['tab'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

if ($tab === 'received') {
  $tasks = array_filter($allTasks, fn($t) => $t['to'] === $currentUser);
} elseif ($tab === 'sent') {
  $tasks = array_filter($allTasks, fn($t) => $t['from'] === $currentUser);
} else {
  $tasks = $allTasks;
}
if ($searchTerm !== '') {
  $tasks = searchTasks($searchTerm, $tasks);
}
$tasks = array_values($tasks);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>TaskFlow – My Assignments</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 3 (kept for compatibility) -->
  <link rel="stylesheet"
    href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

  <!-- Font Awesome -->
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700"
    rel="stylesheet">

  <style>
    /* -------------------------------------------------
           GLOBAL RESET & TYPOGRAPHY
        ------------------------------------------------- */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Nunito', sans-serif;
      background: #f5f7f9;
      color: #333;
      line-height: 1.6;
    }

    /* -------------------------------------------------
           TOP-BAR (exact copy from MA-Monitoring)
        ------------------------------------------------- */
    .header {
      position: sticky;
      top: 0;
      z-index: 30;
      backdrop-filter: blur(4px);
      background: rgba(255, 255, 255, .8);
      border-bottom: 1px solid #e7eef6;
      box-shadow: 0 1px 10px rgba(0, 0, 0, .04);
    }

    .header-content {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 24px;
    }

    html,
    body {
      height: 100%;
      margin: 0;
      display: flex;
      flex-direction: column;
    }

    .dashboard-container {
      flex: 1;
      /* This ensures main content expands to fill space */
    }

    .footer {
      margin-top: auto;
      /* Pushes footer to bottom */
      padding: 16px 24px;
      background: #0f5e8a;
      color: #fff;
      text-align: center;
      font-size: 0.9rem;
    }

    .branding {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: #0f5e8a;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 600;
      font-size: 16px;
    }

    .brand-text h1 {
      font-size: 14px;
      font-weight: 600;
      color: #1a202c;
    }

    .brand-text p {
      font-size: 11px;
      color: #6b7a86;
      margin-top: -2px;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .icon-button {
      position: relative;
      padding: 8px;
      border-radius: 50%;
      background: none;
      border: none;
      cursor: pointer;
      transition: background .2s;
    }

    .icon-button:hover {
      background: rgba(0, 0, 0, .05);
    }

    .notification-badge {
      position: absolute;
      top: 8px;
      right: 8px;
      width: 8px;
      height: 8px;
      background: #e53e3e;
      border-radius: 50%;
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #fff;
      border: 1px solid #e8f1f8;
      padding: 6px 12px;
      border-radius: 8px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
    }

    .user-avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: #f0f6fb;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #0f5e8a;
    }

    .user-name {
      font-size: 12px;
      font-weight: 500;
      display: none;
    }

    @media (min-width:640px) {
      .user-name {
        display: block;
      }
    }

    /* -------------------------------------------------
           MAIN CONTAINER
        ------------------------------------------------- */
    .dashboard-container {
      max-width: 1800px;
      margin: 0 auto;
      padding: 20px;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid #e7eef6;
    }

    .page-title {
      font-size: 24px;
      font-weight: 600;
      color: #1a202c;
    }

    .search-form {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-form input {
      padding: 8px 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      min-width: 250px;
    }

    .btn {
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
      cursor: pointer;
      transition: all .2s;
    }

    .btn-primary {
      background: #0f5e8a;
      color: #fff;
      border: none;
    }

    .btn-primary:hover {
      background: #0d4f70;
    }

    .btn-ghost {
      background: transparent;
      color: #0f5e8a;
      border: 1px solid #0f5e8a;
    }

    .btn-ghost:hover {
      background: #e6f2fa;
      color: #0d4f70;
    }

    .btn-success {
      background: #1e8741;
      color: #fff;
      border: none;
    }

    .btn-success:hover {
      background: #1a6d36;
    }

    /* -------------------------------------------------
           CARDS GRID (same look as MA-Monitoring stat-cards)
        ------------------------------------------------- */
    .cards-grid {
      display: grid;
      gap: 20px;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }

    .card {
      background: #fff;
      border-radius: 12px;
      padding: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
      border: 1px solid #e7eef6;
      transition: all .3s;
      cursor: pointer;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
    }

    .card h3 {
      margin-bottom: 8px;
      font-size: 1.1rem;
      color: #1a202c;
    }

    .card p {
      font-size: 0.9rem;
      color: #6b7a86;
      margin-bottom: 12px;
    }

    .meta {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      font-size: 0.8rem;
      margin-bottom: 12px;
    }

    .meta-item {
      color: #6b7a86;
    }

    .status {
      padding: 2px 8px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.75rem;
    }

    .status.pending {
      background: #fff5e6;
      color: #cc6d00;
    }

    .status.in-progress {
      background: #e6f0ff;
      color: #003087;
    }

    .status.completed {
      background: #e6ffe6;
      color: #1e8741;
    }

    .card-actions {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
    }

    /* -------------------------------------------------
           FOOTER
        ------------------------------------------------- */
    .footer {
      margin-top: 40px;
      padding: 16px 24px;
      background: #0f5e8a;
      color: #fff;
      text-align: center;
      font-size: 0.9rem;
    }

    /* -------------------------------------------------
           RESPONSIVE
        ------------------------------------------------- */
    @media (max-width:900px) {
      .page-header {
        flex-direction: column;
        align-items: stretch;
      }

      .search-form input {
        min-width: 100%;
      }
    }
  </style>
</head>

<body>

  <!------------------- TOP-BAR ------------------->
  <header class="header">
    <div class="header-content">
      <div class="branding">
        <div class="logo">H</div>
        <div class="brand-text">
          <h1>TaskFlow</h1>
          <p>Assignment Management</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="icon-button" title="Notifications">
          <i class="fas fa-bell"></i>
          <span class="notification-badge"></span>
        </button>
        <div class="user-profile">
          <div class="user-avatar"><i class="fas fa-user"></i></div>
          <div class="user-name"><?php echo htmlspecialchars($currentUser); ?></div>
        </div>
      </div>
    </div>
  </header>

  <!------------------- MAIN CONTENT ------------------->
  <div class="dashboard-container">

    <div class="page-header">
      <h1 class="page-title">My Assignments</h1>

      <div class="search-form">
        <form method="GET" action="">
          <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
          <input type="text" name="search"
            placeholder="Search assignments..."
            value="<?php echo htmlspecialchars($searchTerm); ?>">
          <button type="submit" class="btn btn-primary">Search</button>
          <?php if ($searchTerm !== ''): ?>
            <a href="?tab=<?php echo htmlspecialchars($tab); ?>"
              class="btn btn-ghost">Clear</a>
          <?php endif; ?>
        </form>

        <div style="display:flex;gap:8px;">
          <button class="btn btn-primary"
            onclick="personalReport(document.querySelector('input[name=search]').value.trim())">
            Generate Personal Report
          </button>
          <a href="create.php" class="btn btn-success">+ New Assignment</a>
        </div>
      </div>
    </div>

    <!-- ------------------- CARD GRID ------------------- -->
    <div class="cards-grid">
      <?php if (empty($tasks)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:40px;">
          <h3>No assignments found</h3>
        </div>
      <?php else: ?>
        <?php foreach ($tasks as $task): ?>
          <div class="card"
            onclick="location.href='view.php?id=<?php echo $task['id']; ?>'">
            <h3><?php echo htmlspecialchars($task['title']); ?></h3>
            <p><?php echo htmlspecialchars($task['description']); ?></p>

            <div class="meta">
              <span class="meta-item">Due: <?php echo formatDate($task['dueDate']); ?></span>
              <span class="meta-item">From: <?php echo htmlspecialchars($task['from']); ?></span>
              <span class="meta-item">To: <?php echo htmlspecialchars($task['to']); ?></span>
              <span class="meta-item"><?php echo getPriorityBadge($task['priority']); ?></span>
              <span class="status <?php echo getStatusClass($task['status']); ?>">
                <?php echo ucfirst(str_replace('-', ' ', $task['status'])); ?>
              </span>
            </div>

            <div class="card-actions">
              <a href="view.php?id=<?php echo $task['id']; ?>" class="btn btn-primary">View</a>
              <button class="btn btn-ghost"
                onclick="event.stopPropagation();personalReport('<?php echo htmlspecialchars($task['to']); ?>')">
                Report
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <!------------------- FOOTER ------------------->
  <footer class="footer">
    © <?php echo date('Y'); ?> TaskFlow – All rights reserved.
  </footer>

</body>

</html>