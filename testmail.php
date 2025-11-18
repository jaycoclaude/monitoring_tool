<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

echo "Initializing mailer...<br>";

$mail = new PHPMailer(true);

try {
    echo "Configuring SMTP settings...<br>";

    // Enable SMTP debug output
    $mail->SMTPDebug = 2; // Or 3 or 4 for more verbosity
    $mail->Debugoutput = 'html';

    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'mail.rwandafda.gov.rw';   
    $mail->SMTPAuth   = true;
    $mail->Username   = 'notification@rwandafda.gov.rw';
    $mail->Password   = 'NOtification@#135';       
    $mail->SMTPSecure = 'tls';                     
    $mail->Port       = 587;                       

    echo "SMTP settings configured.<br>";

    // Sender and recipient
    echo "Setting sender and recipient...<br>";
    $mail->setFrom('notification@rwandafda.gov.rw', 'RWANDA FDA Notification');
    $mail->addAddress('nshimiyimanaclaude0788@gmail.com', 'Receiver Name');

    // Email content
    echo "Composing message...<br>";
    $mail->isHTML(false);
    $mail->Subject = 'Test Email from Rwanda FDA Monitoring Tool';
    $mail->Body    = 'This is a test email sent .';

    // Send email
    echo "Sending email...<br>";
    $mail->send();
    echo '✅ Email has been sent successfully.';

} catch (Exception $e) {
    echo "❌ Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
