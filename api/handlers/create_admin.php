<?php
$firstName = clean($body['firstName'] ?? '');
$lastName = clean($body['lastName'] ?? '');
$username = clean($body['username'] ?? '');
$password = $body['password'] ?? '';
$email = normalizeEmail($body['email'] ?? '');

if (!is_string($firstName) || !is_string($lastName) || !is_string($username) ||
    !is_string($password) || !is_string($email)) {
    respond(400, ['error' => 'firstName, lastName, username, password, and email must be strings']);
}
if ($firstName === '' || $lastName === '' || $username === '' || $password === '' || $email === '') {
    respond(400, ['error' => 'First name, last name, username, password, and email are required']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['error' => 'A valid email address is required']);
}
if (strlen($firstName) > 50 || strlen($lastName) > 50 || strlen($username) > 50 ||
    strlen($password) > 200 || strlen($email) > 254) {
    respond(400, ['error' => 'One or more fields exceed the allowed length']);
}

$check = $db->prepare('SELECT ID FROM Users WHERE Email = :email LIMIT 1');
$check->execute([':email' => $email]);
if ($check->fetch()) {
    respond(409, ['error' => 'Email address is already registered']);
}

$stmt = $db->prepare(
    "INSERT INTO Users (FirstName, LastName, Username, Password, Email, Email_Verified, Role, Is_Disabled)
     VALUES (:first_name, :last_name, :username, :password, :email, 0, 'Admin', 0)"
);
$stmt->execute([
    ':first_name' => $firstName,
    ':last_name' => $lastName,
    ':username' => $username,
    ':password' => password_hash($password, PASSWORD_DEFAULT),
    ':email' => $email,
]);

respond(201, [
    'id' => (int)$db->lastInsertId(),
    'firstName' => $firstName,
    'lastName' => $lastName,
    'username' => $username,
    'role' => 'Admin',
    'email' => $email,
]);
