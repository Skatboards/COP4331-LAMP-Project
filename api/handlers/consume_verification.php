<?php
$token = ($body ?? [])['token'] ?? '';
if (!is_string($token) || trim($token) === '') {
    respond(400, ['error' => 'A verification token is required']);
}

$tokenHash = hash('sha256', $token);
$db->beginTransaction();
try {
    $stmt = $db->prepare(
        'SELECT ev.ID AS verificationId, ev.User_ID AS userId
         FROM Email_Verifications ev
         WHERE ev.Email_Verification_Token = :token_hash
           AND ev.Time_Consumed IS NULL
           AND ev.Time_Expires > UTC_TIMESTAMP()
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->execute([':token_hash' => $tokenHash]);
    $verification = $stmt->fetch();

    if (!$verification) {
        $db->rollBack();
        respond(400, ['error' => 'Verification token is invalid or expired']);
    }

    $consume = $db->prepare(
        'UPDATE Email_Verifications
         SET Time_Consumed = UTC_TIMESTAMP()
         WHERE ID = :verification_id AND Time_Consumed IS NULL'
    );

    $consume->execute([':verification_id' => $verification['verificationId']]);

    $user = $db->prepare('UPDATE Users SET Email_Verified = 1 WHERE ID = :user_id');
    $user->execute([':user_id' => $verification['userId']]);

    $db->commit();

    respond(200, ['message' => 'Email address verified']);

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log('Verification consumption error: ' . $e->getMessage());
    respond(500, ['error' => 'Unable to verify email address']);
}
