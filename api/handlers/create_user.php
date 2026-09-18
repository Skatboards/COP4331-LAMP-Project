<?php
$firstName = $body['firstName'] ?? '';
$lastName = $body['lastName'] ?? '';
$username = $body['username'] ?? '';
$password = $body['password'] ?? '';

if (!is_string($firstName) || !is_string($lastName) ||
    !is_string($username) || !is_string($password)) {
    respond(400, ['error' => 'firstName, lastName, username, and password must be strings']);
}

$firstName = clean($firstName);
$lastName = clean($lastName);
$username = clean($username);

if ($firstName === '' || $lastName === '' || $username === '' || $password === '') {
    respond(400, ['error' => 'First name, last name, username, and password are required']);
}

$limits = [
    'firstName' => [$firstName, 50],
    'lastName'  => [$lastName, 50],
    'username'  => [$username, 50],
    'password'  => [$password, 50],
];
foreach ($limits as $field => [$value, $maxLength]) {
    if (strlen($value) > $maxLength) {
        respond(400, ['error' => $field . ' must be ' . $maxLength . ' characters or fewer']);
    }
}

$check = $db->prepare('SELECT ID FROM Users WHERE Username = :username LIMIT 1');
$check->execute([':username' => $username]);
if ($check->fetch()) {
    respond(409, ['error' => 'Username is already registered']);
}

// The current schema stores Password as VARCHAR(50) and the existing login
// handler compares it directly. Keep new accounts compatible with that schema.
$stmt = $db->prepare(
    'INSERT INTO Users (FirstName, LastName, Username, Password)
     VALUES (:first_name, :last_name, :username, :password)'
);
$stmt->execute([
    ':first_name' => $firstName,
    ':last_name'  => $lastName,
    ':username'   => $username,
    ':password'   => $password,
]);

respond(201, [
    'id'        => (int) $db->lastInsertId(),
    'firstName' => $firstName,
    'lastName'  => $lastName,
    'username'  => $username,
]);
