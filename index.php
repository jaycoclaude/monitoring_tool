<?php
session_start();

// Retrieve error message if it exists
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
$old_email = isset($_SESSION['old_email']) ? $_SESSION['old_email'] : '';

// Clear session error after showing
unset($_SESSION['login_error']);
unset($_SESSION['old_email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rwanda FDA Monitoring Tool</title>
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
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--dark-gray);
            cursor: pointer;
            z-index: 5;
        }
        
        .password-container {
            position: relative;
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
        
        .forgot-password {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 6px;
            padding: 0.75rem 1rem;
            border: none;
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
                    <!-- Rwanda FDA Logo -->
                    <img src="assets/images/logo.png" alt="Rwanda FDA Logo" class="logo">
                    <h1 class="welcome-text">Monitoring Tool</h1>
                    <p class="subtitle">Staff Login Portal</p>
                </div>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
                
                <form method="post" action="login.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="useremail" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control" id="useremail" placeholder="Enter your staff email" name="txtuseremail" value="<?php echo isset($_POST['txtuseremail']) ? htmlspecialchars($_POST['txtuseremail']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="userpasscode" class="form-label">Passcode</label>
                        <div class="password-container">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control" id="userpasscode" placeholder="Enter your passcode" name="txtuserpasscode" required>
                            </div>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" id="btn_login" name="btn_login" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                    
                    <div class="forgot-password">
                        <a href="#"><i class="fas fa-key me-1"></i>Forgot your passcode?</a>
                    </div>
                </form>
                
                <div class="system-info">
                    <p><i class="fas fa-info-circle"></i> For security reasons, please log out after each session.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('userpasscode');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Auto-focus on email field
        document.getElementById('useremail').focus();
    </script>
</body>
</html>