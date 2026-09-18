<?php
$username = $body['username'] ?? '';
$password = $body['password'] ?? '';

// validation
if (!is_string($username) || !is_string($password)) {
    respond(400, ['error' => 'Username and password are required']);
}
$username = clean($username);
if ($username === '' || $password === '') {
    respond(400, ['error' => 'Username and password are required']);
}

// database check
$stmt = $db->prepare(
    'SELECT ID AS id, FirstName AS firstName, LastName AS lastName
     FROM Users
     WHERE Username = :username AND Password = :password
     LIMIT 1'
);
$stmt->execute([':username' => $username, ':password' => $password]);
$user = $stmt->fetch();

if (!$user) {
    respond(401, ['error' => 'Invalid username or password']);
}

// success (note: sessions handled by js)
$userId = (int) $user['id'];
respond(200, [
    'id'        => $userId,
    'firstName' => $user['firstName'],
    'lastName'  => $user['lastName'],
    'token'     => (string) $userId,
]);
