<?php
$targetId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$targetId) {
    respond(400, ['error' => 'User ID must be a positive integer']);
}

$body = getRequestBody();
$password = $body['password'] ?? '';
if (!is_string($password) || $password === '' || strlen($password) > 200) {
    respond(400, ['error' => 'Password is required and must be 200 characters or fewer']);
}

$check = $db->prepare('SELECT ID FROM Users WHERE ID = :id LIMIT 1');
$check->execute([':id' => $targetId]);
if (!$check->fetch()) {
    respond(404, ['error' => 'User not found']);
}

$stmt = $db->prepare(
    'UPDATE Users
     SET Password = :password, Date_Updated = UTC_TIMESTAMP()
     WHERE ID = :id'
);
$stmt->execute([
    ':password' => password_hash($password, PASSWORD_DEFAULT),
    ':id' => $targetId,
]);
$db->prepare('DELETE FROM User_Sessions WHERE User_ID = :id')->execute([':id' => $targetId]);

respond(200, ['message' => 'Password updated']);
