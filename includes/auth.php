<?php
// includes/auth.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../indexs.php');
    exit;
}

/**
 * Get current logged-in staff info
 */
function getCurrentStaff() {
    global $pdo;
    static $staff = null;
    if ($staff === null) {
        $stmt = $pdo->prepare("
            SELECT s.*, u.user_access, u.role_id
            FROM tbl_staff s
            JOIN tbl_hm_users u ON s.user_id = u.user_id
            WHERE s.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $staff;
}

/**
 * Check if current user has a permission
 */
function can($permission_key): bool {
    if (!isset($_SESSION['user_id'])) return false;

    static $user_permissions = null;
    global $pdo;

    if ($user_permissions === null) {
        $user = getCurrentStaff();
        $user_id = $_SESSION['user_id'];
        $role_id = $user['role_id'] ?? 0;

        // Fetch permissions from role + user overrides
        $stmt = $pdo->prepare("
            SELECT p.permission_key
            FROM tbl_permissions p
            JOIN tbl_role_permissions rp ON p.permission_id = rp.permission_id
            WHERE rp.role_id = ?

            UNION

            SELECT p.permission_key
            FROM tbl_permissions p
            JOIN tbl_user_permissions up ON p.permission_id = up.permission_id
            WHERE up.user_id = ?
        ");
        $stmt->execute([$role_id, $user_id]);
        $user_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    return in_array($permission_key, $user_permissions);
}

/**
 * Enforce permission and block access if user lacks it
 */
function requirePermission($permission_key) {
    if (!can($permission_key)) {
        header('HTTP/1.1 403 Forbidden');
        echo "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
        exit;
    }
}
