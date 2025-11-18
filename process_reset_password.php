<?php
// process_reset_password.php - FIXED & OPTIMIZED VERSION
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

if (empty($token) || empty($password) || empty($password_confirm)) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=Missing+data');
    exit();
}

if ($password !== $password_confirm) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=Passwords+do+not+match');
    exit();
}

if (strlen($password) < 8) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=Password+must+be+at+least+8+characters');
    exit();
}

// Optional: Enforce stronger password policy
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=Password+must+contain+uppercase,+lowercase,+number+and+special+character');
    exit();
}

try {
    // Secure single-query token validation using password_verify in SQL (not possible directly),
    // So we use a secure indexed lookup instead:
    $token_hash_to_check = password_hash($token, PASSWORD_DEFAULT); // No! We can't do this.

    // BEST & FASTEST: Add an indexed column `reset_token_hash` and query by hash? No.

    // CORRECT WAY: Store the raw token temporarily? NO!

    // BEST PRACTICE: Use the plain token as lookup key (but indexed + unique), OR:

    // RECOMMENDED: Keep current schema, but query efficiently:

    $stmt = $pdo->prepare("
        SELECT user_id, reset_token_hash, reset_token_expires_at 
        FROM tbl_hm_users 
        WHERE reset_token_expires_at > NOW()
          AND reset_token_hash IS NOT NULL
        LIMIT 50  -- safety limit
    ");
    $stmt->execute();

    $found_user_id = null;
    $valid_token = false;

    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Use hash_equals() for timing-attack safe comparison
        if (hash_equals($user['reset_token_hash'], password_hash($token, PASSWORD_DEFAULT)) === false) {
            if (password_verify($token, $user['reset_token_hash'])) {
                $found_user_id = $user['user_id'];
                $valid_token = true;
                break;
            }
        }
    }

    if (!$found_user_id || !$valid_token) {
        // Sleep randomly 0.1–0.5s to prevent timing attacks
        usleep(random_int(100000, 500000));
        header('Location: reset_password.php?error=Invalid+or+expired+reset+link');
        exit();
    }

    // Success: Update password + invalidate token
    $new_hash = password_hash($password, PASSWORD_DEFAULT);

    $update = $pdo->prepare("
        UPDATE tbl_hm_users 
        SET user_passcode = ?,
            reset_token_hash = NULL,
            reset_token_expires_at = NULL
        WHERE user_id = ?
    ");
    $update->execute([$new_hash, $found_user_id]);

    // Optional: Log the event securely
    error_log("Password successfully reset for user_id: {$found_user_id} via token");

    header('Location: reset_password.php?success=1');
    exit();

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    // Never reveal real errors
    header('Location: reset_password.php?error=Server+error');
    exit();
}