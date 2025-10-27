<?php
session_start();
require_once 'includes/config.php'; // PDO connection

// Only process if form submitted
if (isset($_POST['btn_login'])) {
    $email = trim($_POST['txtuseremail']);
    $pass = trim($_POST['txtuserpasscode']);

    if (empty($email) || empty($pass)) {
        $error = "Please enter both email and passcode.";
        $_SESSION['login_error'] = $error;
        $_SESSION['old_email'] = $email;
        header("Location: index.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, user_email, user_passcode, user_access, user_status 
                               FROM tbl_hm_users WHERE user_email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && (password_verify($pass, $user['user_passcode']) || $pass === $user['user_passcode'])) {
            if ($user['user_status'] == 1) {
                // Set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['user_email'];
                $_SESSION['user_access'] = $user['user_access'];

                header("Location: landing_page.php");
                exit();
            } else {
                $_SESSION['login_error'] = "Your account is inactive. Please contact the administrator.";
                $_SESSION['old_email'] = $email;
                header("Location: index.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Incorrect username or password.";
            $_SESSION['old_email'] = $email;
            header("Location: index.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['login_error'] = "Database error: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
} else {
    // If accessed directly without submitting form
    header("Location: index.php");
    exit();
}
