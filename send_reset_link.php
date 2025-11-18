<?php
session_start();
require_once 'includes/config.php';

// These MUST be at the top level (after requires)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: forgot_password.php?error=Please+enter+a+valid+email+address');
    exit();
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id FROM tbl_hm_users WHERE user_email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $token_hash = password_hash($token, PASSWORD_DEFAULT);
        $expiry = date('Y-m-d H:i:s', time() + 1800); // 30 minutes

        // Save to DB
        $stmt = $pdo->prepare("UPDATE tbl_hm_users 
                               SET reset_token_hash = ?, reset_token_expires_at = ? 
                               WHERE user_id = ?");
        $stmt->execute([$token_hash, $expiry, $user['user_id']]);


        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        require_once 'PHPMailer/src/Exception.php';

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'mail.rwandafda.gov.rw';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'notification@rwandafda.gov.rw';
        $mail->Password   = 'NOtification@#135';                    // ← Your working password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // ← Correct constant
        $mail->Port       = 587;
        $mail->Timeout    = 30;

        // Recipients
        $mail->setFrom('notification@rwandafda.gov.rw', 'Rwanda FDA - HM System');
        $mail->addAddress($email);

        // Build reset link
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
        $base_url = rtrim($base_url, '/\\') . '/';
        $reset_link = $base_url . 'reset_password.php?token=' . urlencode($token);

        // Beautiful HTML email - SAME STYLE AS ACCOUNT CREATION
        $message = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Reset Your Passcode - Rwanda FDA</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #008751; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .button-link { display: inline-block; background: #008751; color: white; padding: 14px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 20px 0; }
                .footer { background: #34495e; color: white; padding: 20px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Rwanda FDA Monitoring Tool</h1>
                    <p>Food and Drugs Authority</p>
                </div>
               
                <div class='content'>
                    <h2>Passcode Reset Request</h2>
                    <p>Dear Sir/Madam,</p>
                    <p>We have received a request to reset the passcode for your account associated with this email address.</p>
                    
                    <p style='text-align: center;'>
                        <a href='$reset_link' class='button-link'>Reset My Passcode Now</a>
                    </p>
                    
                    <p>Or copy and paste this link into your browser:<br>
                    <a href='$reset_link' style='color: #008751; word-break: break-all;'>$reset_link</a></p>
                    
                    <div class='warning'>
                        <strong>Security Notice:</strong><br>
                        This link will expire in <strong>30 minutes</strong>. If you did not request a password reset, please ignore this email  your account remains secure.
                    </div>
                    
                    <p><strong>Access the system:</strong><br>
                    <a href='https://rwandafda.gov.rw/monitoring-tool/' style='color: #008751;'>https://rwandafda.gov.rw/monitoring-tool/</a></p>
                </div>
               
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Rwanda Food and Drugs Authority. All rights reserved.</p>
                    <p>This is an automated notification. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Set email content
        $mail->isHTML(true);
        $mail->Subject = "Passcode Reset Request - Rwanda FDA HM System";
        $mail->Body    = $message;
        $mail->AltBody = "Reset your passcode here: $reset_link\n\nThis link expires in 30 minutes.\n\nIf you didn't request this, ignore this email.";

        $mail->send();

        error_log("Password reset email sent successfully to: $email");
    }

    // Always show success (prevents email enumeration)
    header('Location: forgot_password.php?sent=1');
    exit();

} catch (Exception $e) {
    error_log("Password Reset Email Failed (Email: $email) - Error: " . $mail->ErrorInfo ?? $e->getMessage());
    header('Location: forgot_password.php?sent=1');
    exit();
}