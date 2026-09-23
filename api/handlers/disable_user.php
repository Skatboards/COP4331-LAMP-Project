<?php
$targetId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$targetId) {
    respond(400, ['error' => 'User ID must be a positive integer']);
}

$adminId = (int)$admin['id'];
if ($targetId === $adminId) {
    respond(400, ['error' => 'Admins cannot disable their own account']);
}

$body = getRequestBody();
$disabled = $body['disabled'] ?? null;
if (!is_bool($disabled)) {
    respond(400, ['error' => 'disabled must be a boolean']);
}

$stmt = $db->prepare(
    'UPDATE Users
     SET Is_Disabled = :disabled, Date_Updated = UTC_TIMESTAMP()
     WHERE ID = :id'
);
$stmt->execute([':disabled' => $disabled ? 1 : 0, ':id' => $targetId]);
if ($stmt->rowCount() === 0) {
    $check = $db->prepare('SELECT ID FROM Users WHERE ID = :id LIMIT 1');
    $check->execute([':id' => $targetId]);
    if (!$check->fetch()) {
        respond(404, ['error' => 'User not found']);
    }
}

if ($disabled) {
    $db->prepare('DELETE FROM User_Sessions WHERE User_ID = :id')->execute([':id' => $targetId]);
}

respond(200, ['message' => $disabled ? 'User disabled' : 'User enabled']);
