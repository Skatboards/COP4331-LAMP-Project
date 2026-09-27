<?php
$email = $body['email'] ?? '';
$password = $body['password'] ?? '';

if (!is_string($email) || !is_string($password)) {
    respond(400, ['error' => 'Email and password are required']);
}
$email = clean($email);
if ($email === '' || $password === '') {
    respond(400, ['error' => 'Email and password are required']);
}

// database check
$stmt = $db->prepare(
    'SELECT ID AS id, FirstName AS firstName, LastName AS lastName,
            Username AS username, Email AS email, Password AS passwordHash,
            Role AS role, Is_Disabled AS isDisabled
     FROM Users
     WHERE Email = :email
     LIMIT 1'
);
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || (int)$user['isDisabled'] === 1 || !password_verify($password, $user['passwordHash'])) {
    respond(401, ['error' => 'Invalid email or password']);
}

$token = bin2hex(random_bytes(32));
$session = $db->prepare(
    'INSERT INTO User_Sessions (User_ID, Token_Hash, Expires_At)
     VALUES (:user_id, :token_hash, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 8 HOUR))'
);
$session->execute([
    ':user_id' => $user['id'],
    ':token_hash' => hash('sha256', $token),
]);

respond(200, [
    'id'        => (int)$user['id'],
    'firstName' => $user['firstName'],
    'lastName'  => $user['lastName'],
    'username'  => $user['username'],
    'email'     => $user['email'],
    'role'      => $user['role'],
    'token'     => $token,
]);
