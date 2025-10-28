<?php
require_once 'data.php';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TaskFlow</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<header class="header">
  <div class="header-content">
    <div class="branding">
      <div class="logo">T</div>
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

<div class="dashboard-container">
