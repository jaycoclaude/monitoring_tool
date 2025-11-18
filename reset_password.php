<?php
session_start();
require_once 'includes/config.php';

$token   = trim($_GET['token'] ?? '');
$success = isset($_GET['success']) && $_GET['success'] == '1';
$error   = $_GET['error'] ?? '';

// If no token at all → show clean error (link from email is broken or used)
if (empty($token) && !$success) {
    $show_invalid_token = true;
} else {
    $show_invalid_token = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Passcode - Rwanda FDA Monitoring Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #008751;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #6c757d;
            --text-dark: #333;
        }
        body { background-color: var(--light-gray); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; display: flex; align-items: center; padding: 20px; }
        .login-container { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; max-width: 420px; width: 100%; margin: 0 auto; }
        .login-header { background: white; padding: 2rem 2rem 1rem; text-align: center; border-bottom: 1px solid var(--medium-gray); }
        .login-body { padding: 2rem; }
        .logo { max-width: 180px; height: auto; }
        .welcome-text { font-size: 1.5rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem; }
        .subtitle { color: var(--dark-gray); font-size: 0.9rem; }
        .form-label { font-weight: 600; color: var(--text-dark); margin-bottom: 0.5rem; }
        .form-control { padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #dee2e6; transition: all 0.2s; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(0,135,81,0.1); }
        .input-group-text { background-color: var(--light-gray); border: 1px solid #dee2e6; }
        .password-container { position: relative; }
        .password-toggle { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--dark-gray); cursor: pointer; z-index: 5; }
        .btn-login { background-color: var(--primary-color); border: none; color: white; padding: 0.75rem; border-radius: 6px; font-weight: 600; transition: all 0.2s; width: 100%; }
        .btn-login:hover { background-color: #006b41; }
        .back-to-login { text-align: center; margin-top: 1.5rem; }
        .back-to-login a { color: var(--primary-color); text-decoration: none; font-size: 0.9rem; }
        .back-to-login a:hover { text-decoration: underline; }
        .alert { border-radius: 6px; padding: 0.75rem 1rem; border: none; }
        .alert-success { background-color: rgba(25,135,84,0.1); color: #0f5132; border-left: 4px solid #198754; }
        .alert-danger { background-color: rgba(220,53,69,0.1); color: #721c24; border-left: 4px solid #dc3545; }
        .system-info { background-color: var(--light-gray); border-radius: 6px; padding: 1rem; margin-top: 1.5rem; font-size: 0.85rem; color: var(--dark-gray); }
        .system-info i { color: var(--primary-color); margin-right: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-container">
                    <img src="assets/images/logo.png" alt="Rwanda FDA Logo" class="logo">
                    <h1 class="welcome-text">Reset Passcode</h1>
                    <p class="subtitle">Create a strong new passcode</p>
                </div>
            </div>

            <div class="login-body">

                <!-- SUCCESS MESSAGE -->
                <?php if ($success): ?>
                    <div class="alert alert-success text-center mb-4">
                        <i class="fas fa-check-circle fa-2x mb-3"></i><br>
                        <strong>Your passcode has been successfully updated!</strong>
                    </div>
                    <div class="d-grid">
                        <a href="index.php" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                        </a>
                    </div>

                <!-- INVALID OR MISSING TOKEN (no token in URL) -->
                <?php elseif ($show_invalid_token): ?>
                    <div class="alert alert-danger text-center mb-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>
                        <strong>Invalid or missing token.</strong><br>
                        This reset link is not valid or has already been used.
                    </div>
                    <div class="d-grid">
                        <a href="forgot_password.php" class="btn btn-outline-success">
                            <i class="fas fa-key me-2"></i>Request New Reset Link
                        </a>
                    </div>

                <!-- SHOW FORM (valid token present) -->
                <?php else: ?>

                    <!-- Show any error from process_reset_password.php -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars(urldecode($error)) ?>
                        </div>
                    <?php endif; ?>

                    <form action="process_reset_password.php" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="mb-4">
                            <label for="password" class="form-label">New Passcode</label>
                            <div class="password-container">
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter new passcode" required minlength="8">
                                </div>
                                <button type="button" class="password-toggle" id="toggleNewPassword">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">Confirm Passcode</label>
                            <div class="password-container">
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                           placeholder="Confirm new passcode" required>
                                </div>
                                <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-key me-2"></i>Update Passcode
                            </button>
                        </div>
                    </form>

                    <div class="back-to-login">
                        <a href="index.php"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
                    </div>

                    <div class="system-info">
                        <p><i class="fas fa-info-circle"></i> Use at least 8 characters including letters, numbers, and symbols.</p>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId, button) {
            const field = document.getElementById(fieldId);
            const icon = button.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.getElementById('toggleNewPassword')?.addEventListener('click', function() {
            togglePassword('password', this);
        });
        document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
            togglePassword('password_confirm', this);
        });

        <?php if (!$success && !$show_invalid_token): ?>
            document.getElementById('password').focus();
        <?php endif; ?>
    </script>
</body>
</html>