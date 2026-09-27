<?php
$firstName = $body['firstName'] ?? '';
$lastName = $body['lastName'] ?? '';
$username = $body['username'] ?? '';
$password = $body['password'] ?? '';
$email = $body['email'] ?? '';


// validation
if (!is_string($firstName) || !is_string($lastName) ||
    !is_string($username) || !is_string($password) || !is_string($email)) {
    respond(400, ['error' => 'firstName, lastName, username, password, and email must be strings']);
}

$firstName = clean($firstName);
$lastName = clean($lastName);
$username = clean($username);
$email = clean($email);

if ($firstName === '' || $lastName === '' || $username === '' || $password === '' || $email === '') {
    respond(400, ['error' => 'First name, last name, username, password, and email are required']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['error' => 'A valid email address is required']);
}

// Validate the plaintext password before hashing it for storage.
$limits = [
    'firstName' => [$firstName, 50],
    'lastName'  => [$lastName, 50],
    'username'  => [$username, 50],
    'password'  => [$password, 200],
    'email'     => [$email, 254],
];
foreach ($limits as $field => [$value, $maxLength]) {
    if (strlen($value) > $maxLength) {
        respond(400, ['error' => $field . ' must be ' . $maxLength . ' characters or fewer']);
    }
}

$check = $db->prepare('SELECT ID FROM Users WHERE Email = :email LIMIT 1');
$check->execute([':email' => $email]);
if ($check->fetch()) {
    // Do not disclose whether an email address belongs to an account.
    respond(202, ['message' => 'If an account exists for this email address, a verification email has been sent.']);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare(
    "INSERT INTO Users (FirstName, LastName, Username, Password, Email, Email_Verified, Role, Is_Disabled)
     VALUES (:first_name, :last_name, :username, :password, :email, 0, 'User', 0)"
);
$stmt->execute([
    ':first_name' => $firstName,
    ':last_name'  => $lastName,
    ':username'   => $username,
    ':password'   => $passwordHash,
    ':email'      => $email,
]);

respond(201, [
    'id'        => (int) $db->lastInsertId(),
    'firstName' => $firstName,
    'lastName'  => $lastName,
    'username'  => $username,
    'email'     => $email,
]);
