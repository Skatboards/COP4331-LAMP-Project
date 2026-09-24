<?php
$firstName = clean($body['firstName'] ?? '');
$lastName = clean($body['lastName'] ?? '');
$username = clean($body['username'] ?? '');
$password = $body['password'] ?? '';

if (!is_string($firstName) || !is_string($lastName) || !is_string($username) || !is_string($password)) {
    respond(400, ['error' => 'firstName, lastName, username, and password must be strings']);
}
if ($firstName === '' || $lastName === '' || $username === '' || $password === '') {
    respond(400, ['error' => 'First name, last name, username, and password are required']);
}
if (strlen($firstName) > 50 || strlen($lastName) > 50 || strlen($username) > 50 || strlen($password) > 200) {
    respond(400, ['error' => 'One or more fields exceed the allowed length']);
}

$check = $db->prepare('SELECT ID FROM Users WHERE Username = :username LIMIT 1');
$check->execute([':username' => $username]);
if ($check->fetch()) {
    respond(409, ['error' => 'Username is already registered']);
}

$stmt = $db->prepare(
    "INSERT INTO Users (FirstName, LastName, Username, Password, Role, Is_Disabled)
     VALUES (:first_name, :last_name, :username, :password, 'Admin', 0)"
);
$stmt->execute([
    ':first_name' => $firstName,
    ':last_name' => $lastName,
    ':username' => $username,
    ':password' => password_hash($password, PASSWORD_DEFAULT),
]);

respond(201, [
    'id' => (int)$db->lastInsertId(),
    'firstName' => $firstName,
    'lastName' => $lastName,
    'username' => $username,
    'role' => 'Admin',
]);
