<?php
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    respond(400, ['error' => 'Contact ID is required — use ?id=']);
}

$stmt = $db->prepare('DELETE FROM Contacts WHERE ID = :id AND User_ID = :user_id');
$stmt->execute([':id' => $id, ':user_id' => $userId]);
if ($stmt->rowCount() === 0) {
    respond(404, ['error' => 'Contact not found']);
}

respond(200, ['message' => 'Contact deleted']);
