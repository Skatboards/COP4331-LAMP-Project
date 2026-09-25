<?php

// composer and helper dependencies
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$recipient = $body['email'] ?? $body['to'] ?? '';
$verificationToken = $body['token'] ?? '';

if (!is_string($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['error' => 'A valid email address is required']);
}
if (!is_string($verificationToken) || trim($verificationToken) === '') {
    respond(400, ['error' => 'A verification token is required']);
}

// link to click in email
$verificationBase = getenv('EMAIL_VERIFICATION_URL');
$separator = str_contains($verificationBase, '?') ? '&' : '?';
$verificationLink = $verificationBase . $separator . 'token=' . rawurlencode($verificationToken);

$mail = new PHPMailer(true);

try {
    // configure env vars for SMTP
    $smtpHost = getenv('MAIL_SERVER_HOST');
    $smtpPort = (int) (getenv('EMAIL_SERVER_PORT') ?: 587);
    $smtpUser = getenv('EMAIL_SERVER_USER');
    $smtpPassword = getenv('EMAIL_SERVER_PASSWORD');
    $fromAddress = getenv('EMAIL_FROM');

    if (!$smtpHost || !$smtpUser || !$smtpPassword || !$fromAddress) {
        throw new Exception('Email configuration is incomplete');
    }

    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;

    $mail->isHTML(true);

    // Email header
    $mail->setFrom($fromAddress);
    $mail->addAddress($recipient);
    $mail->Subject = 'Verify your account';
    $safeLink = htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8');

    // Email content
    $mail->Body = '<p>Click the link below to verify your account:</p>'
        . '<p><a href="' . $safeLink . '">Verify your account</a></p>';
    $mail->AltBody = 'Verify your account: ' . $verificationLink;

    $mail->send();

    respond(202, ['message' => 'Verification email sent']);
} catch (Exception $e) {
    error_log('Mailer Error: ' . $mail->ErrorInfo);
    respond(502, ['error' => 'Unable to send verification email']);
}
