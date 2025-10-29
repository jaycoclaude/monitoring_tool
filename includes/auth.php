<?php
// includes/auth.php
session_start();
require_once  'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../indexs.php');
    exit;
}

// Get current logged-in staff info
function getCurrentStaff() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.*, u.user_access FROM tbl_staff s 
                           JOIN tbl_hm_users u ON s.user_id = u.user_id 
                           WHERE s.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


?>