<?php
// SMTP test script for I Mobile.lk
// Usage: visit http://localhost/imobile/inc/smtp_test.php in your browser

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';
require_once __DIR__ . '/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$to = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
if (!$to) {
    echo "ADMIN_EMAIL is not set in inc/config.php.\n";
    exit;
}

try {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';

    if (defined('SMTP_HOST') && SMTP_HOST) {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : '';
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    }

    $mail->setFrom(defined('MAIL_FROM') ? MAIL_FROM : 'noreply@imobile.lk', defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'I Mobile.lk');
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test from I Mobile.lk';
    $mail->Body = '<p>This is a test email sent from <strong>I Mobile.lk</strong> to verify SMTP settings.</p>';

    if ($mail->send()) {
        echo "Test email sent to {$to}. Check the mailbox.\n";
    } else {
        echo "Failed to send test email. Mailer Error: " . $mail->ErrorInfo . "\n";
    }
} catch (Exception $e) {
    echo "Exception while sending: " . $e->getMessage() . "\n";
    error_log('SMTP test error: ' . $e->getMessage());
}
