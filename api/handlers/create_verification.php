<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$email = $body['email'] ?? '';
if (!is_string($email)) {
    respond(400, ['error' => 'A valid email address is required']);
}
$email = normalizeEmail($email);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['error' => 'A valid email address is required']);
}

// Keep this response identical for unknown and already verified addresses.
// Log only a hash so the raw email address is not written to server logs.
$emailLogId = hash('sha256', $email);
$genericResponse = function ($reason = null) use ($emailLogId) {
    if ($reason !== null) {
        error_log('Verification email not sent: reason=' . $reason . ' email_hash=' . $emailLogId);
    }
    
    respond(202, ['message' => 'If an account exists for this email address, a verification email has been sent.']);
};

$stmt = $db->prepare(
    'SELECT ID, Email_Verified
     FROM Users
     WHERE Email = :email
     LIMIT 1'
);

$stmt->execute([':email' => $email]);
$user = $stmt->fetch();
if (!$user) {
    $genericResponse('account_not_found');
}
if ((int) $user['Email_Verified'] === 1) {
    $genericResponse('already_verified');
}

$rawToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $rawToken);

$ttlMinutes = max(5, (int) (getenv('EMAIL_VERIFICATION_TTL_MINUTES') ?: 30));

$expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
    ->modify('+' . $ttlMinutes . ' minutes')
    ->format('Y-m-d H:i:s');

// ensure only one verifcation token exists and is active at a time
$transactionStarted = false;
if (!$db->inTransaction()) {
    $db->beginTransaction();
    $transactionStarted = true;
}

try {
    $lock = $db->prepare('SELECT ID FROM Users WHERE ID = :user_id FOR UPDATE');
    $lock->execute([':user_id' => $user['ID']]);

    $replace = $db->prepare(
        'DELETE FROM Email_Verifications
         WHERE User_ID = :user_id
           AND Time_Consumed IS NULL
           AND Time_Expires > UTC_TIMESTAMP()'
    );
    $replace->execute([':user_id' => $user['ID']]);

    $insert = $db->prepare(
        'INSERT INTO Email_Verifications
            (Email_Verification_Token, Time_Created, Time_Expires, User_ID)
         VALUES (:token_hash, UTC_TIMESTAMP(), :expires_at, :user_id)'
    );
    
    $insert->execute([
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
        ':user_id' => $user['ID'],
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Verification token storage error: ' . $e->getMessage());
    respond(500, ['error' => 'Unable to create verification request']);
}

// get url base for email and validate it
$verificationBase = trim((string) getenv('EMAIL_VERIFICATION_URL'));
$verificationParts = $verificationBase !== '' ? parse_url($verificationBase) : false;

$validVerificationUrl = is_array($verificationParts)
    && (strtolower((string) ($verificationParts['scheme'] ?? '')) === 'https')
    && !empty($verificationParts['host'])
    && !isset($verificationParts['user'], $verificationParts['pass'], $verificationParts['query'], $verificationParts['fragment'])
    && filter_var($verificationBase, FILTER_VALIDATE_URL) !== false;

if (!$validVerificationUrl) {
    error_log('EMAIL_VERIFICATION_URL is missing or is not a valid HTTPS URL');
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    respond(500, ['error' => 'Email verification is not configured']);
}

$verificationPage = rtrim($verificationBase, '/') . '/verify.html';
$verificationLink = $verificationPage . '#token=' . rawurlencode($rawToken);

$mail = new PHPMailer(true);
try {
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

    $mail->setFrom($fromAddress);
    $mail->addAddress($email);

    $mail->Subject = 'Verify your account';

    $safeLink = htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8');
    $mail->Body = '<p>Click the link below to verify your account:</p>'
        . '<p><a href="' . $safeLink . '">Verify your account</a></p>';

    $mail->AltBody = 'Verify your account: ' . $verificationLink;
    
    $mail->send();

    if ($transactionStarted) {
        $db->commit();
    }

    if (empty($suppressVerificationResponse)) {
        $genericResponse();
    }

} catch (Throwable $e) {
    error_log('Verification email error: ' . ($mail->ErrorInfo ?: $e->getMessage()));
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    respond(502, ['error' => 'Unable to send verification email']);
}
