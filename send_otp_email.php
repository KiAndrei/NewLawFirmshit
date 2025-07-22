<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'config.php';

function send_otp_email($to, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Your Opiña Law Office Registration OTP';
        $mail->Body = '<h2>Your OTP Code</h2><p style="font-size:1.5em;font-weight:bold;">' . $otp . '</p><p>This code will expire in 5 minutes.</p>';
        $mail->send();
    } catch (Exception $e) {
        // For debugging: echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
} 