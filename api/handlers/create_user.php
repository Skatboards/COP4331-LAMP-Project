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
$email = normalizeEmail($email);

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
    error_log('Registration verification email not sent: reason=account_already_exists email_hash=' . hash('sha256', $email));
    respond(202, ['message' => 'If an account exists for this email address, a verification email has been sent.']);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare(
    "INSERT INTO Users (FirstName, LastName, Username, Password, Email, Email_Verified, Role, Is_Disabled, Date_Created, Date_Updated)
     VALUES (:first_name, :last_name, :username, :password, :email, 0, 'User', 0, UTC_TIMESTAMP(), UTC_TIMESTAMP())"
);

try {
    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name'  => $lastName,
        ':username'   => $username,
        ':password'   => $passwordHash,
        ':email'      => $email,
    ]);
} catch (Throwable $e) {
    error_log('User registration error: ' . $e->getMessage());
    respond(500, ['error' => 'Unable to create account']);
}

$newUserId = (int) $db->lastInsertId();

// attempt to send verification email
$suppressVerificationResponse = true;
require __DIR__ . '/create_verification.php';

respond(201, [
    'id'        => $newUserId,
    'firstName' => $firstName,
    'lastName'  => $lastName,
    'username'  => $username,
    'email'     => $email,
]);
