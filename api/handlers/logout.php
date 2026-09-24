<?php
$token = getBearerToken();

if ($token) {
    $stmt = $db->prepare('DELETE FROM User_Sessions WHERE Token_Hash = :token_hash');
    $stmt->execute([':token_hash' => hash('sha256', $token)]);
}

respond(200, ['message' => 'Logged out']);