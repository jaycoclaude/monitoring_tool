<?php
session_start();

// Retrieve messages or old input
$success = isset($_GET['sent']) ? true : false;
$error   = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$old_email = isset($_SESSION['old_email']) ? $_SESSION['old_email'] : '';
unset($_SESSION['old_email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Passcode - Rwanda FDA Monitoring Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #008751; /* Rwanda FDA green */
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #6c757d;
            --text-dark: #333;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
        }
        
        .login-header {
            background-color: white;
            padding: 2rem 2rem 1rem 2rem;
            text-align: center;
            border-bottom: 1px solid var(--medium-gray);
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .logo-container {
            margin-bottom: 1rem;
        }
        
        .logo {
            max-width: 180px;
            height: auto;
  }
        
        .welcome-text {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        
        .subtitle {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 135, 81, 0.1);
        }
        
        .input-group-text {
            background-color: var(--light-gray);
            border: 1px solid #dee2e6;
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            color: white;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.2s;
            width: 100%;
        }
        
        .btn-login:hover {
            background-color: #006b41;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-to-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 6px;
            padding: 0.75rem 1rem;
            border: none;
        }
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #0f5132;
            border-left: 4px solid #198754;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .system-info {
            background-color: var(--light-gray);
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--dark-gray);
        }
        
        .system-info i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-container">
                    <img src="assets/images/logo.png" alt="Rwanda FDA Logo" class="logo">
                    <h1 class="welcome-text">Forgot Passcode</h1>
                    <p class="subtitle">Enter your email to reset your passcode</p>
                </div>
            </div>
            
            <div class="login-body">
                <?php if ($success): ?>
                    <div class="alert alert-success mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        If an account exists with that email, a password reset link has been sent.
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form action="send_reset_link.php" method="POST">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Enter your staff email" 
                                   value="<?= htmlspecialchars($old_email) ?>" required autofocus>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                        </button>
                    </div>
                    
                    <div class="back-to-login">
                        <a href="index.php"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
                    </div>
                </form>

                <div class="system-info">
                    <p><i class="fas fa-info-circle"></i> A reset link will be sent to your email if the account exists.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus on email field
        document.getElementById('email').focus();
    </script>
</body>
</html>